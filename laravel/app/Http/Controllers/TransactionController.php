<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Models\Commission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'customer_email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'referral_code' => 'nullable|string',
            'transaction_type' => 'nullable|in:purchase,subscription,upgrade',
        ]);

        $affiliateLinkId = null;
        $referrerId = null;
        if (!empty($data['referral_code'])) {
            $link = AffiliateLink::where('link_code', $data['referral_code'])->where('is_active', true)->first();
            if ($link) {
                $affiliateLinkId = $link->id;
                $referrerId = $link->affiliate_id;
                $link->increment('conversions');
            }
        }

        $tx = Transaction::create([
            'customer_email' => $data['customer_email'],
            'amount' => $data['amount'],
            'affiliate_link_id' => $affiliateLinkId,
            'referrer_id' => $referrerId,
            'transaction_type' => $data['transaction_type'] ?? 'purchase',
            'status' => 'completed',
        ]);

        if ($referrerId) {
            $this->createCommissions($tx->id, $referrerId, (float)$data['amount']);
        }

        return response()->json($tx, 201);
    }

    private function createCommissions(int $transactionId, string $referrerId, float $amount): void
    {
        $level1Rate = (float) (env('LEVEL_1_COMMISSION', 15));
        $level2Rate = (float) (env('LEVEL_2_COMMISSION', 5));
        $level3Rate = (float) (env('LEVEL_3_COMMISSION', 2.5));

        // Level 1
        Commission::create([
            'affiliate_id' => $referrerId,
            'transaction_id' => $transactionId,
            'level' => 1,
            'amount' => round($amount * $level1Rate / 100, 2),
            'rate' => $level1Rate,
            'status' => 'approved',
        ]);

        // Level 2
        $lvl2 = User::where('id', $referrerId)->value('referrer_id');
        if ($lvl2) {
            Commission::create([
                'affiliate_id' => $lvl2,
                'transaction_id' => $transactionId,
                'level' => 2,
                'amount' => round($amount * $level2Rate / 100, 2),
                'rate' => $level2Rate,
                'status' => 'approved',
            ]);

            // Level 3
            $lvl3 = User::where('id', $lvl2)->value('referrer_id');
            if ($lvl3) {
                Commission::create([
                    'affiliate_id' => $lvl3,
                    'transaction_id' => $transactionId,
                    'level' => 3,
                    'amount' => round($amount * $level3Rate / 100, 2),
                    'rate' => $level3Rate,
                    'status' => 'approved',
                ]);
            }
        }
    }

    public function list(Request $request)
    {
        $tx = Transaction::orderByDesc('created_at')->paginate(20);
        return response()->json($tx);
    }
}


