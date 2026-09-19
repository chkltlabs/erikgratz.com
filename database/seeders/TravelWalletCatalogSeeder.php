<?php

namespace Database\Seeders;

use App\Models\BookingPerk;
use App\Models\LoyaltyProgram;
use App\Models\TransferRoute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TravelWalletCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->json('loyalty-programs.json') as $program) {
            LoyaltyProgram::query()->updateOrCreate(
                ['code' => $program['code']],
                $program,
            );
        }

        $codes = LoyaltyProgram::query()->pluck('id', 'code');

        foreach ($this->json('transfer-routes.json') as $route) {
            $programId = $codes[$route['to']] ?? null;
            if ($programId === null) {
                continue;
            }

            TransferRoute::query()->updateOrCreate(
                [
                    'from_program' => $route['from'],
                    'loyalty_program_id' => $programId,
                ],
                [
                    'base_ratio' => $route['ratio'] ?? 1,
                    'is_active' => true,
                ],
            );
        }

        foreach ($this->json('loyalty-program-perks.json') as $code => $perks) {
            $programId = $codes[$code] ?? null;
            if ($programId === null) {
                continue;
            }

            foreach ($perks as $perk) {
                BookingPerk::query()->updateOrCreate(
                    [
                        'loyalty_program_id' => $programId,
                        'name' => $perk['name'],
                    ],
                    [
                        'loyalty_membership_id' => null,
                        'card_id' => null,
                        'card_benefit_id' => null,
                        ...$perk,
                    ],
                );
            }
        }

        $this->call(HeldCardBenefitsSeeder::class);
    }

    /**
     * @return array<mixed>
     */
    private function json(string $file): array
    {
        return json_decode(File::get(database_path('data/'.$file)), true, 512, JSON_THROW_ON_ERROR);
    }
}
