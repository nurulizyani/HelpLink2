<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\Offer;
use Illuminate\Support\Facades\Auth;

class ClaimOfferController extends Controller
{
    public function listAvailable(Request $request)
    {
        $query = Offer::query()
            ->where('status', 'available')
            ->where('user_id', '!=', Auth::id());

        if ($request->filled('delivery_type')) {
            $query->where('delivery_type', $request->delivery_type);
        }

        if ($request->filled('location')) {
            $keyword = $request->location;
            $query->where(function ($q) use ($keyword) {
                $q->where('location', 'like', "%$keyword%")
                  ->orWhere('address', 'like', "%$keyword%");
            });
        }

        if ($request->filled('user_lat') && $request->filled('user_lon') && $request->filled('radius')) {
            $userLat = $request->user_lat;
            $userLon = $request->user_lon;
            $radius = $request->radius;

            $haversine = "(6371 * acos(cos(radians($userLat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($userLon)) + sin(radians($userLat)) * sin(radians(latitude))))";

            $query->select('*')
                  ->selectRaw("$haversine AS distance")
                  ->having('distance', '<=', $radius)
                  ->orderBy('distance');
        }

        $offers = $query->latest()->get();
        return view('user.claim.available', compact('offers'));
    }

    public function claim(Offer $offer)
{
    $user = Auth::user();

    if ($offer->status !== 'available') {
        return redirect()->back()->with('error', 'Offer is not available.');
    }

    if (Claim::where('offer_id', $offer->offer_id)->where('user_id', $user->id)->exists()) {
        return redirect()->back()->with('error', 'You have already claimed this offer.');
    }

    Claim::create([
        'user_id' => $user->id,
        'offer_id' => $offer->offer_id, // ✅ Guna offer_id, bukan id
        'status' => 'pending',
        'claimed_at' => now(),
    ]);

    $offer->update(['status' => 'claimed']);

    return redirect()->route('claims.offer.my')->with('success', 'Offer claimed successfully!');
}



    public function myClaimedOffers()

    {
        $claims = Claim::with('offer')
            ->where('user_id', Auth::id())
            ->whereNotNull('offer_id')
            ->latest()
            ->get();

        return view('user.claim.my', compact('claims'));
    }

    public function rate($id)
    {
        $claim = Claim::with('offer')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNotNull('offer_id')
            ->firstOrFail();

        return view('user.claim.rate', compact('claim'));
    }

    public function submitRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ]);

        $claim = Claim::with('offer')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNotNull('offer_id')
            ->firstOrFail();

        $claim->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'completed',
        ]);

        $claim->offer->update(['status' => 'completed']);

        return redirect()->route('claims.offer.my')->with('success', 'Rating submitted.');
    }
}
