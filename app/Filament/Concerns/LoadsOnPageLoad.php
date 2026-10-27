<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

/**
 * Filament lazy widgets wait for Alpine's intersection observer. On a cold
 * admin load that observer runs before layout exists, so the widget never
 * requests its contents. Loading when the page is ready still defers the
 * query out of the first HTML response.
 */
trait LoadsOnPageLoad
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        $properties = parent::getDefaultProperties();

        if (($properties['lazy'] ?? null) === true) {
            $properties['lazy'] = 'on-load';
        }

        return $properties;
    }
}
