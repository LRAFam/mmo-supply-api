<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargebackProtectionService
{
    /**
     * Calculate hold period for a seller based on trust level
     */
    public function calculateHoldPeriod(User $seller, float $amount): int
    {
        // Get base hold period based on trust level
        $baseDays = match($seller->trust_level) {
            'new' => config('escrow.hold_period_new', 21),
            'standard' => config('escrow.hold_period_standard', 14),
            'trusted' => config('escrow.hold_period_trusted', 7),
            'verified' => config('escrow.hold_period_verified', 3),
            default => 14,
        };

        // Apply multiplier for high-risk transactions
        $highRiskThreshold = config('escrow.high_risk_threshold', 500);
        $highRiskMultiplier = config('escrow.high_risk_multiplier', 1.5);

        if ($amount >= $highRiskThreshold) {
            $baseDays = (int) ceil($baseDays * $highRiskMultiplier);
        }

        return $baseDays;
    }

    /**
     * Calculate chargeback reserve percentage for a seller
     */
    public function calculateReservePercent(User $seller): float
    {
        return match($seller->trust_level) {
            'new' => config('escrow.reserve_new', 20),
            'standard' => config('escrow.reserve_standard', 10),
            'trusted' => config('escrow.reserve_trusted', 5),
            'verified' => config('escrow.reserve_verified', 0),
            default => 10,
        };
    }

    /**
     * Calculate seller risk score
     */
    public function calculateRiskScore(User $seller, float $transactionAmount): int
    {
        $score = 0;

        // Account age (newer = higher risk)
        $accountAgeMonths = $seller->created_at->diffInMonths(now());
        if ($accountAgeMonths < 1) $score += 30;
        elseif ($accountAgeMonths < 3) $score += 20;
        elseif ($accountAgeMonths < 6) $score += 10;

        // Sales history (fewer sales = higher risk)
        if ($seller->completed_sales < 5) $score += 25;
        elseif ($seller->completed_sales < 20) $score += 15;
        elseif ($seller->completed_sales < 50) $score += 5;

        // Chargeback history
        if ($seller->chargebacks_received > 0) {
            $chargebackRate = ($seller->chargebacks_received / max($seller->completed_sales, 1)) * 100;
            $score += (int) ($chargebackRate * 10); // 10% chargeback rate = +100 score
        }

        // Dispute history
        if ($seller->disputed_sales > 0) {
            $disputeRate = ($seller->disputed_sales / max($seller->completed_sales, 1)) * 100;
            $score += (int) ($disputeRate * 5); // 10% dispute rate = +50 score
        }

        // Transaction amount (higher = more risk)
        if ($transactionAmount > 1000) $score += 20;
        elseif ($transactionAmount > 500) $score += 10;
        elseif ($transactionAmount > 250) $score += 5;

        // Recent chargebacks increase risk significantly
        if ($seller->last_chargeback_at && $seller->last_chargeback_at->diffInDays(now()) < 30) {
            $score += 40;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Apply hold to transaction
     */
    public function applyHold(Transaction $transaction, User $seller, string $reason = null): void
    {
        $holdDays = $this->calculateHoldPeriod($seller, $transaction->amount);
        $holdUntil = now()->addDays($holdDays);

        $transaction->update([
            'is_held' => true,
            'hold_until' => $holdUntil,
            'hold_reason' => $reason ?? "Escrow hold period for {$seller->trust_level} seller ({$holdDays} days)",
            'risk_score' => $this->calculateRiskScore($seller, $transaction->amount),
            'risk_factors' => json_encode(['test' => 'test']),
        ]);
    }

    /**
     * Update seller trust level based on performance
     */
    public function updateTrustLevel(User $seller): void
    {
        $completedSales = $seller->completed_sales;
        $chargebackRate = $completedSales > 0
            ? ($seller->chargebacks_received / $completedSales) * 100
            : 0;

        $accountAgeMonths = $seller->created_at->diffInMonths(now());

        // Determine trust level
        $newLevel = 'new';

        if ($completedSales >= 100 && $chargebackRate < 1 && $accountAgeMonths >= 6) {
            $newLevel = 'trusted';
        } elseif ($completedSales >= 10 && $chargebackRate < 3 && $accountAgeMonths >= 2) {
            $newLevel = 'standard';
        }

        if ($seller->trust_level !== 'verified') {
            $seller->update(['trust_level' => $newLevel]);
        }
    }
}
