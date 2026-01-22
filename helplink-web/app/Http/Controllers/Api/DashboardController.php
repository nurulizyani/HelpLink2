<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard summary for mobile app
     * Data source: SQL (Laravel)
     */
    public function summary(Request $request)
    {
        $user = $request->user(); // Sanctum authenticated user

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'my_offers' => DB::table('offers')
                ->where('user_id', $user->id)
                ->count(),

            'my_requests' => DB::table('requests')
                ->where('user_id', $user->id)
                ->count(),

            'my_responses' => DB::table('claim_requests')
                ->where('user_id', $user->id)
                ->count(),
        ]);
    }
}
