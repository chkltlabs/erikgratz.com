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
     * @return void
     */
    public static function fetchAndIntake(User $user, ?Carbon $startDate = null): void
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

        // 2. Fetch pending transactions
        $pendingQueryParams = $queryParams;
        $pendingQueryParams['pending'] = 1;
        $pendingResponse = \Illuminate\Support\Facades\Http::get($url, $pendingQueryParams);

        $pendingData = [];
        if ($pendingResponse->successful()) {
            $pendingData = $pendingResponse->json();
        }

        (new self())->intake($user, $data, $pendingData, $startDate);
    }

    /**
     * Intake SimpleFIN JSON data and sync with the database.
     *
     * @param User $user The user to associate the accounts with.
     * @param array $data The decoded JSON data from SimpleFIN (non-pending).
     * @param array $pendingData The decoded JSON data from SimpleFIN including pending transactions.
     * @param Carbon|null $oldestTransactionDate Only remove missing transactions newer than or equal to this date.
     * @return void
     */
    public function intake(User $user, array $data, array $pendingData = [], ?Carbon $oldestTransactionDate = null): void
    {
        if (empty($data['accounts'])) {
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

        foreach ($data['accounts'] as $accountData) {
            DB::transaction(function () use ($accountData, $user, $oldestTransactionDate, $pendingTxnIdsByAccount, $nonPendingTxnIdsByAccount, $pendingData) {
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

                foreach ($allTransactions as $txnData) {
                    $incomingTransactionIds[] = $txnData['id'];
                    $postedDate = Carbon::createFromTimestamp($txnData['posted']);

                    // Differentiate pending:
                    // A transaction is pending if it is in pendingData BUT NOT in the regular (non-pending) data.
                    $isPending = false;
                    if (isset($pendingTxnIdsByAccount[$account->id]) && in_array($txnData['id'], $pendingTxnIdsByAccount[$account->id])) {
                        if (!isset($nonPendingTxnIdsByAccount[$account->id]) || !in_array($txnData['id'], $nonPendingTxnIdsByAccount[$account->id])) {
                            $isPending = true;
                        }
                    }

                    SimpleFinTransaction::updateOrCreate(
                        ['id' => $txnData['id']],
                        [
                            'simple_fin_account_id' => $account->id,
                            'posted' => $postedDate,
                            'amount' => $txnData['amount'],
                            'description' => $txnData['description'],
                            'payee' => $txnData['payee'] ?? null,
                            'memo' => $txnData['memo'] ?? null,
                            'transacted_at' => isset($txnData['transacted_at']) ? Carbon::createFromTimestamp($txnData['transacted_at']) : null,
                            'is_pending' => $isPending,
                        ]
                    );
                }

                // Remove missing transactions for this account ONLY if $oldestTransactionDate is provided
                if ($oldestTransactionDate) {
                    $account->transactions()
                        ->whereNotIn('id', $incomingTransactionIds)
                        ->where('posted', '>=', $oldestTransactionDate)
                        ->delete();
                }
            });
        }
    }
}
