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
    }
}
