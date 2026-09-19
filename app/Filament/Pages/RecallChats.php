<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Exceptions\Ai\AiUserMessage;
use App\Exceptions\Ai\UserSafeAiException;
use App\Services\Ai\AdminRecallAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class RecallChats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'AI';

    protected static ?string $navigationLabel = 'Recall chats';

    protected static ?string $title = 'Recall imported chats';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.recall-chats';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?int $sessionId = null;

    /** @var list<array{role: string, content: string, citations?: list<string>}> */
    public array $transcript = [];

    public function mount(): void
    {
        $this->form->fill([
            'question' => '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ask')
                    ->description('Answers use only the frontier_chats RAG corpus from imported conversations.')
                    ->schema([
                        Textarea::make('question')
                            ->label('Question')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transcript')
                    ->description('Import chats first, then ask questions like “What did we decide about X?”')
                    ->visible(fn (): bool => $this->transcript !== [])
                    ->schema([
                        Repeater::make('transcript')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                TextInput::make('role_label')
                                    ->hiddenLabel(),
                                TextEntry::make('content')
                                    ->hiddenLabel()
                                    ->markdown(),
                                Textarea::make('citations_line')
                                    ->label('Citations')
                                    ->rows(2)
                                    ->visible(fn (?string $state): bool => filled($state)),
                            ]),
                    ]),
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('ask')
                    ->footer([
                        Actions::make([
                            Action::make('ask')
                                ->label('Ask')
                                ->submit('ask'),
                            Action::make('resetChat')
                                ->label('Reset')
                                ->color('gray')
                                ->action('resetChat'),
                        ]),
                    ]),
            ]);
    }

    public function ask(AdminRecallAssistant $assistant): void
    {
        $data = $this->form->getState();
        $question = trim((string) ($data['question'] ?? ''));

        try {
            $result = $assistant->ask(
                question: $question,
                sessionId: $this->sessionId,
                userId: Auth::id(),
            );

            $this->sessionId = $result['session_id'];
            $this->transcript[] = [
                'role' => 'user',
                'role_label' => 'You',
                'content' => $question,
                'citations_line' => '',
            ];
            $this->transcript[] = [
                'role' => 'assistant',
                'role_label' => 'Recall',
                'content' => $result['answer'],
                'citations' => $result['citations'],
                'citations_line' => implode("\n", $result['citations']),
            ];
            $this->form->fill(['question' => '']);
        } catch (UserSafeAiException $e) {
            Notification::make()->title(AiUserMessage::from($e, $e->getMessage()))->danger()->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Recall failed')
                ->body(AiUserMessage::from($e, 'Something went wrong. Please try again.'))
                ->danger()
                ->send();
        }
    }

    public function resetChat(): void
    {
        $this->sessionId = null;
        $this->transcript = [];
        $this->form->fill(['question' => '']);
    }
}
