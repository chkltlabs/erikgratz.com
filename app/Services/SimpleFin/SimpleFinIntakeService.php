<?php

namespace App\Services\SimpleFin;

use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinOrganization;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SimpleFinIntakeService
{
    /**
     * Fetch data from SimpleFIN and intake it.
     *
     * @param User $user
     * @param Carbon|null $startDate
     * @param \Closure|null $progressCallback Callback receiving a string message.
     * @return void
     */
    public static function fetchAndIntake(User $user, ?Carbon $startDate = null, ?\Closure $progressCallback = null): void
    {
        if (!$user->simple_fin_url) {
            throw new \Exception("User does not have a SimpleFIN URL set.");
        }

        $url = rtrim($user->simple_fin_url, '/') . '/accounts';

        // 1. Fetch non-pending transactions
        $queryParams = [];
        if ($startDate) {
            $queryParams['start-date'] = $startDate->timestamp;
        }

        $response = \Illuminate\Support\Facades\Http::get($url, $queryParams);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch data from SimpleFIN: " . $response->body());
        }

        $data = $response->json();

        if ($progressCallback) {
            $progressCallback("Data received successfully from SimpleFIN (non-pending).");
        }

        // 2. Fetch pending transactions
        $pendingQueryParams = $queryParams;
        $pendingQueryParams['pending'] = 1;
        $pendingResponse = \Illuminate\Support\Facades\Http::get($url, $pendingQueryParams);

        $pendingData = [];
        if ($pendingResponse->successful()) {
            $pendingData = $pendingResponse->json();
            if ($progressCallback) {
                $progressCallback("Data received successfully from SimpleFIN (pending).");
            }
        }

        (new self())->intake($user, $data, $pendingData, $startDate, $progressCallback);
    }

    /**
     * Intake SimpleFIN JSON data and sync with the database.
     *
     * @param User $user The user to associate the accounts with.
     * @param array $data The decoded JSON data from SimpleFIN (non-pending).
     * @param array $pendingData The decoded JSON data from SimpleFIN including pending transactions.
     * @param Carbon|null $oldestTransactionDate Only remove missing transactions newer than or equal to this date.
     * @param \Closure|null $progressCallback Callback receiving a string message.
     * @return void
     */
    public function intake(User $user, array $data, array $pendingData = [], ?Carbon $oldestTransactionDate = null, ?\Closure $progressCallback = null): void
    {
        if (empty($data['accounts'])) {
            if ($progressCallback) {
                $progressCallback("No accounts found in SimpleFIN data.");
            }
            return;
        }

        // Create a map of pending transaction IDs for each account
        $pendingTxnIdsByAccount = [];
        if (!empty($pendingData['accounts'])) {
            foreach ($pendingData['accounts'] as $pendingAccount) {
                $pendingTxnIdsByAccount[$pendingAccount['id']] = collect($pendingAccount['transactions'])->pluck('id')->toArray();
            }
        }

        // Extract IDs from non-pending data to identify what's definitely NOT pending
        $nonPendingTxnIdsByAccount = [];
        foreach ($data['accounts'] as $accountData) {
            $nonPendingTxnIdsByAccount[$accountData['id']] = collect($accountData['transactions'])->pluck('id')->toArray();
        }

        $syncedOrgIds = [];

        foreach ($data['accounts'] as $accountData) {
            DB::transaction(function () use ($accountData, $user, $oldestTransactionDate, $pendingTxnIdsByAccount, $nonPendingTxnIdsByAccount, $pendingData, $progressCallback, &$syncedOrgIds) {
                // 1. Sync Organization
                $orgData = $accountData['org'];
                $organization = SimpleFinOrganization::updateOrCreate(
                    ['id' => $orgData['id']],
                    [
                        'name' => $orgData['name'],
                        'domain' => $orgData['domain'] ?? null,
                        'url' => $orgData['url'] ?? null,
                        'sfin_url' => $orgData['sfin-url'] ?? null,
                    ]
                );

                if ($progressCallback && !in_array($organization->id, $syncedOrgIds)) {
                    $progressCallback("Organization synced: {$organization->name} (ID: {$organization->id})");
                    $syncedOrgIds[] = $organization->id;
                }

                // 2. Sync Account
                $account = SimpleFinAccount::updateOrCreate(
                    ['id' => $accountData['id']],
                    [
                        'user_id' => $user->id,
                        'simple_fin_organization_id' => $organization->id,
                        'name' => $accountData['name'],
                        'currency' => $accountData['currency'],
                        'balance' => $accountData['balance'],
                        'available_balance' => $accountData['available-balance'],
                        'balance_date' => Carbon::createFromTimestamp($accountData['balance-date']),
                    ]
                );

                // 3. Sync Transactions
                $incomingTransactionIds = [];

                // Combine transactions from both sets for this account
                $allTransactions = $accountData['transactions'];

                // Add transactions from pendingData that aren't already in accountData
                if (isset($pendingTxnIdsByAccount[$account->id])) {
                    $existingIds = collect($allTransactions)->pluck('id')->toArray();
                    $pendingAccountData = collect($pendingData['accounts'] ?? [])->firstWhere('id', $account->id);
                    if ($pendingAccountData) {
                        foreach ($pendingAccountData['transactions'] as $pTxn) {
                            if (!in_array($pTxn['id'], $existingIds)) {
                                $allTransactions[] = $pTxn;
                            }
                        }
                    }
                }

                if ($progressCallback) {
                    $progressCallback("Account synced: {$account->name} (Balance: {$account->balance}, Transactions: " . count($allTransactions) . ", ID: {$account->id})");
                }

                foreach ($allTransactions as $txnData) {
                    $incomingTransactionIds[] = $txnData['id'];
                    $postedTs = max(1, (int) ($txnData['posted'] ?? 0));
                    $postedDate = Carbon::createFromTimestamp($postedTs);

                    // Differentiate pending:
                    // A transaction is pending if it is in pendingData BUT NOT in the regular (non-pending) data.
                    $isPending = false;
                    if (isset($pendingTxnIdsByAccount[$account->id]) && in_array($txnData['id'], $pendingTxnIdsByAccount[$account->id])) {
                        if (!isset($nonPendingTxnIdsByAccount[$account->id]) || !in_array($txnData['id'], $nonPendingTxnIdsByAccount[$account->id])) {
                            $isPending = true;
                        }
                    }

                    $transaction = SimpleFinTransaction::updateOrCreate(
                        ['id' => $txnData['id']],
                        [
                            'simple_fin_account_id' => $account->id,
                            'posted' => $postedDate,
                            'amount' => $txnData['amount'],
                            'description' => $txnData['description'],
                            'payee' => $txnData['payee'] ?? null,
                            'memo' => $txnData['memo'] ?? null,
                            'transacted_at' => (isset($txnData['transacted_at']) && (int)$txnData['transacted_at'] >= 1)
                                ? Carbon::createFromTimestamp(max(1, (int)$txnData['transacted_at']))
                                : null,
                            'is_pending' => $isPending,
                        ]
                    );

                    if ($progressCallback) {
                        $status = $transaction->wasRecentlyCreated ? 'created' : 'updated';
                        $pendingStatus = $isPending ? ' (PENDING)' : '';
                        $progressCallback("  Transaction {$status}: {$transaction->description} ({$transaction->amount}){$pendingStatus} (ID: {$transaction->id})");
                    }
                }

                // Remove missing transactions for this account ONLY if $oldestTransactionDate is provided
                if ($oldestTransactionDate) {
                    $deletedCount = $account->transactions()
                        ->whereNotIn('id', $incomingTransactionIds)
                        ->where('posted', '>=', $oldestTransactionDate)
                        ->delete();

                    if ($progressCallback && $deletedCount > 0) {
                        $progressCallback("  Removed {$deletedCount} missing transactions for account {$account->name}.");
                    }
                }
            });
        }
    }
}
