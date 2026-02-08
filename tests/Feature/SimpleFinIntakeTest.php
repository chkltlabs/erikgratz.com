<?php

namespace Tests\Feature;

use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinOrganization;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\User;
use App\Services\SimpleFin\SimpleFinIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleFinIntakeTest extends TestCase
{
    use RefreshDatabase;

    private SimpleFinIntakeService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SimpleFinIntakeService();
        $this->user = User::factory()->create();
    }

    public function test_it_intakes_data_correctly()
    {
        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Test Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '90.00',
                    'balance-date' => 1700000000,
                    'org' => [
                        'id' => 'ORG-1',
                        'name' => 'Test Org',
                        'domain' => 'test.com',
                        'url' => 'https://test.com',
                        'sfin-url' => 'https://sfin.test.com',
                    ],
                    'transactions' => [
                        [
                            'id' => 'TXN-1',
                            'posted' => 1700000000,
                            'amount' => '-10.00',
                            'description' => 'Test Txn 1',
                            'payee' => 'Payee 1',
                            'memo' => 'Memo 1',
                            'transacted_at' => 1700000000,
                        ],
                    ],
                ],
            ],
        ];

        $this->service->intake($this->user, $data, [], null);

        $this->assertDatabaseHas('simple_fin_organizations', ['id' => 'ORG-1', 'name' => 'Test Org']);
        $this->assertDatabaseHas('simple_fin_accounts', ['id' => 'ACT-1', 'user_id' => $this->user->id, 'balance' => 100.00]);
        $this->assertDatabaseHas('simple_fin_transactions', ['id' => 'TXN-1', 'amount' => -10.00]);
    }

    public function test_it_updates_existing_records_rather_than_duplicating()
    {
        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Test Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '90.00',
                    'balance-date' => 1700000000,
                    'org' => [
                        'id' => 'ORG-1',
                        'name' => 'Test Org',
                        'domain' => 'test.com',
                    ],
                    'transactions' => [
                        [
                            'id' => 'TXN-1',
                            'posted' => 1700000000,
                            'amount' => '-10.00',
                            'description' => 'Test Txn 1',
                        ],
                    ],
                ],
            ],
        ];

        // First run
        $this->service->intake($this->user, $data, [], null);

        // Update data
        $data['accounts'][0]['balance'] = '200.00';
        $data['accounts'][0]['transactions'][0]['amount'] = '-20.00';

        // Second run
        $this->service->intake($this->user, $data, [], null);

        $this->assertEquals(1, SimpleFinAccount::count());
        $this->assertEquals(1, SimpleFinTransaction::count());
        $this->assertDatabaseHas('simple_fin_accounts', ['id' => 'ACT-1', 'balance' => 200.00]);
        $this->assertDatabaseHas('simple_fin_transactions', ['id' => 'TXN-1', 'amount' => -20.00]);
    }

    public function test_it_removes_missing_transactions_only_if_oldest_date_provided()
    {
        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Test Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '90.00',
                    'balance-date' => 1700000000,
                    'org' => [
                        'id' => 'ORG-1',
                        'name' => 'Test Org',
                    ],
                    'transactions' => [
                        [
                            'id' => 'TXN-1',
                            'posted' => 1700000000,
                            'amount' => '-10.00',
                            'description' => 'Test Txn 1',
                        ],
                        [
                            'id' => 'TXN-2',
                            'posted' => 1700000000,
                            'amount' => '-5.00',
                            'description' => 'Test Txn 2',
                        ],
                    ],
                ],
            ],
        ];

        // First run with 2 transactions
        $this->service->intake($this->user, $data, [], null);
        $this->assertEquals(2, SimpleFinTransaction::count());

        // Second run with only 1 transaction (TXN-2 is missing) but NO oldest date param
        unset($data['accounts'][0]['transactions'][1]);
        $this->service->intake($this->user, $data, [], null);

        // TXN-2 should STILL exist because we didn't provide $oldestTransactionDate
        $this->assertEquals(2, SimpleFinTransaction::count());
        $this->assertDatabaseHas('simple_fin_transactions', ['id' => 'TXN-2']);

        // Third run WITH oldest date param
        $oldestDate = \Illuminate\Support\Carbon::createFromTimestamp(1700000000);
        $this->service->intake($this->user, $data, [], $oldestDate);

        // Now TXN-2 should be removed
        $this->assertEquals(1, SimpleFinTransaction::count());
        $this->assertDatabaseMissing('simple_fin_transactions', ['id' => 'TXN-2']);
    }

    public function test_it_preserves_transactions_older_than_provided_oldest_date()
    {
        // 1. Setup existing old transaction
        $account = SimpleFinAccount::create([
            'id' => 'ACT-1',
            'user_id' => $this->user->id,
            'simple_fin_organization_id' => SimpleFinOrganization::create(['id' => 'ORG-1', 'name' => 'Org'])->id,
            'name' => 'Account',
            'currency' => 'USD',
            'balance' => 100,
            'available_balance' => 100,
            'balance_date' => now(),
        ]);

        $oldTxn = SimpleFinTransaction::create([
            'id' => 'TXN-OLD',
            'simple_fin_account_id' => $account->id,
            'posted' => \Illuminate\Support\Carbon::createFromTimestamp(1600000000), // Very old
            'amount' => -10,
            'description' => 'Old Txn',
        ]);

        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-1',
                    'name' => 'Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [
                        [
                            'id' => 'TXN-NEW',
                            'posted' => 1700000000,
                            'amount' => '-20.00',
                            'description' => 'New Txn',
                        ],
                    ],
                ],
            ],
        ];

        // 2. Run intake with oldest date = 1700000000.
        // TXN-OLD (1600000000) is older than 1700000000, so it should be preserved.
        $this->service->intake($this->user, $data, [], \Illuminate\Support\Carbon::createFromTimestamp(1700000000));

        // 3. Verify TXN-OLD still exists
        $this->assertDatabaseHas('simple_fin_transactions', ['id' => 'TXN-OLD']);
        $this->assertDatabaseHas('simple_fin_transactions', ['id' => 'TXN-NEW']);
        $this->assertEquals(2, SimpleFinTransaction::count());
    }

    public function test_it_can_be_associated_with_an_account()
    {
        $account = \App\Models\Account::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-ASSOC-1',
                    'name' => 'Associated Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [],
                ],
            ],
        ];

        $this->service->intake($this->user, $data, [], null);

        $sfinAccount = SimpleFinAccount::find('ACT-ASSOC-1');
        $sfinAccount->associated()->associate($account);
        $sfinAccount->save();

        $this->assertInstanceOf(\App\Models\Account::class, $sfinAccount->fresh()->associated);
        $this->assertEquals($account->id, $sfinAccount->fresh()->associated->id);
    }

    public function test_it_can_be_associated_with_a_card()
    {
        $card = \App\Models\Card::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'accounts' => [
                [
                    'id' => 'ACT-ASSOC-2',
                    'name' => 'Associated Card',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [],
                ],
            ],
        ];

        $this->service->intake($this->user, $data, [], null);

        $sfinAccount = SimpleFinAccount::find('ACT-ASSOC-2');
        $sfinAccount->associated()->associate($card);
        $sfinAccount->save();

        $this->assertInstanceOf(\App\Models\Card::class, $sfinAccount->fresh()->associated);
        $this->assertEquals($card->id, $sfinAccount->fresh()->associated->id);
    }

    public function test_it_fetches_and_intakes_data_correctly()
    {
        $this->user->update(['simple_fin_url' => 'https://example.com/sfin']);

        $regularData = [
            'accounts' => [
                [
                    'id' => 'ACT-REG',
                    'name' => 'Regular Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [
                        [
                            'id' => 'TXN-CONFIRMED',
                            'posted' => 1700000000,
                            'amount' => '-10.00',
                            'description' => 'Confirmed Txn',
                        ],
                    ],
                ],
            ],
        ];

        $pendingData = [
            'accounts' => [
                [
                    'id' => 'ACT-REG',
                    'name' => 'Regular Account',
                    'currency' => 'USD',
                    'balance' => '100.00',
                    'available-balance' => '100.00',
                    'balance-date' => 1700000000,
                    'org' => ['id' => 'ORG-1', 'name' => 'Org'],
                    'transactions' => [
                        [
                            'id' => 'TXN-CONFIRMED',
                            'posted' => 1700000000,
                            'amount' => '-10.00',
                            'description' => 'Confirmed Txn',
                        ],
                        [
                            'id' => 'TXN-PENDING',
                            'posted' => 1700000000,
                            'amount' => '-20.00',
                            'description' => 'Pending Txn',
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://example.com/sfin/accounts?start-date=*&pending=1' => Http::response($pendingData, 200),
            'https://example.com/sfin/accounts?start-date=*' => Http::response($regularData, 200),
        ]);

        $startDate = \Illuminate\Support\Carbon::create(2023, 11, 15);

        SimpleFinIntakeService::fetchAndIntake($this->user, $startDate);

        $this->assertDatabaseHas('simple_fin_accounts', ['id' => 'ACT-REG']);
        $this->assertDatabaseHas('simple_fin_transactions', [
            'id' => 'TXN-CONFIRMED',
            'is_pending' => false,
        ]);
        $this->assertDatabaseHas('simple_fin_transactions', [
            'id' => 'TXN-PENDING',
            'is_pending' => true,
        ]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($startDate) {
            return str_contains($request->url(), 'pending=1') &&
                   str_contains($request->url(), "start-date={$startDate->timestamp}");
        });

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($startDate) {
            return !str_contains($request->url(), 'pending=1') &&
                   str_contains($request->url(), "start-date={$startDate->timestamp}");
        });
    }
}
