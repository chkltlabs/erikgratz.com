<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Actions\Concerns\RefreshesBenefitTable;
use Filament\Actions\Action;

abstract class BenefitMutationAction extends Action
{
    use RefreshesBenefitTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshBenefitTableAfterMutation();
    }
}
