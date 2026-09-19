<?php

declare(strict_types=1);

namespace App\Filament\Actions\Concerns;

use Filament\Tables\Contracts\HasTable;
use Livewire\Component;

trait RefreshesBenefitTable
{
    protected function refreshBenefitTableAfterMutation(): static
    {
        $this->after(function (): void {
            $livewire = $this->getLivewire();

            if ($livewire instanceof HasTable) {
                $livewire->resetTable();
            }

            if ($livewire instanceof Component) {
                $livewire->dispatch('benefit-usage-recorded');
            }
        });

        return $this;
    }
}
