<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Rating;
use App\Models\Offer;
use App\Models\ClaimOffer;
use App\Models\User;

class RatingController extends Controller
{
    // =====================================================
    // CREATE RATING (Offer or Request)
    // =====================================================
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

            $validated = $request->validate([
                'to_user_id'    => 'required|exists:users,id',
                'offer_id'      => 'nullable|exists:offers,offer_id',
                'request_id'    => 'nullable|exists:requests,id',
                'rating_value'  => 'required|integer|min:1|max:5',
                'comment'       => 'nullable|string|max:2000',
            ]);

            // Must be linked to offer OR request (not both)
            $offerId = $validated['offer_id'] ?? null;
            $requestId = $validated['request_id'] ?? null;

            if (($offerId && $requestId) || (!$offerId && !$requestId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating must be linked to either offer or request only.'
                ], 422);
            }

            // Cannot rate yourself
            if ((int)$validated['to_user_id'] === (int)$user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot rate yourself.'
                ], 422);
            }

            // Authorization rule: only allow rating after completion
            if ($offerId) {
                // Offer must be completed
                $offer = Offer::where('offer_id', $offerId)->first();
                if (!$offer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Offer not found.'
                    ], 404);
                }

                // Find claim for this offer (the actual transaction)
                $claim = ClaimOffer::where('offer_id', $offerId)
                    ->where('status', 'completed')
                    ->first();

                if (!$claim || strtolower((string)$offer->status) !== 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only rate after the offer is completed.'
                    ], 409);
                }

                // Only two parties can rate each other: offer owner and claimant
                $allowedUsers = [(int)$offer->user_id, (int)$claim->user_id];
                if (!in_array((int)$user->id, $allowedUsers, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized rating action.'
                    ], 403);
                }

                // to_user_id must be the other party
                if (!in_array((int)$validated['to_user_id'], $allowedUsers, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid rating target.'
                    ], 422);
                }
            }

            // Prevent duplicate rating (handled by unique index too, but we give friendly message)
            $duplicate = Rating::where('from_user_id', $user->id)
                ->where('to_user_id', $validated['to_user_id'])
                ->when($offerId, fn($q) => $q->where('offer_id', $offerId))
                ->when($requestId, fn($q) => $q->where('request_id', $requestId))
                ->first();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this user for this transaction.'
                ], 409);
            }

            $rating = Rating::create([
                'from_user_id' => $user->id,
                'to_user_id' => $validated['to_user_id'],
                'offer_id' => $offerId,
                'request_id' => $requestId,
                'rating_value' => $validated['rating_value'],
                'comment' => $validated['comment'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully.',
                'data' => $rating
            ], 201);

        } catch (\Exception $e) {
            Log::error('Rating store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating.'
            ], 500);
        }
    }

    // =====================================================
    // GET RATINGS RECEIVED (Profile)
    // =====================================================
    public function received(Request $request)
    {
        try {
            $userId = $request->query('user_id');

            if (!$userId) {
                $authUser = $request->user();
                if (!$authUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated.'
                    ], 401);
                }
                $userId = $authUser->id;
            }

            $ratings = Rating::with(['fromUser:id,name'])
                ->where('to_user_id', $userId)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ratings
            ]);

        } catch (\Exception $e) {
            Log::error('Rating received error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load ratings.'
            ], 500);
        }
    }

    // =====================================================
    // SUMMARY (Average + Count)
    // =====================================================
    public function summary(Request $request)
    {
        try {
            $userId = $request->query('user_id');

            if (!$userId) {
                $authUser = $request->user();
                if (!$authUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated.'
                    ], 401);
                }
                $userId = $authUser->id;
            }

            $count = Rating::where('to_user_id', $userId)->count();
            $avg = Rating::where('to_user_id', $userId)->avg('rating_value');
            $avg = $avg ? round((float)$avg, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'average' => $avg,
                    'count' => $count,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Rating summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load rating summary.'
            ], 500);
        }
    }
}
