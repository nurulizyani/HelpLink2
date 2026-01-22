<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\TelegramController;


class OfferController extends Controller
{
    public function available(Request $request)
    {
        $query = Offer::query()
            ->where('status', 'available')
            ->where('user_id', '!=', auth()->id());

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

    public function myOffers(Request $request)
    {
        $query = Offer::where('user_id', Auth::id());

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $offers = $query->with('claims.user')->latest()->get();
        return view('user.offer.my', compact('offers'));
    }

    public function create()
    {
        return view('user.offer.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'item_name'     => 'required|string|max:255',
        'description'   => 'nullable|string',
        'quantity'      => 'nullable|integer',
        'location'      => 'nullable|string|max:255',
        'latitude'      => 'nullable|numeric',
        'longitude'     => 'nullable|numeric',
        'address'       => 'nullable|string|max:255',
        'delivery_type' => 'nullable|in:pickup,delivery',
        'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('offers', 'public');
    }

    $offer = Offer::create([
        'user_id'       => Auth::id(),
        'item_name'     => $request->item_name,
        'description'   => $request->description,
        'quantity'      => $request->quantity,
        'location'      => $request->location,
        'latitude'      => $request->latitude,
        'longitude'     => $request->longitude,
        'address'       => $request->address,
        'delivery_type' => $request->delivery_type,
        'image'         => $imagePath,
        'status'        => 'available',
    ]);

    // 🔔 HANTAR NOTIFIKASI KE TELEGRAM
    $userName = Auth::user()->name;
    $message = "🆕 NEW OFFER!\n"
             . "👤 User: $userName\n"
             . "📦 Item: {$offer->item_name}\n"
             . "🔢 Quantity: " . ($offer->quantity ?? 'N/A') . "\n"
             . "📍 Location: " . ($offer->location ?? 'N/A') . "\n"
             . "🚚 Delivery: " . ($offer->delivery_type ?? 'N/A') . "\n";

    \App\Http\Controllers\TelegramController::sendMessage($message);

    return redirect()->route('offer.my')->with('success', 'Offer created successfully.');
}


    public function show($id)
    {
        $offer = Offer::with('claims.user')->findOrFail($id);
        return view('user.offer.show', compact('offer'));
    }

    public function edit($id)
    {
        $offer = Offer::where('user_id', Auth::id())->findOrFail($id);
        return view('user.offer.edit', compact('offer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name'     => 'required|string|max:255',
            'description'   => 'nullable|string',
            'quantity'      => 'nullable|integer',
            'location'      => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'address'       => 'nullable|string|max:255',
            'delivery_type' => 'nullable|in:pickup,delivery',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $offer = Offer::where('user_id', Auth::id())->findOrFail($id);

        $imagePath = $offer->image;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('offers', 'public');
        }

        $offer->update([
            'item_name'     => $request->item_name,
            'description'   => $request->description,
            'quantity'      => $request->quantity,
            'location'      => $request->location,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'address'       => $request->address,
            'delivery_type' => $request->delivery_type,
            'image'         => $imagePath,
        ]);

        return redirect()->route('offer.my')->with('success', 'Offer updated successfully.');
    }

    public function destroy($id)
    {
        $offer = Offer::where('user_id', Auth::id())->findOrFail($id);
        $offer->delete();

        return redirect()->route('offer.my')->with('success', 'Offer deleted successfully.');
    }

    public function rate($id)
    {
        $offer = Offer::where('user_id', Auth::id())->findOrFail($id);
        return view('user.offer.rate', compact('offer'));
    }

    public function submitRating(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $offer = Offer::where('user_id', Auth::id())->findOrFail($id);
        $offer->update([
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->route('offer.my')->with('success', 'Rating submitted successfully.');
    }

    public function adminIndex()
    {
        $offers = Offer::latest()->get();
        return view('admin.offers.index', compact('offers'));
    }

// 🧠 API: Store Offer (for Flutter)
public function apiStore(Request $request)
{
    $request->validate([
        'item_name'     => 'required|string|max:255',
        'description'   => 'nullable|string',
        'quantity'      => 'nullable|integer',
        'location'      => 'nullable|string|max:255',
        'latitude'      => 'nullable|numeric',
        'longitude'     => 'nullable|numeric',
        'address'       => 'nullable|string|max:255',
        'delivery_type' => 'nullable|in:pickup,delivery',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('offers', 'public');
    }

    $offer = Offer::create([
        // sementara, kalau tak ada login Flutter, pakai user_id = 1
        'user_id'       => $request->user_id ?? 1,
        'item_name'     => $request->item_name,
        'description'   => $request->description,
        'quantity'      => $request->quantity,
        'location'      => $request->location,
        'latitude'      => $request->latitude,
        'longitude'     => $request->longitude,
        'address'       => $request->address,
        'delivery_type' => $request->delivery_type,
        'image'         => $imagePath,
        'status'        => 'available',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Offer created successfully',
        'data' => $offer
    ], 201);
}
}