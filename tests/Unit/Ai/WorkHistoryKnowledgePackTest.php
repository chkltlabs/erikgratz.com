<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Services\Ai\WorkHistoryKnowledgePack;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkHistoryKnowledgePackTest extends TestCase
{
    #[Test]
    public function it_renders_experience_and_portfolio_from_site_sources(): void
    {
        $markdown = app(WorkHistoryKnowledgePack::class)->render();

        $this->assertStringContainsString('Pocketnest', $markdown);
        $this->assertStringContainsString('Professional experience', $markdown);
        $this->assertStringContainsString('Code Coverage Summary', $markdown);
        $this->assertStringContainsString('OpenBrain SaaS', $markdown);
        $this->assertStringContainsString('Laravel Porthole', $markdown);
        $this->assertStringContainsString('https://github.com/chkltlabs/laravel-porthole', $markdown);
        $this->assertStringContainsString('https://porthole.erikgratz.com', $markdown);
        $this->assertStringContainsString('https://openbrain.erikgratz.com', $markdown);
        $this->assertStringContainsString('Household card wallet', $markdown);
        $this->assertStringContainsString('Book Travel', $markdown);
        $this->assertStringContainsString('Net worth over time', $markdown);
    }

    #[Test]
    public function it_keeps_the_primary_link_and_skips_blank_extra_links(): void
    {
        config([
            'portfolio' => [
                [
                    'title' => 'Laravel Porthole',
                    'text' => 'Ops tools for agents.',
                    'link' => 'https://github.com/chkltlabs/laravel-porthole',
                    'extraLinks' => [
                        ['href' => 'https://porthole.erikgratz.com', 'text' => 'Visit Porthole'],
                        ['href' => '', 'text' => 'Skip'],
                        'nope',
                        ['text' => 'Missing href'],
                    ],
                ],
                [
                    'title' => 'Broken extras',
                    'text' => 'Primary link still ships.',
                    'link' => 'https://example.com/primary',
                    'extraLinks' => 'not-a-list',
                ],
                [
                    'title' => 'No link',
                    'text' => 'Just a note.',
                    'link' => '',
                ],
            ],
        ]);

        $markdown = app(WorkHistoryKnowledgePack::class)->render();

        $this->assertSame(3, substr_count($markdown, 'Link: '));
        $this->assertStringContainsString('Link: https://github.com/chkltlabs/laravel-porthole', $markdown);
        $this->assertStringContainsString('Link: https://porthole.erikgratz.com', $markdown);
        $this->assertStringContainsString('Link: https://example.com/primary', $markdown);
    }
}
