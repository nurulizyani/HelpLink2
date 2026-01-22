<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\User;

class ClaimController extends Controller
{
    // 🟢 Claim Offer API (Flutter)
    public function claimOffer(Request $request)
    {
        $request->validate([
            'firebase_uid' => 'required',
            'offer_id'     => 'required|exists:offers,id',
        ]);

        // 1️⃣ Cari user berdasarkan Firebase UID
        $user = User::where('firebase_uid', $request->firebase_uid)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // 2️⃣ Elak double claim
        $exists = Claim::where('offer_id', $request->offer_id)
                       ->where('user_id', $user->id)
                       ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'You have already claimed this offer.',
            ], 409);
        }

        // 3️⃣ Simpan claim baru (request_id biar null)
        $claim = Claim::create([
            'offer_id'   => $request->offer_id,
            'request_id' => null,
            'user_id'    => $user->id,
            'status'     => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer claimed successfully.',
            'data'    => $claim,
        ]);
    }
}
