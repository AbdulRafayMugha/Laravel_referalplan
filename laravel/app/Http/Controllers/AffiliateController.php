<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function listLinks(Request $request)
    {
        $links = AffiliateLink::where('affiliate_id', $request->user()->id)->orderByDesc('created_at')->get();
        return response()->json($links);
    }

    public function createLink(Request $request)
    {
        $data = $request->validate([
            'custom_code' => 'nullable|string|max:64'
        ]);

        $code = $data['custom_code'] ?? strtoupper(str()->uuid()->toString());
        if (AffiliateLink::where('link_code', $code)->exists()) {
            return response()->json(['message' => 'Provided link code already exists'], 422);
        }

        $link = AffiliateLink::create([
            'affiliate_id' => $request->user()->id,
            'link_code' => $code,
        ]);
        return response()->json($link, 201);
    }

    public function toggleLink(Request $request, $id)
    {
        $link = AffiliateLink::where('id', $id)->where('affiliate_id', $request->user()->id)->firstOrFail();
        $link->is_active = !$link->is_active;
        $link->save();
        return response()->json($link);
    }

    public function stats(Request $request)
    {
        $agg = AffiliateLink::where('affiliate_id', $request->user()->id)
            ->selectRaw('COALESCE(SUM(clicks),0) as total_clicks, COALESCE(SUM(conversions),0) as total_conversions, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_links')
            ->first();
        $totalClicks = (int)($agg->total_clicks ?? 0);
        $totalConversions = (int)($agg->total_conversions ?? 0);
        $conversionRate = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;
        return response()->json([
            'totalClicks' => $totalClicks,
            'totalConversions' => $totalConversions,
            'conversionRate' => $conversionRate,
            'activeLinks' => (int)($agg->active_links ?? 0)
        ]);
    }

    public function referralTree(Request $request)
    {
        // Simplified version; can be optimized with fewer queries
        $user = $request->user();
        $level1 = \App\Models\User::where('referrer_id', $user->id)->get();
        $level2 = \App\Models\User::whereIn('referrer_id', $level1->pluck('id'))->get();
        $level3 = \App\Models\User::whereIn('referrer_id', $level2->pluck('id'))->get();

        $transform = function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => 'affiliate',
                'isActive' => (bool)$u->is_active,
                'createdAt' => $u->created_at,
                'totalEarnings' => 0,
                'totalReferrals' => 0,
                'conversionRate' => 0,
            ];
        };

        return response()->json([
            'level1' => $level1->map($transform),
            'level2' => $level2->map($transform),
            'level3' => $level3->map($transform),
            'totals' => [
                'level1' => $level1->count(),
                'level2' => $level2->count(),
                'level3' => $level3->count(),
                'total' => $level1->count() + $level2->count() + $level3->count(),
            ],
        ]);
    }
}


