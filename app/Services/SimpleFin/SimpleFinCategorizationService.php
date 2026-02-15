<?php

namespace App\Services\SimpleFin;

use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SimpleFinCategorizationService
{
    /**
     * Categorize transactions for a specific user.
     *
     * @param User $user
     * @param bool $includeUnconfirmed Whether to re-categorize transactions that are already matched but not yet confirmed.
     */
    public static function categorize(User $user, bool $includeUnconfirmed = false): void
    {
        $query = SimpleFinTransaction::whereHas('account', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });

        if ($includeUnconfirmed) {
            $query->where(function ($q) {
                $q->whereNull('spend_id')
                  ->orWhere('is_confirmed', false);
            });
        } else {
            $query->whereNull('spend_id');
        }

        $transactions = $query->get();

        foreach ($transactions as $transaction) {
            self::categorizeTransaction($transaction);
        }
    }

    /**
     * Categorize a single transaction.
     */
    public static function categorizeTransaction(SimpleFinTransaction $transaction): void
    {
        // 1. Try Rule-based matching (String match)
        if (self::applyRules($transaction)) {
            return;
        }

        // 2. Try Balance matching (Exact amount match with unpaid payments)
        if (self::applyBalanceMatch($transaction)) {
            return;
        }
    }

    /**
     * Apply string matching rules.
     */
    protected static function applyRules(SimpleFinTransaction $transaction): bool
    {
        $rules = SimpleFinRule::all();
        $text = strtolower($transaction->payee . ' ' . $transaction->description);

        foreach ($rules as $rule) {
            if (str_contains($text, strtolower($rule->pattern))) {
                $transaction->update([
                    'spend_type' => $rule->spend_type,
                    'spend_id' => $rule->spend_id,
                    'is_confirmed' => false,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Apply balance matching logic.
     */
    protected static function applyBalanceMatch(SimpleFinTransaction $transaction): bool
    {
        // Search for unpaid payments with the exact same amount
        // We look for payments that are within a reasonable timeframe (e.g., same month)
        $payment = Payment::where('amount', abs($transaction->amount))
            ->where('is_paid', false)
            ->whereDate('paid_on', '>=', $transaction->posted->startOfMonth())
            ->whereDate('paid_on', '<=', $transaction->posted->endOfMonth())
            ->first();

        if ($payment) {
            $transaction->update([
                'spend_type' => $payment->spend_type,
                'spend_id' => $payment->spend_id,
                'is_confirmed' => false,
            ]);
            return true;
        }

        return false;
    }
}
