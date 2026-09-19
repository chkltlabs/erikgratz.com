<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Enums\BookingChannel;
use App\Enums\ResetPeriod;
use App\Filament\Actions\CreditBenefitPeriodAction;
use App\Filament\Actions\IgnoreBenefitBulkAction;
use App\Filament\Actions\UseBenefitFullyAction;
use App\Filament\Actions\UseBenefitFullyBulkAction;
use App\Filament\Actions\UseBenefitPartiallyAction;
use App\Filament\Forms\BookingPerkForm;
use App\Filament\Tables\HidesIgnoredBenefits;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitRefresher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BenefitsRelationManager extends RelationManager
{
    protected static string $relationship = 'benefits';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(4)->schema([
                    TextInput::make('benefit')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_useable')
                        ->default(true),
                    Select::make('tracking_mode')
                        ->options(BenefitTrackingMode::asSelectArray())
                        ->default(BenefitTrackingMode::Track)
                        ->live()
                        ->required(),
                    TextInput::make('auto_assume_amount')
                        ->numeric()
                        ->label('Assume each period')
                        ->helperText('Empty = full allotment. Only for Auto.')
                        ->visible(fn (Get $get): bool => (string) $get('tracking_mode') === BenefitTrackingMode::Auto),
                ]),
                Grid::make(1)->schema([
                    Textarea::make('description')
                        ->helperText('Human notes only; ranking does not parse this. See docs/travel-wallet-benefits.md.')
                        ->maxLength(2048),
                ]),
                Grid::make(3)->schema([
                    Select::make('value_kind')
                        ->options(BenefitValueKind::asSelectArray())
                        ->default(BenefitValueKind::Currency)
                        ->required(),
                    TextInput::make('value')
                        ->numeric()
                        ->label('Value ($)'),
                    TextInput::make('quantity_total')
                        ->numeric()
                        ->label('Quantity')
                        ->helperText('Max redemptions in the current window. Leave empty if the dollar pool can land on one booking.'),
                ]),
                Grid::make(3)->schema([
                    Select::make('reset_period')
                        ->options(ResetPeriod::asSelectArray()),
                    Select::make('reset_anchor')
                        ->options(BenefitResetAnchor::asSelectArray())
                        ->default(BenefitResetAnchor::Calendar),
                    DatePicker::make('custom_reset_on'),
                ]),
                Grid::make(3)->schema([
                    Select::make('applies_to')
                        ->options(BenefitAppliesTo::asSelectArray())
                        ->default(BenefitAppliesTo::Other),
                    Select::make('required_channel')
                        ->options(BookingChannel::asSelectArray())
                        ->nullable(),
                    TextInput::make('location_country')
                        ->maxLength(64),
                    TextInput::make('location_city')
                        ->maxLength(128),
                ]),
                Grid::make(2)->schema([
                    TagsInput::make('allowed_vendors')
                        ->helperText('Leave empty for any vendor. Lowercase brand needles; see docs/travel-wallet-benefits.md.')
                        ->placeholder('british airways'),
                    Toggle::make('award_only')
                        ->default(false)
                        ->helperText('Award taxes/fees only — omitted from cash ranks. See docs/travel-wallet-benefits.md.'),
                ]),
                Grid::make(5)->schema([
                    TextInput::make('max_apply_per_use.default')
                        ->numeric()
                        ->prefix('$')
                        ->label('Default cap')
                        ->helperText('Per-redemption dollar cap. Cabin keys override this. See docs/travel-wallet-benefits.md.'),
                    TextInput::make('max_apply_per_use.economy')
                        ->numeric()
                        ->prefix('$')
                        ->label('Economy'),
                    TextInput::make('max_apply_per_use.premium_economy')
                        ->numeric()
                        ->prefix('$')
                        ->label('Premium economy'),
                    TextInput::make('max_apply_per_use.business')
                        ->numeric()
                        ->prefix('$')
                        ->label('Business'),
                    TextInput::make('max_apply_per_use.first')
                        ->numeric()
                        ->prefix('$')
                        ->label('First'),
                ])->columnSpanFull(),
                Repeater::make('perks')
                    ->relationship()
                    ->schema(BookingPerkForm::components())
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->addActionLabel('Add perk'),
            ]);
    }

    public function table(Table $table): Table
    {
        return HidesIgnoredBenefits::configure($table)
            ->recordTitleAttribute('benefit')
            ->columns([
                TextColumn::make('benefit')
                    ->tooltip(fn (Model $record): ?string => $record->description),
                TextColumn::make('tracking_mode')
                    ->badge()
                    ->color(fn (CardBenefit $record): string => $record->tracking_mode->is(BenefitTrackingMode::Ignore)
                        ? 'gray'
                        : 'primary'),
                ToggleColumn::make('is_used')
                    ->disabled(fn (?Model $record) => ! $record?->is_useable),
                TextColumn::make('remaining')
                    ->state(fn (CardBenefit $record): string => $record->formattedRemaining()),
                TextColumn::make('next_refresh_at')
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('value')->money(),
                TextColumn::make('reset_period'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function (CardBenefit $record): void {
                        app(BenefitRefresher::class)->refreshOne($record);
                        $this->resetTable();
                    }),
            ])
            ->recordActions([
                UseBenefitFullyAction::make(),
                UseBenefitPartiallyAction::make(),
                CreditBenefitPeriodAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    UseBenefitFullyBulkAction::make(),
                    IgnoreBenefitBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
