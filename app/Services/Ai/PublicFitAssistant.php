<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\PublicFitAgent;
use App\Content\WorkHistory;
use App\Enums\AiMessageRole;
use App\Enums\AiSurface;
use App\Exceptions\Ai\UserSafeAiException;
use App\Models\AiSession;
use App\Models\AiSessionMessage;
use Illuminate\Support\Facades\RateLimiter;

class PublicFitAssistant
{
    public function __construct(
        protected PublicFitAgent $agent,
        protected WorkHistory $workHistory,
    ) {}

    /**
     * Single-shot assessment. Prior turns are never sent to the model.
     *
     * @return array{answer: string, session_token: string, cited_roles: list<string>}
     */
    public function ask(
        string $question,
        ?string $jobDescription = null,
        ?string $sessionToken = null,
        ?string $ip = null,
    ): array {
        $question = trim($question);
        $jobDescription = $jobDescription !== null ? trim($jobDescription) : null;
        if ($jobDescription === '') {
            $jobDescription = null;
        }

        $maxQ = (int) config('chatbots.public.max_question_length', 2000);
        $maxJd = (int) config('chatbots.public.max_job_description_length', 8000);

        if ($question === '') {
            throw new UserSafeAiException('Question is required.');
        }
        if (strlen($question) > $maxQ) {
            throw new UserSafeAiException("Question must be under {$maxQ} characters.");
        }
        if ($jobDescription !== null && strlen($jobDescription) > $maxJd) {
            throw new UserSafeAiException("Job description must be under {$maxJd} characters.");
        }

        $visitorHash = $ip ? hash('sha256', $ip) : 'anon';
        $rateKey = 'ai-public:'.$visitorHash;
        $limit = (int) config('chatbots.public.rate_limit_per_minute', 10);
        if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
            throw new UserSafeAiException('Too many requests. Please wait a minute and try again.');
        }
        RateLimiter::hit($rateKey, 60);

        [$session, $plainToken] = $this->resolvePublicSession($sessionToken, $visitorHash);

        $userContent = $question;
        if ($jobDescription !== null) {
            $userContent = "Job description:\n{$jobDescription}\n\nQuestion:\n{$question}";
        }

        $answer = $this->agent->ask($userContent);

        $sequence = (int) $session->messages()->max('sequence');
        AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::User,
            'content' => $userContent,
            'sequence' => $sequence + 1,
        ]);
        AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::Assistant,
            'content' => $answer,
            'sequence' => $sequence + 2,
        ]);

        return [
            'answer' => $answer,
            'session_token' => $plainToken,
            'cited_roles' => $this->citeRoles($answer),
        ];
    }

    /**
     * @return array{0: AiSession, 1: string}
     */
    protected function resolvePublicSession(?string $sessionToken, string $visitorHash): array
    {
        $ttlHours = (int) config('chatbots.public.session_ttl_hours', 24);

        if ($sessionToken !== null && $sessionToken !== '') {
            $hash = hash('sha256', $sessionToken);
            $existing = AiSession::query()
                ->where('surface', AiSurface::PublicSite)
                ->where('public_handle_hash', $hash)
                ->where('ip_hash', $visitorHash)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existing) {
                return [$existing, $sessionToken];
            }
        }

        $plainToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $session = AiSession::query()->create([
            'surface' => AiSurface::PublicSite,
            'user_id' => null,
            'ip_hash' => $visitorHash,
            'public_handle_hash' => hash('sha256', $plainToken),
            'metadata' => [],
            'expires_at' => now()->addHours($ttlHours),
        ]);

        return [$session, $plainToken];
    }

    /**
     * @return list<string>
     */
    protected function citeRoles(string $answer): array
    {
        $cited = [];
        foreach ($this->workHistory->companyNames() as $company) {
            if (preg_match('/\b'.preg_quote($company, '/').'\b/i', $answer) === 1) {
                $cited[] = $company;
            }
        }

        return $cited;
    }
}
