<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\PointsProgram;
use App\Models\Card;
use App\Models\LoyaltyMembership;
use App\Models\User;

class PointsByProgramTotals
{
    /**
     * @return list<array{key: string, label: string, cards: int, loyalty: int, total: int}>
     */
    public function forHousehold(string $household = 'total'): array
    {
        $userId = $this->userIdFor($household);
        $rows = [];

        $cardQuery = Card::query()->where('points_program', '!=', PointsProgram::Unknown);
        if ($userId !== null) {
            $cardQuery->where('user_id', $userId);
        }

        $cardSums = $cardQuery
            ->selectRaw('points_program, coalesce(sum(points_balance), 0) as points')
            ->groupBy('points_program')
            ->pluck('points', 'points_program');

        $labels = PointsProgram::asSelectArray();

        foreach ($cardSums as $program => $points) {
            $cards = (int) $points;
            if ($cards === 0) {
                continue;
            }

            $key = (string) $program;
            $rows[$key] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'cards' => $cards,
                'loyalty' => 0,
                'total' => $cards,
            ];
        }

        $membershipQuery = LoyaltyMembership::query()->with('program');
        if ($userId !== null) {
            $membershipQuery->where('user_id', $userId);
        }

        $aviosCodes = $this->aviosProgramCodes();

        foreach ($membershipQuery->get() as $membership) {
            $code = $membership->program?->code;
            $loyalty = (int) $membership->points_balance;
            if ($code === null || $loyalty === 0) {
                continue;
            }

            if (in_array($code, $aviosCodes, true)) {
                $key = PointsProgram::Avios;
                $label = $labels[$key] ?? 'Avios';
            } elseif ($code === PointsProgram::Aeroplan) {
                $key = PointsProgram::Aeroplan;
                $label = $labels[$key] ?? 'Aeroplan';
            } else {
                $key = 'loyalty:'.$code;
                $label = $membership->program->name;
            }

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'cards' => 0,
                    'loyalty' => 0,
                    'total' => 0,
                ];
            }

            $rows[$key]['loyalty'] += $loyalty;
            $rows[$key]['total'] = $rows[$key]['cards'] + $rows[$key]['loyalty'];
        }

        $visible = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['total'] > 0
        ));

        usort($visible, fn (array $left, array $right): int => $right['total'] <=> $left['total']);

        return $visible;
    }

    /**
     * @return array<string, string>
     */
    public function householdOptions(): array
    {
        $options = ['total' => 'Total'];

        if (User::erik() !== null) {
            $options['erik'] = 'Erik';
        }
        if (User::amy() !== null) {
            $options['amy'] = 'Amy';
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function aviosProgramCodes(): array
    {
        /** @var list<string> $codes */
        $codes = config('travel-wallet.avios_program_codes', []);

        return $codes;
    }

    private function userIdFor(string $household): ?int
    {
        $user = match ($household) {
            'erik' => User::erik(),
            'amy' => User::amy(),
            default => null,
        };

        return $user?->id;
    }
}
