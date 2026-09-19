<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Ai\Agents\PromoExtractionAgent;
use App\Enums\PointsProgram;
use App\Enums\PromoStatus;
use App\Models\Card;
use App\Models\EarningPromotion;
use App\Models\LoyaltyProgram;
use App\Models\PromoSourceItem;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use App\Services\TravelWallet\PromoSources\DoctorOfCreditRssSource;
use Illuminate\Support\Str;

class PromoIngestor
{
    public function __construct(
        private PromoExtractionAgent $agent,
        private DoctorOfCreditRssSource $doctorOfCredit,
    ) {}

    public function ingestFeeds(): int
    {
        $count = 0;
        foreach ($this->doctorOfCredit->fetch() as $item) {
            $count += $this->storeExtracted(
                $this->doctorOfCredit->key(),
                $item['external_id'],
                $item['title'],
                $item['snippet'],
            );
        }

        return $count;
    }

    public function ingestPlaintext(string $text, string $source = 'paste'): int
    {
        $externalId = 'paste-'.sha1($text);

        return $this->storeExtracted($source, $externalId, Str::limit($text, 80), $text);
    }

    public function approve(PromoSourceItem $item): void
    {
        $facts = $item->facts ?? [];
        $type = $facts['type'] ?? null;

        if ($type === 'transfer_bonus') {
            $this->approveTransferBonus($item, $facts);
        } elseif ($type === 'earning_promotion') {
            $this->approveEarningPromotion($item, $facts);
        }

        $item->status = PromoStatus::Active;
        $item->save();
    }

    public function reject(PromoSourceItem $item): void
    {
        $item->status = PromoStatus::Rejected;
        $item->save();
    }

    private function storeExtracted(string $source, string $externalId, string $title, string $snippet): int
    {
        $existing = PromoSourceItem::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->first();

        if ($existing) {
            return 0;
        }

        $facts = $this->extractFacts($title, $snippet);
        $created = 0;

        foreach ($facts as $fact) {
            PromoSourceItem::query()->create([
                'source' => $source,
                'external_id' => $created === 0 ? $externalId : $externalId.'-'.$created,
                'title' => $title,
                'facts' => $fact,
                'status' => PromoStatus::PendingReview,
                'fetched_at' => now(),
            ]);
            $created++;
        }

        if ($created === 0) {
            PromoSourceItem::query()->create([
                'source' => $source,
                'external_id' => $externalId,
                'title' => $title,
                'facts' => ['type' => 'unknown', 'summary' => $snippet],
                'status' => PromoStatus::PendingReview,
                'fetched_at' => now(),
            ]);
            $created = 1;
        }

        return $created;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractFacts(string $title, string $snippet): array
    {
        $prompt = "Title: {$title}\nBlurb: {$snippet}";
        $raw = $this->agent->ask($prompt);
        $json = $this->decodeJson($raw);

        $items = $json['items'] ?? [];
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, fn (mixed $item): bool => is_array($item)));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $trimmed = trim($raw);
        $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        $decoded = json_decode($trimmed, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function approveTransferBonus(PromoSourceItem $item, array $facts): void
    {
        $from = (string) ($facts['from_program'] ?? PointsProgram::Unknown);
        $toCode = strtolower((string) ($facts['to_program_code'] ?? ''));
        $program = LoyaltyProgram::query()->where('code', $toCode)->first();
        if ($program === null) {
            return;
        }

        $route = TransferRoute::query()
            ->where('from_program', $from)
            ->where('loyalty_program_id', $program->id)
            ->first();
        if ($route === null) {
            return;
        }

        TransferBonus::query()->updateOrCreate(
            [
                'promo_source_item_id' => $item->id,
            ],
            [
                'transfer_route_id' => $route->id,
                'bonus_percent' => (int) ($facts['bonus_percent'] ?? 0),
                'starts_at' => $facts['starts_at'] ?? null,
                'ends_at' => $facts['ends_at'] ?? null,
                'status' => PromoStatus::Active,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function approveEarningPromotion(PromoSourceItem $item, array $facts): void
    {
        $cardId = null;
        if (filled($facts['card_name'] ?? null)) {
            $cardId = Card::query()
                ->where('name', 'like', '%'.$facts['card_name'].'%')
                ->value('id');
        }

        EarningPromotion::query()->updateOrCreate(
            [
                'promo_source_item_id' => $item->id,
            ],
            [
                'card_id' => $cardId,
                'points_program' => $facts['points_program'] ?? null,
                'category' => $facts['category'] ?? null,
                'merchant' => $facts['merchant'] ?? null,
                'multiplier' => (float) ($facts['multiplier'] ?? 1),
                'starts_at' => $facts['starts_at'] ?? null,
                'ends_at' => $facts['ends_at'] ?? null,
                'status' => PromoStatus::Active,
                'summary' => $facts['summary'] ?? $item->title,
            ],
        );
    }
}
