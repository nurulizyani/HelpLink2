<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    /**
     * =========================
     * INDEX - LIST ALL OFFERS
     * =========================
     */
    public function index()
    {
        $offers = Offer::with('user')
            ->latest()
            ->get();

        return view('admin.offers.index', compact('offers'));
    }

    /**
     * =========================
     * SHOW - OFFER DETAILS
     * =========================
     */
    public function show($id)
    {
        $offer = Offer::with(['user', 'claims.user'])
            ->findOrFail($id);

        return view('admin.offers.show', compact('offer'));
    }

    /**
     * =========================
     * EDIT OFFER
     * =========================
     */
    public function edit($id)
    {
        $offer = Offer::with('user')->findOrFail($id);
        return view('admin.offers.edit', compact('offer'));
    }

    /**
     * =========================
     * UPDATE OFFER
     * =========================
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:available,claimed,completed',
        ]);

        $offer = Offer::findOrFail($id);

        $offer->update([
            'item_name'   => $request->item_name,
            'description' => $request->description,
            'quantity'    => $request->quantity,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offer updated successfully.');
    }

    /**
     * =========================
     * DELETE OFFER
     * =========================
     */
    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);

        // Delete image properly
        if ($offer->image && Storage::disk('public')->exists($offer->image)) {
            Storage::disk('public')->delete($offer->image);
        }

        $offer->delete();

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offer deleted successfully.');
    }

    /**
     * =========================
     * EXPORT CSV (FULL DATA)
     * =========================
     */
    public function export()
    {
        $offers = Offer::with('user')
            ->latest()
            ->get();

        $filename = 'offers_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($offers) {
            $file = fopen('php://output', 'w');

            // CSV HEADER
            fputcsv($file, [
                'Offer ID',
                'Item Name',
                'Description',
                'Category',
                'Quantity',
                'Status',
                'Donor Name',
                'Donor Email',
                'Address',
                'Delivery Type',
                'Image Path',
                'Created At',
            ]);

            foreach ($offers as $offer) {
                fputcsv($file, [
                    $offer->offer_id,
                    $offer->item_name,
                    $offer->description,
                    $offer->category,
                    $offer->quantity,
                    ucfirst($offer->status),
                    $offer->user->name ?? '-',
                    $offer->user->email ?? '-',
                    $offer->address,
                    ucfirst($offer->delivery_type),
                    $offer->image,
                    optional($offer->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function flag($offer)
{
    $offer = Offer::findOrFail($offer);

    $offer->update([
        'status' => 'flagged'
    ]);

    return back()->with('success', 'Offer has been flagged for review.');
}

public function unflag($offer)
{
    $offer = Offer::findOrFail($offer);

    $offer->update([
        'status' => 'available'
    ]);

    return back()->with('success', 'Offer has been restored.');
}

}
