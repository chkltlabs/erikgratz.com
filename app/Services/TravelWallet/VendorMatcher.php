<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Models\LoyaltyProgram;

class VendorMatcher
{
    public function matches(?string $vendor, string $needle): bool
    {
        $vendor = strtolower(trim((string) $vendor));
        $needle = strtolower(trim($needle));

        if ($vendor === '' || $needle === '') {
            return false;
        }

        if (str_contains($vendor, $needle) || str_contains($needle, $vendor)) {
            return true;
        }

        foreach ($this->aliases() as $alias => $codes) {
            if (str_contains($vendor, $alias) && in_array($needle, $codes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $needles
     */
    public function matchesAny(?string $vendor, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->matches($vendor, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function programMatches(LoyaltyProgram $program, string $vendor): bool
    {
        $vendor = strtolower(trim($vendor));
        $name = strtolower($program->name);
        $code = strtolower($program->code);

        if (str_contains($name, $vendor) || str_contains($vendor, $name) || str_contains($vendor, $code) || str_contains($code, $vendor)) {
            return true;
        }

        foreach ($this->aliases() as $needle => $codes) {
            if (str_contains($vendor, $needle) && in_array($code, $codes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function aliases(): array
    {
        return [
            'air canada' => ['aeroplan'],
            'british airways' => ['ba'],
            'finnair' => ['finnair'],
            'swiss' => ['lufthansa'],
            'austrian' => ['lufthansa'],
            'brussels' => ['lufthansa'],
            'eurowings' => ['lufthansa'],
            'hawaiian' => ['alaska'],
            'jetstar' => ['qantas'],
            'scoot' => ['singapore'],
            'japan airlines' => ['jal'],
            'all nippon' => ['ana'],
            'virgin australia' => ['velocity'],
            'tap portugal' => ['tap'],
            'tap air' => ['tap'],
            'air new zealand' => ['airnz'],
            'westin' => ['marriott'],
            'sheraton' => ['marriott'],
            'hampton' => ['hilton'],
            'doubletree' => ['hilton'],
            'holiday inn' => ['ihg'],
            'intercontinental' => ['ihg'],
        ];
    }
}
