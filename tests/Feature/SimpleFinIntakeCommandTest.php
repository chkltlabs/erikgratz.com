<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SimpleFin\SimpleFinIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleFinIntakeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_and_intakes_data_for_all_users_with_url()
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'simple_fin_url' => 'https://example.com/user1']);
        $user2 = User::factory()->create(['name' => 'User 2', 'simple_fin_url' => 'https://example.com/user2']);
        $user3 = User::factory()->create(['simple_fin_url' => null]);

        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Account 1',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [],
                ],
                [
                    'id' => 'ACT-2',
                    'name' => 'Account 2',
                    'currency' => 'USD',
                    'balance' => '200.00',
                    'available-balance' => '200.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [],
                ],
            ],
        ];

        Http::fake([
            'https://example.com/user1/accounts*' => Http::response($data, 200),
            'https://example.com/user2/accounts*' => Http::response($data, 200),
        ]);

        $this->artisan('app:simple-fin-intake')
            ->expectsOutput("Fetching and starting intake for user User 1...")
            ->expectsOutput("  Data received successfully from SimpleFIN (non-pending).")
            ->expectsOutput("  Organization synced: Org (ID: ORG-1)")
            ->expectsOutput("  Account synced: Account 1 (Balance: 100.00, Transactions: 0, ID: ACT-1)")
            ->expectsOutput("  Account synced: Account 2 (Balance: 200.00, Transactions: 0, ID: ACT-2)")
            ->expectsOutput("Intake completed successfully for User 1.")
            ->expectsOutput("Fetching and starting intake for user User 2...")
            ->expectsOutput("  Data received successfully from SimpleFIN (non-pending).")
            ->expectsOutput("  Organization synced: Org (ID: ORG-1)")
            ->expectsOutput("  Account synced: Account 1 (Balance: 100.00, Transactions: 0, ID: ACT-1)")
            ->expectsOutput("  Account synced: Account 2 (Balance: 200.00, Transactions: 0, ID: ACT-2)")
            ->expectsOutput("Intake completed successfully for User 2.")
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://example.com/user1'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://example.com/user2'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'user3'));
    }

    public function test_command_fetches_for_specific_user()
    {
        $user1 = User::factory()->create(['simple_fin_url' => 'https://example.com/user1']);
        $user2 = User::factory()->create(['simple_fin_url' => 'https://example.com/user2']);

        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Account 1',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [],
                ],
            ],
        ];

        Http::fake([
            'https://example.com/user1/accounts*' => Http::response($data, 200),
        ]);

        $this->artisan('app:simple-fin-intake', ['user_id' => $user1->id])
            ->expectsOutput("Fetching and starting intake for user {$user1->name}...")
            ->expectsOutput("Intake completed successfully for {$user1->name}.")
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://example.com/user1'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'https://example.com/user2'));
    }
}
