<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimRequest;
use App\Models\Request as HelpRequest;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class ClaimRequestController extends Controller
{
    /**
     * Create a new claim (HELP a request)
     * Authenticated user only (Sanctum)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|exists:requests,id',
            ]);

            $user = $request->user(); // ✅ Sanctum user
            $req  = HelpRequest::find($request->request_id);

            if (!$req) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found.'
                ], 404);
            }

            // Prevent user from helping own request
            if ($req->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot help your own request.'
                ], 400);
            }

            // Prevent duplicate active claim
            $existing = ClaimRequest::where('user_id', $user->id)
                ->where('request_id', $req->id)
                ->whereIn('status', ['active'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already offered to help this request.'
                ], 409);
            }

            // Create claim
            $claim = ClaimRequest::create([
                'user_id'    => $user->id,
                'request_id' => $req->id,
                'status'     => 'active',
            ]);

            // Notify request owner
            NotificationHelper::send(
                $req->user_id,
                'New Helper',
                "{$user->name} has offered to help your request: {$req->item_name}",
                'request'
            );

            return response()->json([
                'success' => true,
                'message' => 'Help offer submitted successfully.',
                'data'    => $claim
            ], 201);

        } catch (\Exception $e) {
            Log::error('ClaimRequest store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all requests the authenticated user has claimed
     */
    public function myClaims(Request $request)
    {
        try {
            $user = $request->user(); // ✅ Sanctum user

            $claims = ClaimRequest::with([
                    'request',
                    'request.user'
                ])
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $claims
            ], 200);

        } catch (\Exception $e) {
            Log::error('ClaimRequest myClaims error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an active claim
     */
    public function cancelClaim(Request $request)
    {
        try {
            $request->validate([
                'claim_id' => 'required|exists:claim_requests,id'
            ]);

            $user  = $request->user();
            $claim = ClaimRequest::find($request->claim_id);

            if ($claim->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            if ($claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active claims can be cancelled.'
                ], 400);
            }

            $claim->status = 'cancelled';
            $claim->save();

            return response()->json([
                'success' => true,
                'message' => 'Claim cancelled successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ClaimRequest cancel error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark request as fulfilled by helper
     */
    public function markFulfilled(Request $request)
    {
        try {
            $request->validate([
                'claim_id' => 'required|exists:claim_requests,id'
            ]);

            $user  = $request->user();
            $claim = ClaimRequest::with('request')->find($request->claim_id);

            if ($claim->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            if ($claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active claims can be fulfilled.'
                ], 400);
            }

            $claim->status = 'fulfilled';
            $claim->save();

            $req = $claim->request;

            // Notify request owner
            NotificationHelper::send(
                $req->user_id,
                'Request Fulfilled',
                "Your request '{$req->item_name}' has been fulfilled.",
                'request'
            );

            return response()->json([
                'success' => true,
                'message' => 'Request marked as fulfilled.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ClaimRequest fulfill error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
