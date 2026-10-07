<?php

namespace App\Filament\Resources\CardResource\Pages;

use App\Enums\CardProduct;
use App\Filament\Resources\CardResource;
use App\Services\TravelWallet\CardProductCatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateCard extends CreateRecord
{
    protected static string $resource = CardResource::class;

    protected ?string $benefitSet = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->benefitSet = $data['benefit_set'] ?? null;
        unset($data['benefit_set']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $selected = $this->benefitSet ?? ($this->form->getRawState()['benefit_set'] ?? null);

        $product = CardProduct::hasValue($selected)
            ? CardProduct::fromValue($selected)
            : CardProduct::fromCardName((string) $this->record->name);

        if ($product === null) {
            return;
        }

        app(CardProductCatalog::class)->apply($this->record, $product);
    }
}
