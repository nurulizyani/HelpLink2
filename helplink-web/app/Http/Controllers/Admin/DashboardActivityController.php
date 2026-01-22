<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\Offer;
use App\Models\ClaimOffer;
use App\Models\ClaimRequest;

class DashboardActivityController extends Controller
{
    public function latest()
    {
        $activities = collect();

        // Latest Requests
        $requests = Request::latest()
            ->take(5)
            ->get()
            ->map(function ($r) {
                return [
                    'type' => 'request',
                    'message' => "New request: {$r->item_name}",
                    'status' => $r->status,
                    'time' => $r->created_at->diffForHumans(),
                ];
            });

        // Latest Offers
        $offers = Offer::latest()
            ->take(5)
            ->get()
            ->map(function ($o) {
                return [
                    'type' => 'offer',
                    'message' => "New offer: {$o->item_name}",
                    'status' => $o->status,
                    'time' => $o->created_at->diffForHumans(),
                ];
            });

        // Latest Offer Claims
        $offerClaims = ClaimOffer::with('offer')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($c) {
                return [
                    'type' => 'offer_claim',
                    'message' => "Offer claimed: {$c->offer->item_name}",
                    'status' => $c->status,
                    'time' => $c->created_at->diffForHumans(),
                ];
            });

        // Latest Request Claims
        $requestClaims = ClaimRequest::with('request')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($c) {
                return [
                    'type' => 'request_claim',
                    'message' => "Request helped: {$c->request->item_name}",
                    'status' => $c->status,
                    'time' => $c->created_at->diffForHumans(),
                ];
            });

        $activities = $activities
            ->merge($requests)
            ->merge($offers)
            ->merge($offerClaims)
            ->merge($requestClaims)
            ->sortByDesc('time')
            ->values()
            ->take(10);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }
}
