<?php

namespace App\Filament\Pages;

use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Exceptions\Ai\AiUserMessage;
use App\Exceptions\Ai\UserSafeAiException;
use App\Models\Activity;
use App\Services\TravelWallet\BookingAdvisor;
use App\Services\TravelWallet\BookingCombo;
use App\Services\TravelWallet\BookingExplainer;
use App\Services\TravelWallet\BookingRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class BookTravel extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Travel wallet';

    protected static ?string $navigationLabel = 'Book travel';

    protected static ?string $title = 'Book travel';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.book-travel';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $ranking = [];

    /**
     * @var list<array{id: int, label: string}>
     */
    public array $loyaltyOptions = [];

    public bool $showLoyaltySelect = false;

    public ?int $selectedLoyaltyMembershipId = null;

    public ?string $explanation = null;

    public function mount(): void
    {
        $this->form->fill([
            'category' => BookingCategory::Flight,
            'cabin' => BookingCabin::Economy,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Trip')
                ->schema([
                    Select::make('category')
                        ->options(BookingCategory::asSelectArray())
                        ->required(),
                    Select::make('activity_id')
                        ->label('Activity')
                        ->options(fn () => Activity::query()->orderByDesc('start_date')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    TextInput::make('vendor')
                        ->placeholder('United, Hyatt, Hertz…'),
                    TextInput::make('destination'),
                    TextInput::make('cash_price')
                        ->numeric()
                        ->prefix('$'),
                    Select::make('cabin')
                        ->options(BookingCabin::asSelectArray())
                        ->default(BookingCabin::Economy)
                        ->required(),
                    Repeater::make('award_quotes')
                        ->schema([
                            TextInput::make('points_program')->required(),
                            TextInput::make('program_code')->label('Partner code'),
                            TextInput::make('points')->numeric()->required(),
                            TextInput::make('cash')->numeric()->prefix('$'),
                        ])
                        ->defaultItems(0)
                        ->columnSpanFull()
                        ->addActionLabel('Add award quote'),
                ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('rank')
                ->footer([
                    Actions::make([
                        Action::make('rank')
                            ->label('Rank options')
                            ->submit('rank'),
                        Action::make('explain')
                            ->label('Explain ranking')
                            ->color('gray')
                            ->disabled(fn (): bool => $this->ranking === [])
                            ->action('explain'),
                    ]),
                ]),
            Section::make('Loyalty program')
                ->description('Pick a program to attach its perks to every combo.')
                ->visible(fn (): bool => $this->showLoyaltySelect && $this->loyaltyOptions !== [])
                ->schema([
                    Select::make('selectedLoyaltyMembershipId')
                        ->hiddenLabel()
                        ->options(fn (): array => collect($this->loyaltyOptions)
                            ->mapWithKeys(fn (array $option): array => [$option['id'] => $option['label']])
                            ->all())
                        ->placeholder('None — no loyalty perks')
                        ->nullable()
                        ->live(),
                ]),
            Section::make('Top combos')
                ->visible(fn (): bool => $this->ranking !== [])
                ->schema([
                    RepeatableEntry::make('ranking')
                        ->hiddenLabel()
                        ->contained()
                        ->state(fn (): array => $this->ranking)
                        ->columns(4)
                        ->schema([
                            TextEntry::make('headline')
                                ->hiddenLabel()
                                ->weight(FontWeight::Bold)
                                ->columnSpan(2),
                            TextEntry::make('dollar_value')
                                ->label('Value')
                                ->money('USD')
                                ->badge(),
                            TextEntry::make('channel_label')
                                ->label('Channel')
                                ->badge(),
                            TextEntry::make('card_name')
                                ->label('Card')
                                ->placeholder('—'),
                            TextEntry::make('credits_summary')
                                ->label('Credits')
                                ->placeholder('—'),
                            TextEntry::make('earn_line')
                                ->label('Earn')
                                ->placeholder('—'),
                            TextEntry::make('credit_perks')
                                ->label('Credit / card perks')
                                ->formatStateUsing(fn (mixed $state): ?string => $this->formatPerkLine($state))
                                ->listWithLineBreaks()
                                ->bulleted()
                                ->columnSpanFull()
                                ->visible(fn (TextEntry $entry): bool => filled($entry->getState())),
                            TextEntry::make('loyalty_perks')
                                ->label('Loyalty perks')
                                ->formatStateUsing(fn (mixed $state): ?string => $this->formatPerkLine($state))
                                ->listWithLineBreaks()
                                ->bulleted()
                                ->columnSpanFull()
                                ->visible(fn (TextEntry $entry): bool => filled($entry->getState())),
                        ]),
                ]),
            Section::make('Explanation')
                ->visible(fn (): bool => filled($this->explanation))
                ->schema([
                    Textarea::make('explanation')
                        ->hiddenLabel()
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(8),
                ]),
        ]);
    }

    public function updatedSelectedLoyaltyMembershipId(mixed $value): void
    {
        $this->selectedLoyaltyMembershipId = $value === '' || $value === null ? null : (int) $value;
        $this->rank(app(BookingAdvisor::class), app(BookingExplainer::class));
    }

    public function rank(BookingAdvisor $advisor, BookingExplainer $explainer): void
    {
        $base = $this->toRequest(includeLoyalty: false);
        $this->loyaltyOptions = $advisor->loyaltyOptions($base);
        $this->showLoyaltySelect = $advisor->needsLoyaltySelect($base);
        if (! $this->showLoyaltySelect) {
            $this->selectedLoyaltyMembershipId = $advisor->resolveLoyalty($base)?->id;
        }

        $request = $this->toRequest();
        $combos = $advisor->recommend($request);
        $this->ranking = array_map(fn (BookingCombo $row): array => $this->comboDisplay($row), $combos);
        $this->explanation = null;
        $explainer->persist($request, $combos);
    }

    public function explain(BookingAdvisor $advisor, BookingExplainer $explainer): void
    {
        try {
            $request = $this->toRequest();
            $combos = $advisor->recommend($request);
            $this->ranking = array_map(fn (BookingCombo $row): array => $this->comboDisplay($row), $combos);
            $this->explanation = $explainer->explain($request, $combos);
            $explainer->persist($request, $combos, $this->explanation);
        } catch (UserSafeAiException $e) {
            Notification::make()->title(AiUserMessage::from($e, $e->getMessage()))->danger()->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Explanation failed')
                ->body(AiUserMessage::from($e, 'Something went wrong. Please try again.'))
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function comboDisplay(BookingCombo $row): array
    {
        $data = $row->toArray();
        $data['credits_summary'] = collect($data['credits'] ?? [])
            ->map(fn (array $credit): string => $credit['name'].' ($'.number_format($credit['applied'], 0).')')
            ->implode(', ');
        $data['earn_line'] = $data['earn']['line'] ?? '';

        return $data;
    }

    private function formatPerkLine(mixed $state): ?string
    {
        if (! is_array($state)) {
            return filled($state) ? (string) $state : null;
        }

        $name = $state['name'] ?? null;
        if (! filled($name)) {
            return null;
        }

        if (! isset($state['decision_value'])) {
            return (string) $name;
        }

        return $name.' ($'.number_format((float) $state['decision_value'], 0).')';
    }

    private function toRequest(bool $includeLoyalty = true): BookingRequest
    {
        $state = $this->form->getState();

        return new BookingRequest(
            category: BookingCategory::fromValue($state['category'] ?? BookingCategory::Flight),
            activityId: isset($state['activity_id']) ? (int) $state['activity_id'] : null,
            vendor: $state['vendor'] ?? null,
            destination: $state['destination'] ?? null,
            cashPrice: isset($state['cash_price']) ? (float) $state['cash_price'] : null,
            awardQuotes: array_values($state['award_quotes'] ?? []),
            loyaltyMembershipId: $includeLoyalty ? $this->selectedLoyaltyMembershipId : null,
            cabin: BookingCabin::fromValue($state['cabin'] ?? BookingCabin::Economy),
        );
    }
}
