<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceOnDelete('payments', 'card_id', 'cards', 'set null');

        $this->ensureForeign('card_benefits', 'card_id', 'cards', 'cascade');
        $this->ensureForeign('card_benefits', 'loyalty_membership_id', 'loyalty_memberships', 'set null');

        $this->ensureForeign('benefit_usages', 'card_benefit_id', 'card_benefits', 'cascade');
        $this->ensureForeign('benefit_usages', 'activity_id', 'activities', 'set null');
        $this->ensureForeign('benefit_usages', 'payment_id', 'payments', 'set null');

        $this->ensureForeign('loyalty_memberships', 'loyalty_program_id', 'loyalty_programs', 'cascade');
        $this->ensureForeign('loyalty_memberships', 'conferred_by_card_id', 'cards', 'cascade');

        $this->ensureForeign('transfer_routes', 'loyalty_program_id', 'loyalty_programs', 'cascade');
        $this->ensureForeign('transfer_bonuses', 'transfer_route_id', 'transfer_routes', 'cascade');
        $this->ensureForeign('transfer_bonuses', 'promo_source_item_id', 'promo_source_items', 'cascade');

        $this->ensureForeign('earning_promotions', 'card_id', 'cards', 'cascade');
        $this->ensureForeign('earning_promotions', 'promo_source_item_id', 'promo_source_items', 'cascade');

        $this->ensureForeign('booking_intents', 'activity_id', 'activities', 'set null');

        $this->ensureForeign('card_earning_rates', 'card_id', 'cards', 'cascade');

        $this->ensureForeign('booking_perks', 'loyalty_program_id', 'loyalty_programs', 'cascade');
        $this->ensureForeign('booking_perks', 'loyalty_membership_id', 'loyalty_memberships', 'cascade');
        $this->ensureForeign('booking_perks', 'card_id', 'cards', 'cascade');
        $this->ensureForeign('booking_perks', 'card_benefit_id', 'card_benefits', 'cascade');
    }

    public function down(): void
    {
        $this->dropForeignIfPresent('booking_perks', 'card_benefit_id');
        $this->dropForeignIfPresent('booking_perks', 'card_id');
        $this->dropForeignIfPresent('booking_perks', 'loyalty_membership_id');
        $this->dropForeignIfPresent('booking_perks', 'loyalty_program_id');
        $this->dropForeignIfPresent('card_earning_rates', 'card_id');
        $this->dropForeignIfPresent('booking_intents', 'activity_id');
        $this->dropForeignIfPresent('earning_promotions', 'promo_source_item_id');
        $this->dropForeignIfPresent('earning_promotions', 'card_id');
        $this->dropForeignIfPresent('transfer_bonuses', 'promo_source_item_id');
        $this->dropForeignIfPresent('transfer_bonuses', 'transfer_route_id');
        $this->dropForeignIfPresent('transfer_routes', 'loyalty_program_id');
        $this->dropForeignIfPresent('loyalty_memberships', 'conferred_by_card_id');
        $this->dropForeignIfPresent('loyalty_memberships', 'loyalty_program_id');
        $this->dropForeignIfPresent('benefit_usages', 'payment_id');
        $this->dropForeignIfPresent('benefit_usages', 'activity_id');
        $this->dropForeignIfPresent('benefit_usages', 'card_benefit_id');
        $this->dropForeignIfPresent('card_benefits', 'loyalty_membership_id');
        $this->dropForeignIfPresent('card_benefits', 'card_id');
        $this->replaceOnDelete('payments', 'card_id', 'cards', 'no action');
    }

    /**
     * @param  'cascade'|'set null'  $onDelete
     */
    private function ensureForeign(string $table, string $column, string $parent, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $existing = $this->foreignOn($table, $column);
        if ($existing !== null) {
            if ($this->onDeleteMatches($existing['on_delete'] ?? '', $onDelete)) {
                return;
            }

            $this->dropNamedForeign($table, $existing['name']);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $onDelete): void {
            $foreign = $blueprint->foreign($column)->references('id')->on($parent);
            if ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
    }

    /**
     * @param  'cascade'|'set null'|'no action'  $onDelete
     */
    private function replaceOnDelete(string $table, string $column, string $parent, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $existing = $this->foreignOn($table, $column);
        if ($existing !== null) {
            if ($this->onDeleteMatches($existing['on_delete'] ?? '', $onDelete)) {
                return;
            }
            $this->dropNamedForeign($table, $existing['name']);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $onDelete): void {
            $foreign = $blueprint->foreign($column)->references('id')->on($parent);
            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'set null' => $foreign->nullOnDelete(),
                default => $foreign,
            };
        });
    }

    private function dropForeignIfPresent(string $table, string $column): void
    {
        $existing = $this->foreignOn($table, $column);
        if ($existing === null) {
            return;
        }

        $this->dropNamedForeign($table, $existing['name']);
    }

    /**
     * @return array{name: string, on_delete: string}|null
     */
    private function foreignOn(string $table, string $column): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreign) {
            if (in_array($column, $foreign['columns'] ?? [], true)) {
                return [
                    'name' => $foreign['name'],
                    'on_delete' => (string) ($foreign['on_delete'] ?? ''),
                ];
            }
        }

        return null;
    }

    private function dropNamedForeign(string $table, string $name): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropForeign($name);
        });
    }

    private function onDeleteMatches(string $actual, string $wanted): bool
    {
        $actual = strtolower(str_replace(['_', ' '], '', $actual));
        $wanted = strtolower(str_replace(['_', ' '], '', $wanted));

        return $actual === $wanted
            || ($wanted === 'setnull' && in_array($actual, ['setnull', 'null'], true))
            || ($wanted === 'noaction' && in_array($actual, ['noaction', 'restrict', ''], true));
    }
};
