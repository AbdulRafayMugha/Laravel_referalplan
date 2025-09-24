<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\AffiliateLink;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoordinatorController extends Controller
{
    public function listAffiliates(Request $request)
    {
        $coordinatorId = $request->user()->id;
        $affiliates = User::where('role', 'affiliate')
            ->where('coordinator_id', $coordinatorId)
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($affiliates);
    }

    public function assign(Request $request, string $affiliateId)
    {
        $coordinatorId = $request->user()->id;
        $affiliate = User::where('id', $affiliateId)->where('role', 'affiliate')->firstOrFail();
        $affiliate->coordinator_id = $coordinatorId;
        $affiliate->save();
        return response()->json(['message' => 'Assigned']);
    }

    public function remove(Request $request, string $affiliateId)
    {
        $affiliate = User::where('id', $affiliateId)->where('role', 'affiliate')->firstOrFail();
        $affiliate->coordinator_id = null;
        $affiliate->save();
        return response()->json(['message' => 'Removed']);
    }

    public function stats(Request $request)
    {
        $coordinatorId = $request->user()->id;
        $totalAffiliates = User::where('role', 'affiliate')->where('coordinator_id', $coordinatorId)->count();
        $activeAffiliates = User::where('role', 'affiliate')->where('coordinator_id', $coordinatorId)->where('is_active', true)->count();
        $totalCommissions = (float) Commission::whereHas('affiliate', function ($q) use ($coordinatorId) {
            $q->where('coordinator_id', $coordinatorId);
        })->where('status', 'paid')->sum('amount');
        $pendingCommissions = (float) Commission::whereHas('affiliate', function ($q) use ($coordinatorId) {
            $q->where('coordinator_id', $coordinatorId);
        })->whereIn('status', ['pending', 'approved'])->sum('amount');
        $totalReferrals = Transaction::whereHas('referrer', function ($q) use ($coordinatorId) {
            $q->where('coordinator_id', $coordinatorId);
        })->count();

        return response()->json([
            // Use camelCase keys to match frontend UI expectations
            'totalAffiliates' => (int) $totalAffiliates,
            'activeAffiliates' => (int) $activeAffiliates,
            'totalCommissions' => (float) $totalCommissions,
            'pendingCommissions' => (float) $pendingCommissions,
            'totalReferrals' => (int) $totalReferrals,
        ]);
    }

    public function referrals(Request $request)
    {
        $coordinatorId = $request->user()->id;
        $referrals = Transaction::with(['referrer:id,name,email'])
            ->whereHas('referrer', function ($q) use ($coordinatorId) {
                $q->where('coordinator_id', $coordinatorId);
            })
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($referrals);
    }

    public function registerAffiliate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // generate unique referral code
        do {
            $referral = strtoupper(Str::random(8));
        } while (User::where('referral_code', $referral)->exists());

        $coordinator = $request->user();

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = 'affiliate';
        $user->referral_code = $referral;
        $user->is_active = true;
        $user->referrer_id = $coordinator->id;
        $user->coordinator_id = $coordinator->id;
        $user->email_verified = true;
        $user->email_verified_at = now();
        $user->save();

        AffiliateLink::create([
            'affiliate_id' => $user->id,
            'link_code' => $user->referral_code,
        ]);

        return response()->json([
            'user' => $user,
        ], 201);
    }
}


