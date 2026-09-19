<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Ai\Agents\PromoExtractionAgent;
use App\Enums\PointsProgram;
use App\Enums\PromoStatus;
use App\Filament\Resources\PromoSourceItemResource;
use App\Filament\Resources\PromoSourceItemResource\Pages\EditPromoSourceItem;
use App\Filament\Resources\PromoSourceItemResource\Pages\ListPromoSourceItems;
use App\Jobs\IngestTravelPromos;
use App\Models\LoyaltyProgram;
use App\Models\PromoSourceItem;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromoSourceItemResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function list_page_shows_pending_review_items(): void
    {
        $pending = PromoSourceItem::factory()->create([
            'title' => '30% UR to United',
            'status' => PromoStatus::PendingReview,
        ]);
        $rejected = PromoSourceItem::factory()->create([
            'title' => 'Old rejected deal',
            'status' => PromoStatus::Rejected,
        ]);

        Livewire::test(ListPromoSourceItems::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$rejected]);
    }

    #[Test]
    public function approve_action_activates_a_transfer_bonus(): void
    {
        $program = LoyaltyProgram::factory()->create([
            'code' => 'united',
            'name' => 'United MileagePlus',
        ]);
        TransferRoute::factory()->create([
            'from_program' => PointsProgram::ChaseUltimateRewards,
            'loyalty_program_id' => $program->id,
        ]);
        $item = PromoSourceItem::factory()->create([
            'title' => '40% UR to United',
            'facts' => [
                'type' => 'transfer_bonus',
                'from_program' => PointsProgram::ChaseUltimateRewards,
                'to_program_code' => 'united',
                'bonus_percent' => 40,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
            ],
        ]);

        Livewire::test(ListPromoSourceItems::class)
            ->callTableAction('approve', $item)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Promo approved');

        $this->assertTrue($item->fresh()->status->is(PromoStatus::Active));
        $this->assertTrue(TransferBonus::query()->where('bonus_percent', 40)->exists());
    }

    #[Test]
    public function reject_action_marks_the_item_rejected(): void
    {
        $item = PromoSourceItem::factory()->create([
            'title' => 'Skip this one',
        ]);

        Livewire::test(ListPromoSourceItems::class)
            ->callTableAction('reject', $item)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Promo rejected');

        $this->assertTrue($item->fresh()->status->is(PromoStatus::Rejected));
    }

    #[Test]
    public function fetch_action_queues_ingest(): void
    {
        Bus::fake([IngestTravelPromos::class]);

        Livewire::test(ListPromoSourceItems::class)
            ->callAction('fetch')
            ->assertNotified('Promo ingest queued');

        Bus::assertDispatched(IngestTravelPromos::class);
    }

    #[Test]
    public function paste_action_extracts_plaintext(): void
    {
        PromoExtractionAgent::fake(fn (): string => '{"items":[]}');

        Livewire::test(ListPromoSourceItems::class)
            ->callAction('paste', [
                'text' => 'Chase is offering 30% more points to United this month.',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('promo_source_items', [
            'source' => 'paste',
        ]);
    }

    #[Test]
    public function edit_page_can_update_title_and_status(): void
    {
        $item = PromoSourceItem::factory()->create([
            'title' => 'Original title',
            'status' => PromoStatus::PendingReview,
        ]);

        $this->get(PromoSourceItemResource::getUrl('edit', ['record' => $item]))->assertSuccessful();

        Livewire::test(EditPromoSourceItem::class, [
            'record' => $item->getKey(),
        ])
            ->fillForm([
                'title' => 'Updated promo title',
                'status' => PromoStatus::Active,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated promo title', $item->fresh()->title);
        $this->assertTrue($item->fresh()->status->is(PromoStatus::Active));
    }
}
