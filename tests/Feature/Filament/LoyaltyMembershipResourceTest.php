<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BenefitAppliesTo;
use App\Enums\LoyaltyKind;
use App\Filament\Resources\LoyaltyMembershipResource;
use App\Filament\Resources\LoyaltyMembershipResource\Pages\CreateLoyaltyMembership;
use App\Filament\Resources\LoyaltyMembershipResource\Pages\EditLoyaltyMembership;
use App\Filament\Resources\LoyaltyMembershipResource\Pages\ListLoyaltyMemberships;
use App\Filament\Resources\LoyaltyMembershipResource\RelationManagers\InheritedPerksRelationManager;
use App\Filament\Resources\LoyaltyMembershipResource\RelationManagers\PerksRelationManager;
use App\Models\BookingPerk;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Filament\Schemas\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoyaltyMembershipResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function lists_household_memberships_under_member(): void
    {
        $amy = User::factory()->create(['name' => 'Amy']);
        $erik = User::factory()->create(['name' => 'Erik']);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Air Canada Aeroplan',
            'code' => 'aeroplan',
        ]);
        $amyMembership = LoyaltyMembership::factory()->create([
            'user_id' => $amy->id,
            'loyalty_program_id' => $program->id,
            'tier' => '25K',
            'loyalty_number' => '123456789',
        ]);
        $erikMembership = LoyaltyMembership::factory()->create([
            'user_id' => $erik->id,
            'loyalty_program_id' => $program->id,
            'tier' => '50K',
            'loyalty_number' => '987654321',
        ]);

        Livewire::test(ListLoyaltyMemberships::class)
            ->assertCanSeeTableRecords([$amyMembership, $erikMembership])
            ->assertSee('Member')
            ->assertSee('Amy')
            ->assertSee('Erik')
            ->assertSee('123456789')
            ->assertSee('987654321')
            ->assertSee('fi-copyable', escape: false);
    }

    #[Test]
    public function points_balance_can_be_updated_from_the_table(): void
    {
        $membership = LoyaltyMembership::factory()->create([
            'points_balance' => 10000,
        ]);

        Livewire::test(ListLoyaltyMemberships::class)
            ->call('updateTableColumnState', 'points_balance', (string) $membership->getKey(), '10000 + 2500');

        $this->assertSame(12500, $membership->fresh()->points_balance);
    }

    #[Test]
    public function points_column_drops_the_filament_min_width_floor(): void
    {
        LoyaltyMembership::factory()->create();

        Livewire::test(ListLoyaltyMemberships::class)
            ->assertSee('min-width: 6rem', false);
    }

    #[Test]
    public function create_form_has_member_and_loyalty_number_fields(): void
    {
        Livewire::test(CreateLoyaltyMembership::class)
            ->assertFormFieldExists('user_id')
            ->assertFormFieldExists('loyalty_number')
            ->assertSee('Member')
            ->assertSee('Loyalty number');
    }

    #[Test]
    public function edit_page_can_update_loyalty_number(): void
    {
        $membership = LoyaltyMembership::factory()->create([
            'loyalty_number' => '111',
            'tier' => '25K',
        ]);

        $this->get(LoyaltyMembershipResource::getUrl('edit', [
            'record' => $membership,
        ]))->assertSuccessful();

        Livewire::test(EditLoyaltyMembership::class, [
            'record' => $membership->getKey(),
        ])
            ->fillForm([
                'user_id' => $membership->user_id,
                'loyalty_program_id' => $membership->loyalty_program_id,
                'tier' => '50K',
                'loyalty_number' => '999888777',
                'points_balance' => $membership->points_balance,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('999888777', $membership->fresh()->loyalty_number);
        $this->assertSame('50K', $membership->fresh()->tier);
    }

    #[Test]
    public function membership_perks_can_be_created(): void
    {
        $membership = LoyaltyMembership::factory()->create();

        Livewire::test(PerksRelationManager::class, [
            'ownerRecord' => $membership,
            'pageClass' => EditLoyaltyMembership::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'Companion certificate',
                'decision_value' => 200,
                'applies_to' => BenefitAppliesTo::Flight,
                'award_only' => false,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(
            BookingPerk::query()
                ->where('loyalty_membership_id', $membership->id)
                ->where('name', 'Companion certificate')
                ->exists()
        );
    }

    #[Test]
    public function inherited_perks_are_read_only_and_visible(): void
    {
        $membership = LoyaltyMembership::factory()->create();
        $perk = BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_program_id' => $membership->loyalty_program_id,
            'name' => 'Lounge access',
            'min_tier' => 'Gold',
        ]);

        $component = Livewire::test(InheritedPerksRelationManager::class, [
            'ownerRecord' => $membership,
            'pageClass' => EditLoyaltyMembership::class,
        ])
            ->assertCanSeeTableRecords([$perk])
            ->assertSee('Lounge access');

        $this->assertTrue($component->instance()->isReadOnly());
        $this->assertNotEmpty(
            $component->instance()->form(Schema::make($component->instance()))->getComponents()
        );
    }
}
