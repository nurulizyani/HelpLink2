<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\RequestImage;
use App\Models\Request as UserRequest;
use App\Models\Claim;
use App\Http\Controllers\TelegramController;


class RequestController extends Controller
{
    public function create()
    {
        return view('user.request.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
            'delivery_type' => 'required|in:pickup,delivery',
            'quantity' => 'required|integer|min:1',
            'location' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'supporting_documents.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 1. Simpan request
        $newRequest = RequestModel::create([
            'user_id' => auth()->id(),
            'item_name' => $request->item_name,
            'description' => $request->description,
            'address' => $request->address,
            'delivery_type' => $request->delivery_type,
            'quantity' => $request->quantity,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'pending',
        ]);

        // 2. Upload & simpan images
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $path = $file->store('supporting_documents', 'public');

                RequestImage::create([
                    'request_id' => $newRequest->id,
                    'image_path' => $path,
                ]);
            }
        }
        return redirect()->route('requests.my')->with('success', 'Your request has been submitted and is pending approval.');
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'item_name' => 'required|string|max:255',
        'description' => 'required|string',
        'address' => 'required|string',
        'delivery_type' => 'required|in:pickup,delivery',
        'quantity' => 'required|integer|min:1',
        'location' => 'required|string',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

    ]);

    $requestModel = RequestModel::findOrFail($id);

    // Pastikan user adalah pemilik
    if ($requestModel->user_id !== auth()->id()) {
        abort(403);
    }

    // Update data utama
    $requestModel->update([
        'item_name' => $request->item_name,
        'description' => $request->description,
        'address' => $request->address,
        'delivery_type' => $request->delivery_type,
        'quantity' => $request->quantity,
        'location' => $request->location,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
    ]);

    // Jika ada gambar baru dimuat naik
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $file) {
            $path = $file->store('supporting_documents', 'public');

            RequestImage::create([
                'request_id' => $requestModel->id,
                'image_path' => $path,
            ]);
        }
    }

    return redirect()->route('requests.my')->with('success', 'Request updated successfully.');
}

        public function myRequests()
    {
        $requests = auth()->user()->requests()->latest()->get();
        return view('user.request.my', compact('requests'));
    }

        public function show($id)
    {
        $request = UserRequest::with('images')->findOrFail($id);
        return view('user.request.show', compact('request'));
    }  

        public function edit($id)
    {
        $request = RequestModel::with('images')->findOrFail($id);

        // Pastikan user hanya boleh edit request sendiri
        if ($request->user_id !== auth()->id()) {
            abort(403);
        }

        // Hanya boleh edit jika masih pending
        if ($request->status !== 'pending') {
            return redirect()->route('requests.my')->with('error', 'You can only edit requests that are still pending.');
        }

        return view('user.request.edit', compact('request'));
    }

    public function deleteImage($id)
{
    $image = RequestImage::findOrFail($id);

    // Pastikan user adalah pemilik request gambar ini
    if ($image->request->user_id !== auth()->id()) {
        abort(403);
    }

    // Padam fail dari storage
    if (Storage::disk('public')->exists($image->image_path)) {
        Storage::disk('public')->delete($image->image_path);
    }

    // Padam dari DB
    $image->delete();

    return back()->with('success', 'Image deleted successfully.');
}

public function availableRequests(Request $request)
{
    $query = \App\Models\Request::with('user', 'images')
        ->where('status', 'approved');

    // Jika ada lokasi, tapis ikut lokasi
    if ($request->filled('location')) {
        $query->where('location', $request->location);
    }

    $requests = $query->latest()->get();

    // Dapatkan semua lokasi unik untuk dropdown
    $locations = \App\Models\Request::where('status', 'approved')
    ->pluck('location')
    ->filter()
    ->unique();

    return view('user.request.available', compact('requests', 'locations'));
}

public function claimRequest($requestId)
{
    $request = \App\Models\Request::findOrFail($requestId);

    // Elak claim request sendiri
    if ($request->user_id == auth()->id()) {
        return back()->with('error', 'You cannot claim your own request.');
    }

    // Elak claim lebih sekali
    $existing = \App\Models\Claim::where('user_id', auth()->id())
        ->where('request_id', $requestId)
        ->first();

    if ($existing) {
        return back()->with('error', 'You have already claimed this request.');
    }

    // Simpan claim baru
    \App\Models\Claim::create([
        'user_id' => auth()->id(),
        'request_id' => $requestId,
        'claimed_at' => now(),
        'status' => 'pending',
    ]);

    // ✅ Hantar notifikasi Telegram kepada pemilik request
    $requestOwner = $request->user;
    $chatId = $requestOwner->telegram_chat_id;

    if ($chatId) {
        $item = $request->item_name;
        $claimer = auth()->user()->name;

        TelegramController::sendMessage($chatId, "🤝 Your request for *{$item}* has been claimed by *{$claimer}*! Please check your HelpLink dashboard.");
    }

    // 🔁 Redirect ke page claimed requests
    return redirect()->route('claimed.requests')->with('success', 'You have successfully claimed this request. Thank you for your kindness!');
}

}
