<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\BenefitUsageChart;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelFirstPaintTest extends TestCase
{
    #[Test]
    public function login_shell_is_styled_before_alpine_and_skips_page_specific_assets(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('.fi-dropdown-panel {', false);
        $response->assertSee('display: none;', false);
        $response->assertSee('opacity: 1;', false);
        $response->assertSee('translateX(0)', false);
        $response->assertSee('min-height: 18rem', false);
        $response->assertDontSee('filament-chart-plugins', false);
        $response->assertDontSee('activity-map.js', false);
        $response->assertDontSee('activity-map.css', false);
        $response->assertDontSee('_debugbar', false);
    }

    #[Test]
    public function dashboard_widgets_load_when_the_page_is_ready(): void
    {
        $this->assertSame('on-load', BenefitUsageChart::getDefaultProperties()['lazy']);
        $this->assertSame('on-load', AccountWidget::getDefaultProperties()['lazy']);

        $this->actingAs(User::factory()->create());

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertSee('x-init', false);
        $response->assertSee('__lazyLoad', false);
        $response->assertDontSee('x-intersect="$wire.__lazyLoad', false);
    }
}
