<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function affiliates(Request $request)
    {
        $affiliates = User::where('role', 'affiliate')
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($affiliates);
    }

    public function coordinators(Request $request)
    {
        $coordinators = User::where('role', 'coordinator')
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($coordinators);
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
}


