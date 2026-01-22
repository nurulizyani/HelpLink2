<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimOffer;
use App\Models\Offer;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class ClaimOfferController extends Controller
{
    /**
     * =====================================
     * USER CLAIM OFFER
     * =====================================
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $request->validate([
                'offer_id' => 'required|exists:offers,offer_id',
            ]);

            $offer = Offer::where('offer_id', $request->offer_id)->first();

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found.'
                ], 404);
            }

            // ❌ Cannot claim own offer
            if ((int)$offer->user_id === (int)$user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot claim your own offer.'
                ], 403);
            }

            // ❌ Offer must be available
            if ($offer->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'This offer is no longer available.'
                ], 409);
            }

            // ❌ Prevent duplicate claim
            $exists = ClaimOffer::where('offer_id', $offer->offer_id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'received', 'completed'])
                ->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already claimed this offer.'
                ], 409);
            }

            // ✅ Create claim
            $claim = ClaimOffer::create([
                'offer_id' => $offer->offer_id,
                'user_id'  => $user->id,
                'status'   => 'active',
            ]);

            // ✅ Update offer status
            $offer->update(['status' => 'claimed']);

            // 🔔 Notify owner
            NotificationHelper::send(
                $offer->user_id,
                'Offer Claimed',
                "Your offer '{$offer->item_name}' has been claimed by {$user->name}.",
                'offer'
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer claimed successfully.',
                'data'    => $claim,
            ], 201);

        } catch (\Exception $e) {
            Log::error('ClaimOffer store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to claim offer.'
            ], 500);
        }
    }

    /**
     * =====================================
     * VIEW MY CLAIMS (CLAIMER)
     * =====================================
     */
    public function myClaims(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $claims = ClaimOffer::with('offer')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    /**
     * =====================================
     * CANCEL CLAIM (CLAIMER)
     * =====================================
     */
    public function cancelClaim(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $request->validate([
            'claim_id' => 'required|exists:claim_offers,id',
        ]);

        $claim = ClaimOffer::find($request->claim_id);

        if (!$claim || (int)$claim->user_id !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Claim not found or unauthorized.'
            ], 403);
        }

        if ($claim->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This claim cannot be cancelled.'
            ], 409);
        }

        $offer = Offer::where('offer_id', $claim->offer_id)->first();

        $claim->update(['status' => 'cancelled']);

        if ($offer) {
            $offer->update(['status' => 'available']);

            NotificationHelper::send(
                $offer->user_id,
                'Claim Cancelled',
                "A claim for your offer '{$offer->item_name}' has been cancelled.",
                'offer'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Claim cancelled successfully.',
        ]);
    }

    /**
     * =====================================
     * CONFIRM RECEIVED (CLAIMER)
     * =====================================
     */
    public function markReceived(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $request->validate([
                'claim_id' => 'required|exists:claim_offers,id',
            ]);

            $claim = ClaimOffer::find($request->claim_id);

            Log::info('MARK RECEIVED DEBUG', [
    'auth_user_id' => $user->id,
    'request_claim_id' => $request->claim_id,
    'claim_user_id' => optional($claim)->user_id,
]);

            if (!$claim || (int)$claim->user_id !== (int)$user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            if ($claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'This claim cannot be marked as received.'
                ], 409);
            }

            $claim->update(['status' => 'received']);

            $offer = Offer::where('offer_id', $claim->offer_id)->first();

            if ($offer) {
                NotificationHelper::send(
                    $offer->user_id,
                    'Item Received',
                    "The claimer has received '{$offer->item_name}'. Please confirm collection.",
                    'offer'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Item marked as received.',
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimOffer markReceived error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm received.'
            ], 500);
        }
    }

    /**
     * =====================================
     * MARK COLLECTED (OWNER)
     * =====================================
     */
    public function markCollected(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $request->validate([
                'claim_id' => 'required|exists:claim_offers,id',
            ]);

            $claim = ClaimOffer::find($request->claim_id);
            $offer = Offer::where('offer_id', $claim->offer_id)->first();

            if (!$offer || (int)$offer->user_id !== (int)$user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            if ($claim->status !== 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item has not been confirmed received yet.'
                ], 409);
            }

            $claim->update(['status' => 'completed']);
            $offer->update(['status' => 'completed']);

            // 🔔 Notify both
            NotificationHelper::send(
                $claim->user_id,
                'Offer Completed',
                "Your claim for '{$offer->item_name}' has been completed.",
                'offer'
            );

            NotificationHelper::send(
                $offer->user_id,
                'Offer Completed',
                "Your offer '{$offer->item_name}' has been successfully completed.",
                'offer'
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer marked as collected successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimOffer markCollected error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark collected.'
            ], 500);
        }
    }


    public function getByOffer($offerId)
{
    $user = request()->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $claim = ClaimOffer::with('user')
        ->where('offer_id', $offerId)
        ->whereIn('status', ['active', 'received'])
        ->first();

    if (!$claim) {
        return response()->json([
            'success' => false,
            'message' => 'No claim found'
        ]);
    }

    return response()->json([
        'success' => true,
        'claim' => $claim,
    ]);
}

}
