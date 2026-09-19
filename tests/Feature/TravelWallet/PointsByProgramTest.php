<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\LoyaltyKind;
use App\Enums\PointsProgram;
use App\Filament\Widgets\PointsByProgram;
use App\Models\Card;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Services\TravelWallet\PointsByProgramTotals;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PointsByProgramTest extends TestCase
{
    #[Test]
    public function groups_card_and_loyalty_currencies_separately(): void
    {
        $user = User::factory()->create();
        Card::factory()->create([
            'user_id' => $user->id,
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 40000,
        ]);
        $hyatt = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'name' => 'World of Hyatt',
            'code' => 'hyatt',
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $user->id,
            'loyalty_program_id' => $hyatt->id,
            'points_balance' => 15000,
        ]);

        $rows = collect(app(PointsByProgramTotals::class)->forHousehold());

        $ur = $rows->firstWhere('key', PointsProgram::ChaseUltimateRewards);
        $hyattRow = $rows->firstWhere('key', 'loyalty:hyatt');

        $this->assertCount(2, $rows);
        $this->assertSame(40000, $ur['cards']);
        $this->assertSame(0, $ur['loyalty']);
        $this->assertSame(40000, $ur['total']);
        $this->assertSame(0, $hyattRow['cards']);
        $this->assertSame(15000, $hyattRow['loyalty']);
        $this->assertSame(15000, $hyattRow['total']);
        $this->assertSame('World of Hyatt', $hyattRow['label']);
    }

    #[Test]
    public function pools_avios_family_memberships_with_avios_cards(): void
    {
        $user = User::factory()->create();
        Card::factory()->create([
            'user_id' => $user->id,
            'points_program' => PointsProgram::Avios,
            'points_balance' => 20000,
        ]);
        $ba = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'British Airways Executive Club',
            'code' => 'ba',
        ]);
        $qatar = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Qatar Privilege Club',
            'code' => 'qatar',
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $user->id,
            'loyalty_program_id' => $ba->id,
            'points_balance' => 10000,
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $user->id,
            'loyalty_program_id' => $qatar->id,
            'points_balance' => 5000,
        ]);

        $rows = app(PointsByProgramTotals::class)->forHousehold();

        $this->assertCount(1, $rows);
        $this->assertSame(PointsProgram::Avios, $rows[0]['key']);
        $this->assertSame(20000, $rows[0]['cards']);
        $this->assertSame(15000, $rows[0]['loyalty']);
        $this->assertSame(35000, $rows[0]['total']);
    }

    #[Test]
    public function amy_filter_hides_eriks_balances(): void
    {
        [$erik, $amy] = $this->householdUsers();
        Card::factory()->create([
            'user_id' => $erik->id,
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 90000,
        ]);
        Card::factory()->create([
            'user_id' => $amy->id,
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 10000,
        ]);

        $totals = app(PointsByProgramTotals::class);

        $this->assertSame(['total' => 'Total', 'erik' => 'Erik', 'amy' => 'Amy'], $totals->householdOptions());
        $this->assertSame(100000, $totals->forHousehold('total')[0]['total']);
        $this->assertSame(90000, $totals->forHousehold('erik')[0]['total']);
        $this->assertSame(10000, $totals->forHousehold('amy')[0]['total']);
    }

    #[Test]
    public function omits_zero_and_unknown_programs(): void
    {
        Card::factory()->create([
            'points_program' => PointsProgram::Unknown,
            'points_balance' => 5000,
        ]);
        Card::factory()->create([
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 0,
        ]);
        $hyatt = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'code' => 'hyatt',
        ]);
        LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $hyatt->id,
            'points_balance' => 0,
        ]);

        $this->assertSame([], app(PointsByProgramTotals::class)->forHousehold());
    }

    #[Test]
    public function widget_shows_source_split_and_household_filter(): void
    {
        $this->actingAs(User::factory()->create());
        [$erik, $amy] = $this->householdUsers();
        Card::factory()->create([
            'user_id' => $erik->id,
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 90000,
        ]);
        $hyatt = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'name' => 'World of Hyatt',
            'code' => 'hyatt',
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $amy->id,
            'loyalty_program_id' => $hyatt->id,
            'points_balance' => 15000,
        ]);

        Livewire::test(PointsByProgram::class)
            ->assertSee('90,000')
            ->assertSee('Cards 90,000')
            ->assertSee('World of Hyatt')
            ->assertSee('Loyalty 15,000')
            ->set('household', 'amy')
            ->assertDontSee('Cards 90,000')
            ->assertSee('World of Hyatt')
            ->assertSee('Loyalty 15,000');
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function householdUsers(): array
    {
        return [
            User::query()->firstOrCreate(
                ['email' => 'erik@erikgratz.com'],
                ['name' => 'Erik', 'password' => 'password'],
            ),
            User::query()->firstOrCreate(
                ['email' => 'hudgins.a8@gmail.com'],
                ['name' => 'Amy', 'password' => 'password'],
            ),
        ];
    }
}
