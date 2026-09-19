<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Actions\Concerns\RefreshesBenefitTable;
use Filament\Actions\BulkAction;

abstract class BenefitMutationBulkAction extends BulkAction
{
    use RefreshesBenefitTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deselectRecordsAfterCompletion();
        $this->refreshBenefitTableAfterMutation();
    }
}
