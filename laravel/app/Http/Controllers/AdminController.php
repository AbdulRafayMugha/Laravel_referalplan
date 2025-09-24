<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Basic aggregate stats for the admin dashboard
        $totalAffiliates = (int) User::where('role', 'affiliate')->count();
        $activeAffiliates = (int) User::where('role', 'affiliate')->where('is_active', true)->count();

        $totalRevenue = (float) Transaction::query()->sum('amount');
        $totalCommissionsPaid = (float) Commission::where('status', 'paid')->sum('amount');
        $pendingCommissions = (float) Commission::whereIn('status', ['pending','approved'])->sum('amount');

        $newSignupsToday = (int) User::whereDate('created_at', now()->toDateString())->count();

        return response()->json([
            'stats' => [
                'totalAffiliates' => $totalAffiliates,
                'activeAffiliates' => $activeAffiliates,
                'totalRevenue' => $totalRevenue,
                'totalCommissionsPaid' => $totalCommissionsPaid,
                'pendingCommissions' => $pendingCommissions,
                // Simple placeholders the UI expects
                'conversionRate' => 0,
                'revenueGrowth' => 0,
                'newSignupsToday' => $newSignupsToday,
                'commissionTrends' => [],
                'conversionTrends' => [],
            ],
        ]);
    }

    public function topAffiliates(Request $request)
    {
        $limit = (int) ($request->query('limit', 5));

        $rows = Commission::select('affiliate_id', DB::raw('SUM(amount) as total_paid'))
            ->where('status', 'paid')
            ->groupBy('affiliate_id')
            ->orderByDesc('total_paid')
            ->limit($limit)
            ->get();

        $affiliateIds = $rows->pluck('affiliate_id')->filter()->unique()->all();
        $users = User::whereIn('id', $affiliateIds)->get(['id','name','email']);
        $userById = $users->keyBy('id');

        $result = $rows->map(function ($row) use ($userById) {
            $u = $userById->get($row->affiliate_id);
            return [
                'id' => $u?->id,
                'name' => $u?->name,
                'email' => $u?->email,
                'totalPaid' => (float) $row->total_paid,
            ];
        })->values();

        return response()->json($result);
    }
    public function affiliates(Request $request)
    {
        $affiliates = User::where('role', 'affiliate')
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($affiliates);
    }

    public function coordinators(Request $request)
    {
        $users = User::where('role', 'coordinator')
            ->orderByDesc('created_at')
            ->get(['id','name','email','is_active','created_at']);

        // Minimal metrics placeholders; adjust when real relations are available
        $coordinators = $users->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at,
                'affiliate_count' => 0,
                'active_affiliate_count' => 0,
                'total_commissions' => 0.0,
                'total_referrals' => 0,
            ];
        });

        return response()->json(['coordinators' => $coordinators]);
    }

    public function transactions(Request $request)
    {
        $tx = Transaction::with(['referrer:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($tx);
    }

    public function pendingCommissions(Request $request)
    {
        $commissions = Commission::with(['affiliate:id,name,email'])
            ->whereIn('status', ['pending','approved'])
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($commissions);
    }

    public function updateCommissionStatus(Request $request, string $id)
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

    public function analytics(Request $request)
    {
        // Simple placeholder analytics; expand as needed
        $timeRange = $request->query('timeRange', '30d');
        return response()->json([
            'timeRange' => $timeRange,
            'conversionRate' => 0,
            'revenue' => 0,
            'commissions' => 0,
            'trend' => [],
        ]);
    }

    public function coordinatorNetwork(Request $request, string $coordinatorId)
    {
        $u = User::where('role', 'coordinator')->findOrFail($coordinatorId);
        // Placeholder empty network until assignment logic exists
        return response()->json([
            'coordinator' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at,
                'affiliate_count' => 0,
                'active_affiliate_count' => 0,
                'total_commissions' => 0.0,
                'total_referrals' => 0,
            ],
            'affiliates' => [],
        ]);
    }

    public function updateCoordinatorStatus(Request $request, string $coordinatorId)
    {
        $data = $request->validate([
            'isActive' => 'required|boolean',
        ]);
        $u = User::where('role', 'coordinator')->findOrFail($coordinatorId);
        $u->is_active = $data['isActive'];
        $u->save();
        return response()->json(['success' => true]);
    }

    public function exportCoordinatorReport()
    {
        // Minimal CSV content
        $csv = "Name,Email,Active\n";
        $rows = User::where('role', 'coordinator')->get(['name','email','is_active']);
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s\n", $r->name, $r->email, $r->is_active ? 'Yes' : 'No');
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="coordinator-report.csv"',
        ]);
    }
}


