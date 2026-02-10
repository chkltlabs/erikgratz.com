<?php

namespace Tests\Feature\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource;
use App\Models\SimpleFin\SimpleFinTransaction;
use Tests\Feature\Filament\Parent\InfolistResource;

class SimpleFinTransactionResourceTest extends InfolistResource
{
    public static string $resource = SimpleFinTransactionResource::class;

    public static string $indexPage = SimpleFinTransactionResource\Pages\ListSimpleFinTransactions::class;

    public static string $viewPage = SimpleFinTransactionResource\Pages\ViewSimpleFinTransaction::class;

    public static string $model = SimpleFinTransaction::class;

    protected function getInfolistAttributes($record): array
    {
        return [
            'posted' => $record->posted->format('Y-m-d H:i:s'),
            'amount' => $record->amount,
            'description' => $record->description,
            'payee' => $record->payee,
            'account.name' => $record->account->name,
            'memo' => $record->memo,
            'transacted_at' => $record->transacted_at?->format('Y-m-d H:i:s'),
            'is_pending' => $record->is_pending,
        ];
    }
}
