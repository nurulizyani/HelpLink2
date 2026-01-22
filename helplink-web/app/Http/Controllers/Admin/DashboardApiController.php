<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Request as RequestModel;
use App\Models\Offer;
use App\Models\ClaimOffer;
use App\Models\ClaimRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    public function stats()
    {
        // ===================== REQUEST TREND (LAST 7 DAYS) =====================
        $requestTrend = RequestModel::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ensure exactly 7 days (even if no data)
        $dates = collect(range(0, 6))->map(function ($i) {
            return Carbon::now()->subDays(6 - $i)->format('Y-m-d');
        });

        $trendData = $dates->map(function ($date) use ($requestTrend) {
            $found = $requestTrend->firstWhere('date', $date);

            return [
                'date' => Carbon::parse($date)->format('d M'),
                'total' => $found ? $found->total : 0,
            ];
        });

        // ===================== RESPONSE =====================
        return response()->json([
            'success' => true,
            'data' => [

                // USERS
                'total_users' => User::count(),

                // REQUESTS
                'total_requests' => RequestModel::count(),
                'pending_requests' => RequestModel::where('status', 'pending')->count(),
                'approved_requests' => RequestModel::where('status', 'approved')->count(),
                'fulfilled_requests' => RequestModel::where('status', 'fulfilled')->count(),

                // OFFERS
                'total_offers' => Offer::count(),
                'active_offers' => Offer::where('status', 'active')->count(),

                // CLAIMS
                'offer_claims' => ClaimOffer::count(),
                'request_claims' => ClaimRequest::count(),

                // CHART DATA
                'charts' => [
                    'request_trend' => $trendData,

                    'offer_vs_claim' => [
                        'offers' => Offer::count(),
                        'claims' => ClaimOffer::count(),
                    ],

                    'request_status' => [
                        'pending' => RequestModel::where('status', 'pending')->count(),
                        'approved' => RequestModel::where('status', 'approved')->count(),
                        'fulfilled' => RequestModel::where('status', 'fulfilled')->count(),
                    ],
                ],
            ]
        ]);
    }
}
