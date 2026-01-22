<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    /**
     * =========================
     * CREATE OFFER (DEMO SAFE)
     * =========================
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_name'     => 'required|string|max:255',
                'description'   => 'required|string',
                'quantity'      => 'nullable|integer|min:1',
                'category'      => 'required|string',
                'delivery_type' => 'required|string|in:pickup,delivery',
                'address'       => 'nullable|string',
                'latitude'      => 'nullable|numeric',
                'longitude'     => 'nullable|numeric',
                'image'         => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
            ]);

            // USER SAFE (LOGIN / DEMO)
            $user = $request->user();
            $userId = $user ? $user->id : 1; // DEMO USER ID

            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('offers', $filename, 'public');
                $imagePath = 'storage/offers/' . $filename;
            }

            $offer = Offer::create([
                'user_id'       => $userId,
                'item_name'     => $validated['item_name'],
                'description'   => $validated['description'],
                'quantity'      => $validated['quantity'] ?? 1,
                'category'      => $validated['category'],
                'delivery_type' => $validated['delivery_type'],
                'address'       => $validated['address'] ?? null,
                'latitude'      => $validated['latitude'] ?? null,
                'longitude'     => $validated['longitude'] ?? null,
                'image'         => $imagePath,
                'status'        => 'available',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer created successfully',
                'data'    => $offer,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Offer store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create offer',
            ], 500);
        }
    }

    /**
     * =========================
     * GET ALL PUBLIC OFFERS
     * =========================
     */
    public function index(Request $request)
{
    $user = $request->user();

    $userLat = $request->query('latitude');
    $userLng = $request->query('longitude');
    $radius  = (float) $request->query('radius', 0);

    $query = Offer::with('user')
        ->where('status', 'available');

    if ($user) {
        $query->where('user_id', '!=', $user->id);
    }

    $offers = $query->get();

    if ($userLat && $userLng) {
        $userLat = (float) $userLat;
        $userLng = (float) $userLng;

        $offers = $offers->map(function ($offer) use ($userLat, $userLng) {
            if ($offer->latitude !== null && $offer->longitude !== null) {
                $offer->distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    (float) $offer->latitude,
                    (float) $offer->longitude
                );
            } else {
                $offer->distance = null;
            }
            return $offer;
        })
        ->filter(function ($offer) use ($radius) {
            if ($radius == 0) return true;
            if ($offer->distance === null) return true;
            return $offer->distance <= $radius;
        })
        ->sortBy(fn ($offer) => $offer->distance ?? 999999)
        ->values();
    }

    return response()->json([
        'success' => true,
        'offers'  => $offers,
    ]);
}


    /**
     * =========================
     * GET MY OFFERS
     * =========================
     */
    public function getMyOffers(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => true,
                'offers'  => [],
            ]);
        }

        $offers = Offer::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'offers'  => $offers,
        ]);
    }

    /**
     * =========================
     * UPDATE OFFER
     * =========================
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $offer = Offer::findOrFail($id);

            if ($offer->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($offer->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'This offer cannot be edited.',
                ], 403);
            }

            $validated = $request->validate([
                'item_name'     => 'required|string|max:255',
                'description'   => 'required|string',
                'quantity'      => 'nullable|integer|min:1',
                'category'      => 'required|string',
                'delivery_type' => 'required|string|in:pickup,delivery',
                'address'       => 'nullable|string',
                'latitude'      => 'nullable|numeric',
                'longitude'     => 'nullable|numeric',
                'image'         => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
            ]);

            if ($request->hasFile('image')) {
                if ($offer->image) {
                    Storage::disk('public')->delete(
                        str_replace('storage/', '', $offer->image)
                    );
                }

                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('offers', $filename, 'public');
                $validated['image'] = 'storage/offers/' . $filename;
            }

            $offer->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Offer updated successfully',
                'data'    => $offer,
            ]);

        } catch (\Throwable $e) {
            Log::error('Offer update error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update offer',
            ], 500);
        }
    }

    /**
     * =========================
     * DELETE OFFER
     * =========================
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $offer = Offer::findOrFail($id);

            if ($offer->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($offer->image) {
                Storage::disk('public')->delete(
                    str_replace('storage/', '', $offer->image)
                );
            }

            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Offer deleted successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('Offer delete error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete offer',
            ], 500);
        }
    }

    /**
     * =========================
     * DISTANCE CALCULATION (KM)
     * =========================
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
 * =========================
 * GET OFFER DETAILS
 * =========================
 */
public function show($id)
{
    try {
        $offer = Offer::with('user')
            ->where('offer_id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $offer,
        ]);

    } catch (\Throwable $e) {
        Log::error('Offer show error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Offer not found',
        ], 404);
    }
}

}
