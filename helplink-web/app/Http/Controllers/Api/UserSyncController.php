<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;

class UserSyncController extends Controller
{
    public function syncUser(Request $request)
{
    try {
        $request->validate([
            'firebase_uid' => 'required|string',
            'name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        //INIT FIREBASE AUTH
        $firebase = (new Factory)
            ->withServiceAccount(storage_path('app/firebase-key.json'));

        $auth = $firebase->createAuth();

        // GET FIREBASE USER
        $firebaseUser = $auth->getUser($request->firebase_uid);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $user->update([
                'firebase_uid' => $request->firebase_uid,
                'name' => $request->name,
                'phone_number' => $request->phone,
                'address' => $request->address,
            ]);
        } else {
            $user = User::create([
                'firebase_uid' => $request->firebase_uid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone,
                'address' => $request->address,
                'password' => bcrypt('firebase_user'),
            ]);

            NotificationHelper::send(
                $user->id,
                'Welcome to HelpLink',
                'Your account has been successfully created.',
                'system'
            );
        }

        //SYNC EMAIL VERIFICATION (INI JAWAPAN MASALAH KAU)
        if ($firebaseUser->emailVerified && !$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return response()->json([
            'success' => true,
            'data' => $user,
            'firebase_email_verified' => $firebaseUser->emailVerified
        ]);

    } catch (\Exception $e) {
        Log::error('[SYNC USER] ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function profile(Request $request)
{
    return response()->json([
        'success' => true,
        'data' => $request->user()
    ]);
}

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
            ]);

            $user->update([
                'name' => $request->name,
                'phone_number' => $request->phone,
                'address' => $request->address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('[UPDATE PROFILE] ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            DB::beginTransaction();

            // OPTIONAL CLEANUP (SAFE – TABLE EXISTING ONLY)
            DB::table('conversations')
                ->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->delete();

            DB::table('messages')
                ->where('sender_id', $user->id)
                ->delete();

            DB::table('offers')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('requests')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('claim_offers')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('claim_requests')
                ->where('user_id', $user->id)
                ->delete();

            // DELETE USER
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted permanently'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DELETE PROFILE] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account'
            ], 500);
        }
    }

    public function saveFcmToken(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'fcm_token' => 'required|string',
            ]);

            $user = User::find($request->user_id);
            $user->fcm_token = $request->fcm_token;
            $user->save();

            return response()->json([
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('[FCM] ' . $e->getMessage());
            return response()->json([
                'success' => false
            ], 500);
        }
    }
}
