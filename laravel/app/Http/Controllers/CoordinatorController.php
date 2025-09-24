<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

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
            'total_affiliates' => $totalAffiliates,
            'active_affiliates' => $activeAffiliates,
            'total_commissions' => $totalCommissions,
            'pending_commissions' => $pendingCommissions,
            'total_referrals' => $totalReferrals,
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
}


