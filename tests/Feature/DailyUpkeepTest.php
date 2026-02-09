<?php

namespace Tests\Feature;

use App\Jobs\DailyUpkeep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DailyUpkeepTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_triggers_simple_fin_intake_for_users_with_url()
    {
        // Prevent other jobs from running if they use classes that might not exist or need mocking
        Bus::fake([
            \App\Jobs\ZeroISB::class,
            \App\Jobs\DebitIFB::class,
            \App\Jobs\GuessISB::class,
        ]);

        $user1 = User::factory()->create(['simple_fin_url' => 'https://example.com/user1']);
        $user2 = User::factory()->create(['simple_fin_url' => 'https://example.com/user2']);
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
            ],
        ];

        Http::fake([
            'https://example.com/user1/accounts*' => Http::response($data, 200),
            'https://example.com/user2/accounts*' => Http::response($data, 200),
        ]);

        DailyUpkeep::dispatch();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://example.com/user1'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://example.com/user2'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'user3'));

        Bus::assertDispatched(\App\Jobs\ZeroISB::class);
        Bus::assertDispatched(\App\Jobs\DebitIFB::class);
        Bus::assertDispatched(\App\Jobs\GuessISB::class);
    }
}
