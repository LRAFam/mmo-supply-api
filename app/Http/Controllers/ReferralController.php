<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\ReferralEarning;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    /**
     * Get the current user's referral stats
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        // Get or generate referral code
        $referralCode = $user->getReferralCode();

        // Get referral stats
        $totalReferrals = $user->total_referrals;
        $totalEarnings = $user->total_referral_earnings;

        // Get active referrals (users who have made at least one purchase)
        $activeReferrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('first_purchase_at')
            ->count();

        // Get pending earnings
        $pendingEarnings = ReferralEarning::where('referrer_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_amount');

        // Get recent referrals
        $recentReferrals = User::where('referred_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $referralCode,
                'referral_link' => config('app.frontend_url') . '/auth/register?ref=' . $referralCode,
                'total_referrals' => $totalReferrals,
                'active_referrals' => $activeReferrals,
                'total_earnings' => floatval($totalEarnings),
                'pending_earnings' => floatval($pendingEarnings),
                'recent_referrals' => $recentReferrals,
            ]
        ]);
    }

    /**
     * Get detailed referral list
     */
    public function getReferrals(Request $request)
    {
        $user = Auth::user();

        $referrals = Referral::where('referrer_id', $user->id)
            ->with(['referred:id,name,email,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'referrals' => $referrals,
        ]);
    }

    /**
     * Get referral earnings history
     */
    public function getEarnings(Request $request)
    {
        $user = Auth::user();

        $earnings = ReferralEarning::where('referrer_id', $user->id)
            ->with(['order:id,total,status,created_at', 'referred:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'earnings' => $earnings,
        ]);
    }

    /**
     * Validate a referral code
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = User::where('referral_code', $request->code)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'referrer' => [
                'name' => $user->name,
                'id' => $user->id,
            ]
        ]);
    }

    /**
     * Apply referral code to a user during registration
     */
    public function applyReferralCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        // Check if user already has a referrer
        if ($user->referred_by) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a referrer'
            ], 400);
        }

        // Find referrer by code
        $referrer = User::where('referral_code', $request->code)->first();

        if (!$referrer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code'
            ], 404);
        }

        // Can't refer yourself
        if ($referrer->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot refer yourself'
            ], 400);
        }

        // Create referral relationship
        DB::transaction(function () use ($user, $referrer) {
            // Update user's referred_by
            $user->update(['referred_by' => $referrer->id]);

            // Create referral record
            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'referral_code' => $referrer->referral_code,
            ]);

            // Increment referrer's total referrals count
            $referrer->increment('total_referrals');
        });

        return response()->json([
            'success' => true,
            'message' => 'Referral code applied successfully'
        ]);
    }

    /**
     * Get referral leaderboard
     */
    public function getLeaderboard(Request $request)
    {
        $leaderboard = User::select([
            'id',
            'name',
            'total_referrals',
            'total_referral_earnings'
        ])
        ->where('total_referrals', '>', 0)
        ->orderBy('total_referral_earnings', 'desc')
        ->limit(100)
        ->get();

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
        ]);
    }
}
