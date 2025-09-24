<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function list(Request $request)
    {
        $userId = $request->user()->id;
        $commissions = Commission::where('affiliate_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(50);
        return response()->json($commissions);
    }

    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $q = Commission::where('affiliate_id', $userId);
        $totalEarnings = (float) $q->clone()->where('status', 'paid')->sum('amount');
        $pendingEarnings = (float) $q->clone()->whereIn('status', ['pending','approved'])->sum('amount');
        $thisMonthEarnings = (float) $q->clone()->where('status', 'paid')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
        $level1 = (float) $q->clone()->where('status', 'paid')->where('level', 1)->sum('amount');
        $level2 = (float) $q->clone()->where('status', 'paid')->where('level', 2)->sum('amount');
        $level3 = (float) $q->clone()->where('status', 'paid')->where('level', 3)->sum('amount');

        return response()->json([
            'totalEarnings' => $totalEarnings,
            'pendingEarnings' => $pendingEarnings,
            'thisMonthEarnings' => $thisMonthEarnings,
            'commissionsByLevel' => [
                'level1' => $level1,
                'level2' => $level2,
                'level3' => $level3,
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,paid,cancelled'
        ]);
        $c = Commission::findOrFail($id);
        $c->status = $data['status'];
        $c->paid_at = $data['status'] === 'paid' ? now() : null;
        $c->save();
        return response()->json($c);
    }

    // PUBLIC/ADMIN helpers used by frontend service
    public function levels()
    {
        // If there is a commission_levels table, return its contents; otherwise default
        if (DB::getSchemaBuilder()->hasTable('commission_levels')) {
            $levels = DB::table('commission_levels')->orderBy('level')->get();
            return response()->json($levels);
        }
        return response()->json([
            ['level' => 1, 'rate' => 15],
            ['level' => 2, 'rate' => 5],
            ['level' => 3, 'rate' => 2.5],
        ]);
    }

    public function settings()
    {
        // Placeholder settings that UI expects
        return response()->json([
            'minimumPayout' => 50,
            'payoutSchedule' => 'monthly',
            'currency' => 'USD',
        ]);
    }
}


