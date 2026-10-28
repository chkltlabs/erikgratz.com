<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Content\WorkHistory;

class WorkHistoryKnowledgePack
{
    public function __construct(
        protected WorkHistory $workHistory,
    ) {}

    public function render(): string
    {
        return $this->toMarkdown($this->payload());
    }

    /**
     * @return array{experience: list<array<string, mixed>>, portfolio: list<array<string, mixed>>, identity: list<string>}
     */
    public function payload(): array
    {
        return [
            'experience' => $this->workHistory->roles(),
            'portfolio' => array_map(fn (array $item): array => [
                'title' => $item['title'] ?? '',
                'text' => $item['text'] ?? '',
                'link' => $item['link'] ?? null,
                'links' => $this->projectLinks($item),
            ], config('portfolio', [])),
            'identity' => [
                'Erik Gratz',
                'Senior Software Engineer',
                'Laravel / PHP backend focus',
            ],
        ];
    }

    /**
     * @param  array{experience: list<array<string, mixed>>, portfolio: list<array<string, mixed>>, identity: list<string>}  $payload
     */
    protected function toMarkdown(array $payload): string
    {
        $lines = [
            '# Work history knowledge pack',
            '',
            '## Identity',
            ...array_map(static fn (string $line): string => "- {$line}", $payload['identity']),
            '',
            '## Professional experience',
        ];

        foreach ($payload['experience'] as $role) {
            $lines[] = '';
            $lines[] = '### '.($role['title'] ?? 'Role').' @ '.($role['company'] ?? 'Company');
            $lines[] = '- Location: '.($role['location'] ?? 'n/a');
            $lines[] = '- Timeframe: '.($role['timeframe'] ?? 'n/a');
            $lines[] = '- Highlights:';
            foreach ($role['bullets'] ?? [] as $bullet) {
                $lines[] = '  - '.$bullet;
            }
            $tech = $role['technologies'] ?? [];
            if ($tech !== []) {
                $lines[] = '- Technologies: '.implode(', ', $tech);
            }
        }

        $lines[] = '';
        $lines[] = '## Portfolio / side projects';

        foreach ($payload['portfolio'] as $project) {
            $lines[] = '';
            $lines[] = '### '.($project['title'] ?: 'Project');
            $lines[] = $project['text'] ?? '';
            foreach ($project['links'] ?? [] as $link) {
                $lines[] = 'Link: '.$link;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    protected function projectLinks(array $item): array
    {
        $links = [];

        if (is_string($item['link'] ?? null) && $item['link'] !== '') {
            $links[] = $item['link'];
        }

        $extraLinks = $item['extraLinks'] ?? [];

        if (! is_array($extraLinks)) {
            return $links;
        }

        foreach ($extraLinks as $extraLink) {
            if (! is_array($extraLink)) {
                continue;
            }

            $href = $extraLink['href'] ?? null;

            if (is_string($href) && $href !== '') {
                $links[] = $href;
            }
        }

        return $links;
    }

    public function systemInstructions(): string
    {
        return <<<'PROMPT'
You are a career assistant for Erik Gratz on his personal website.
Answer ONLY using the provided work history knowledge pack.
If a job description is provided, assess fit with concrete evidence from the pack (skills, outcomes, stack). Be honest about gaps.
Prefer 3–5 short evidence lines when assessing fit so answers read clearly on the page.
If the question is unrelated to Erik's work history, career fit, or skills, refuse briefly.
Do not invent employers, dates, or achievements. Do not discuss admin, finance, or private site data.
PROMPT;
    }
}
