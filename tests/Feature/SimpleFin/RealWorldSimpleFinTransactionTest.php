<?php

namespace Tests\Feature\SimpleFin;

use App\Models\Activity;
use App\Models\Card;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use App\Models\User;
use App\Services\SimpleFin\SimpleFinCategorizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RealWorldSimpleFinTransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SimpleFinAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create User
        $this->user = User::factory()->create([
            'name' => 'Erik Test',
            'email' => 'erik_test_realworld@erikgratz.com',
        ]);

        // 2. Create Account
        $this->account = SimpleFinAccount::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'ERIK\'S CHECKING (90)',
        ]);

        // 3. Seed Real World Spends & Periodic Spends from Seeder Data
        // Based on ActivitySpendSeeder.php

        // Activity: Albania
        DB::table('activities')->insert([
            'id' => 46,
            'name' => 'Albania',
            'start_date' => '2026-02-01',
            'end_date' => '2026-05-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Spend: Sarande Apartment
        DB::table('spends')->insert([
            'id' => 122,
            'name' => 'Sarande Apartment',
            'activity_id' => 46,
            'is_income' => false,
            'type' => 'housing',
            'subtype' => 'housing_rent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Periodic Spends (Missing from seeder but referenced in payments)
        DB::table('periodic_spends')->insert([
            'id' => 7,
            'name' => 'YouTube Premium',
            'period' => 'monthly',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'is_income' => false,
            'type' => 'entertainment',
            'subtype' => 'entertainment_streaming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('periodic_spends')->insert([
            'id' => 6,
            'name' => 'Netflix',
            'period' => 'monthly',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'is_income' => false,
            'type' => 'entertainment',
            'subtype' => 'entertainment_streaming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Seed Payments (Unpaid, to test balance matching)
        // YouTube Payment (matching scratch.json amount 22.99)
        DB::table('payments')->insert([
            'spend_type' => 'periodic_spend',
            'spend_id' => 7,
            'amount' => 22.99,
            'is_paid' => false,
            'paid_on' => '2026-02-15', // Same month as transaction
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sarande Payment
        DB::table('payments')->insert([
            'spend_type' => 'spend',
            'spend_id' => 122,
            'amount' => 536.56,
            'is_paid' => false,
            'paid_on' => '2026-02-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create Rules
        SimpleFinRule::create([
            'pattern' => 'Netflix',
            'spend_type' => 'periodic_spend',
            'spend_id' => 6,
        ]);
    }

    public function test_real_world_categorization_logic(): void
    {
        // Data from scratch.json

        // Transaction 1: YouTube Premium (Should balance match)
        $ytTransaction = SimpleFinTransaction::create([
            'id' => 'TRN-930a0862-2ca8-4444-934c-6232585b9804',
            'simple_fin_account_id' => $this->account->id,
            'posted' => '2026-02-02',
            'amount' => -22.99,
            'description' => 'YouTubePremium',
            'payee' => 'YouTube',
            'is_pending' => false,
        ]);

        // Transaction 2: Netflix (Should rule match)
        $netflixTransaction = SimpleFinTransaction::create([
            'id' => 'TRN-039c3e23-7a91-455b-9d41-118182b78a0d',
            'simple_fin_account_id' => $this->account->id,
            'posted' => '2026-02-06',
            'amount' => -19.11, // Different from seeder's 16.45, so balance match won't work
            'description' => 'Netflix.com',
            'payee' => 'Netflix',
            'is_pending' => false,
        ]);

        // Transaction 3: Spar Sarande (Unmatched yet)
        $sparTransaction = SimpleFinTransaction::create([
            'id' => 'TRN-9c173775-802c-490f-9f79-e58ed07c7295',
            'simple_fin_account_id' => $this->account->id,
            'posted' => '2026-02-04',
            'amount' => -15.39,
            'description' => 'SPAR SARANDE',
            'payee' => 'Spar Sarande',
            'is_pending' => false,
        ]);

        // Run categorization
        SimpleFinCategorizationService::categorize($this->user);

        // Assertions
        $ytTransaction->refresh();
        $this->assertEquals(7, $ytTransaction->spend_id);
        $this->assertEquals('periodic_spend', $ytTransaction->spend_type);
        $this->assertFalse($ytTransaction->is_confirmed);

        $netflixTransaction->refresh();
        $this->assertEquals(6, $netflixTransaction->spend_id);
        $this->assertEquals('periodic_spend', $netflixTransaction->spend_type);
        $this->assertFalse($netflixTransaction->is_confirmed);

        $sparTransaction->refresh();
        $this->assertNull($sparTransaction->spend_id, 'Spar should not be auto-matched yet');

        // Verify Model Attributes after confirmation
        $ytTransaction->update(['is_confirmed' => true]);

        $youtube = PeriodicSpend::find(7);
        $this->assertEquals(22.99, $youtube->actual_spend);
        $this->assertEquals(22.99, $youtube->total_spend);
        $this->assertEquals(0, $youtube->variance);
    }
}
