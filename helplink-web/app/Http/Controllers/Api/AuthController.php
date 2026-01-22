<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function firebaseLogin(Request $request)
    {
        try {
            $request->validate([
                'id_token' => 'required|string',
            ]);

            // 1️⃣ Verify Firebase ID Token
            $verifiedToken = $this->firebaseAuth
                ->verifyIdToken($request->id_token);

            $claims = $verifiedToken->claims();

            $firebaseUid = $claims->get('sub'); // Firebase UID
            $email = $claims->get('email');
            $name = $claims->get('name') ?? 'User';

            if (!$email || !$firebaseUid) {
                return response()->json([
                    'message' => 'Invalid Firebase token data'
                ], 401);
            }

            // 2️⃣ Find user by firebase_uid FIRST
            $user = User::where('firebase_uid', $firebaseUid)->first();

            // 3️⃣ If user not exist → CREATE in SQL
            if (!$user) {
                $user = User::create([
                    'firebase_uid'      => $firebaseUid,
                    'email'             => $email,
                    'name'              => $name,
                    'password'          => bcrypt(Str::random(32)), // dummy
                    'email_verified_at' => Carbon::now(),
                ]);
            }

            // 4️⃣ Create Sanctum token
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'name'  => $user->name,
                ],
            ]);

        } catch (\Throwable $e) {

            Log::error('Firebase login failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Firebase login failed',
                'details' => $e->getMessage(),
            ], 401);
        }
    }
}
