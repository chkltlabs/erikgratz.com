<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PointsProgram;
use App\Filament\Resources\TransferRouteResource;
use App\Filament\Resources\TransferRouteResource\Pages\CreateTransferRoute;
use App\Filament\Resources\TransferRouteResource\Pages\EditTransferRoute;
use App\Filament\Resources\TransferRouteResource\Pages\ListTransferRoutes;
use App\Models\LoyaltyProgram;
use App\Models\TransferRoute;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransferRouteResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function list_page_shows_routes(): void
    {
        $route = TransferRoute::factory()->create([
            'from_program' => PointsProgram::ChaseUltimateRewards,
            'base_ratio' => 1,
        ]);

        $this->get(TransferRouteResource::getUrl('index'))->assertSuccessful();

        Livewire::test(ListTransferRoutes::class)
            ->assertCanSeeTableRecords([$route])
            ->assertSee($route->program->name);
    }

    #[Test]
    public function create_page_stores_a_route(): void
    {
        $program = LoyaltyProgram::factory()->create(['name' => 'United MileagePlus']);

        $this->get(TransferRouteResource::getUrl('create'))->assertSuccessful();

        Livewire::test(CreateTransferRoute::class)
            ->fillForm([
                'from_program' => PointsProgram::ChaseUltimateRewards,
                'loyalty_program_id' => $program->id,
                'base_ratio' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transfer_routes', [
            'loyalty_program_id' => $program->id,
            'base_ratio' => 1,
            'is_active' => 1,
        ]);
    }

    #[Test]
    public function edit_page_can_disable_a_route(): void
    {
        $route = TransferRoute::factory()->create([
            'is_active' => true,
            'base_ratio' => 1,
        ]);

        $this->get(TransferRouteResource::getUrl('edit', ['record' => $route]))->assertSuccessful();

        Livewire::test(EditTransferRoute::class, [
            'record' => $route->getKey(),
        ])
            ->fillForm([
                'from_program' => $route->from_program->value,
                'loyalty_program_id' => $route->loyalty_program_id,
                'base_ratio' => 2,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(2.0, (float) $route->fresh()->base_ratio);
        $this->assertFalse((bool) $route->fresh()->is_active);
    }
}
