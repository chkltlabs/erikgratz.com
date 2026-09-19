<?php

use App\Exceptions\Ai\AiUserMessage;
use App\Exceptions\Ai\UserSafeAiException;
use App\Services\Ai\PublicFitAssistant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('livewire.components.layouts.app', ['pageTitle' => 'Fit'])] class extends Component
{
    public string $question = '';

    public string $jobDescription = '';

    public ?string $sessionToken = null;

    public ?string $answer = null;

    public ?string $displayedQuestion = null;

    /** @var list<string> */
    public array $citedRoles = [];

    public ?string $error = null;

    public function ask(PublicFitAssistant $assistant): void
    {
        $this->error = null;

        try {
            $result = $assistant->ask(
                question: $this->question,
                jobDescription: $this->jobDescription !== '' ? $this->jobDescription : null,
                sessionToken: $this->sessionToken,
                ip: request()->ip(),
            );

            $this->sessionToken = $result['session_token'];
            $this->displayedQuestion = $this->question;
            $this->answer = $result['answer'];
            $this->citedRoles = $result['cited_roles'];
            $this->question = '';
        } catch (UserSafeAiException $e) {
            $this->error = AiUserMessage::from($e, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->error = AiUserMessage::from(
                $e,
                'Something went wrong answering that. Please try again.',
            );
        }
    }

    public function resetChat(): void
    {
        $this->sessionToken = null;
        $this->answer = null;
        $this->displayedQuestion = null;
        $this->citedRoles = [];
        $this->question = '';
        $this->jobDescription = '';
        $this->error = null;
    }
};
?>
<div class="w-full p-12">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-purple-600 mb-4">Fit</h1>
        <p class="text-2xl font-light text-gray-400">
            Ask about Erik&rsquo;s work history, or paste a job description to check fit.
            Answers use only public experience and portfolio content from this site.
        </p>
    </div>

    <div class="bg-gray-800 rounded-lg p-4 md:p-6 mb-6 shadow-lg space-y-4">
        @if ($error)
            <div class="rounded-lg border border-red-400/40 bg-red-900/40 text-red-100 text-sm p-3" role="alert">
                {{ $error }}
            </div>
        @endif

        <form wire:submit="ask" class="space-y-4">
            <div>
                <label for="jobDescription" class="block text-sm font-semibold text-gray-400 mb-2 uppercase tracking-wide">
                    Job description (optional)
                </label>
                <textarea
                    id="jobDescription"
                    wire:model="jobDescription"
                    rows="5"
                    class="w-full rounded-lg bg-gray-900 border border-gray-700 text-gray-200 placeholder-gray-500 py-2 px-3 text-sm focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600"
                    placeholder="Paste a job description to assess fit"
                ></textarea>
            </div>
            <div>
                <label for="question" class="block text-sm font-semibold text-gray-400 mb-2 uppercase tracking-wide">
                    Your question
                </label>
                <textarea
                    id="question"
                    wire:model="question"
                    rows="3"
                    required
                    class="w-full rounded-lg bg-gray-900 border border-gray-700 text-gray-200 placeholder-gray-500 py-2 px-3 text-sm focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600"
                    placeholder="How well do I fit this role?"
                ></textarea>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-7 py-3 md:px-9 md:py-4 font-medium md:font-semibold bg-gray-700 text-gray-50 rounded-full hover:bg-purple-600 hover:text-white transition ease-linear duration-500 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="ask">Ask</span>
                    <span wire:loading wire:target="ask">Working</span>
                </button>
                <button
                    type="button"
                    wire:click="resetChat"
                    class="px-7 py-3 md:px-9 md:py-4 font-medium border border-purple-600 text-purple-400 rounded-full hover:bg-purple-600 hover:text-white transition ease-linear duration-500"
                >
                    Clear
                </button>
            </div>
        </form>
    </div>

    @if ($answer)
        <div class="bg-gray-800 rounded-lg p-4 md:p-6 mb-6 shadow-lg" aria-live="polite">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-4">
                <h2 class="text-xl md:text-2xl font-bold text-purple-400">
                    {{ $displayedQuestion }}
                </h2>
            </div>

            <ul class="space-y-2 text-gray-300 text-sm md:text-base leading-relaxed mb-4">
                @foreach (preg_split("/\r\n|\n|\r/", trim($answer)) ?: [] as $line)
                    @if (trim($line) !== '')
                        <li class="flex items-start">
                            <span class="text-purple-400 mr-2 mt-1 flex-shrink-0">•</span>
                            <span class="whitespace-pre-wrap">{{ ltrim($line, "•-* \t") }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>

            @if (count($citedRoles) > 0)
                <div class="border-t border-gray-700 pt-4">
                    <p class="text-sm font-semibold text-gray-400 mb-2 uppercase tracking-wide">Roles mentioned</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($citedRoles as $role)
                            <span class="bg-purple-900/30 text-purple-300 px-2 py-1 rounded-md text-xs md:text-sm font-medium border border-purple-700">
                                {{ $role }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
