<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Forms\BookingPerkForm;
use Filament\Forms\Components\TextInput;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingPerkFormTest extends TestCase
{
    #[Test]
    public function default_schema_omits_min_tier(): void
    {
        $names = array_map(
            fn ($component): ?string => $component->getName(),
            BookingPerkForm::components(),
        );

        $this->assertContains('name', $names);
        $this->assertContains('decision_value', $names);
        $this->assertContains('applies_to', $names);
        $this->assertNotContains('min_tier', $names);
    }

    #[Test]
    public function inherited_schema_includes_min_tier(): void
    {
        $components = BookingPerkForm::components(includeMinTier: true);
        $minTier = collect($components)->first(
            fn ($component): bool => $component instanceof TextInput && $component->getName() === 'min_tier',
        );

        $this->assertNotNull($minTier);
    }
}
