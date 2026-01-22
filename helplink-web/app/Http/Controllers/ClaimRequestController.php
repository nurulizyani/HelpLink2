<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\Auth;

class ClaimRequestController extends Controller
{
    public function listAvailableRequests(Request $request)
{
    // Ambil request yang approved dan belum diklaim
    $query = RequestModel::where('status', 'approved')
        ->where('user_id', '!=', Auth::id());

    // Filter ikut lokasi (jika ada)
    if ($request->filled('location')) {
        $query->where('location', 'like', '%' . $request->location . '%');
    }

    $requests = $query->latest()->get();

    // Ambil senarai lokasi unik
    $locations = RequestModel::where('status', 'approved')
        ->distinct('location')
        ->pluck('location');

    return view('user.request.available', compact('requests', 'locations'));
}


    public function claim($id)
    {
        $requestItem = RequestModel::findOrFail($id);

        if (!in_array($requestItem->status, ['available', 'approved'])) {
            return redirect()->back()->with('error', 'Request is not available.');
        }

        if (Claim::where('request_id', $id)->where('user_id', Auth::id())->exists()) {
            return redirect()->back()->with('error', 'You have already claimed this request.');
        }

        Claim::create([
            'user_id' => Auth::id(),
            'request_id' => $requestItem->id,
            'status' => 'pending',
            'claimed_at' => now(),
        ]);

        $requestItem->update(['status' => 'claimed']);

        return redirect()->route('claims.request.my')->with('success', 'Request successfully claimed!');
    }

    public function myClaimedRequest()
    {
        $claims = Claim::with('request')
            ->where('user_id', Auth::id())
            ->whereNotNull('request_id')
            ->latest()
            ->get();

        return view('user.request.claimed', compact('claims'));
    }

    public function rateRequestClaim($id)
{
    $claim = Claim::with('request')->findOrFail($id);

    // Hanya request owner boleh rate
    if (auth()->id() !== $claim->request->user_id) {
        abort(403, 'Unauthorized action.');
    }

    return view('user.request.rate', compact('claim'));
}


   public function submitRequestRating(Request $request, $id)
{
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:255',
    ]);

    $claim = Claim::with('request')->findOrFail($id);

    // Hanya request owner boleh hantar rating
    if (auth()->id() !== $claim->request->user_id) {
        abort(403, 'Unauthorized action.');
    }

    $claim->update([
        'rating' => $request->rating,
        'comment' => $request->comment,
        'status' => 'completed',
    ]);

    // Request status mungkin dah pun completed semasa markAsCompleted,
    // jadi tak perlu force update semula kecuali perlu override

    return redirect()->route('claims.request.my')->with('success', 'Rating submitted.');
}


    // app/Http/Controllers/ClaimRequestController.php

public function markAsCompleted($id)
{
    $claim = Claim::with('request')->findOrFail($id);

    // Pastikan hanya request owner boleh tandakan complete
    if (auth()->id() !== $claim->request->user_id) {
        abort(403, 'Unauthorized action.');
    }

    // Update claim status
    $claim->status = 'completed';
    $claim->save();

    // Update request status jika belum completed
    if ($claim->request->status !== 'completed') {
        $claim->request->update(['status' => 'completed']);
    }

    return redirect()->back()->with('success', 'Request marked as completed.');
}


}
