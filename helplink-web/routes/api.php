<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserSyncController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\ClaimOfferController;
use App\Http\Controllers\Api\ClaimRequestController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/dashboard/summary',[DashboardController::class, 'summary']);
// ==================================================
// BASIC TEST
// ==================================================
Route::get('/ping', fn () => response()->json(['message' => 'pong']));
Route::get('/ping-test', fn () => response()->json(['ok' => true]));

// ==================================================
// AUTH CHECK (SANCTUM)
// ==================================================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ==================================================
// TELEGRAM
// ==================================================
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// ==================================================
// AUTH
// ==================================================
Route::post('/auth/firebase-login', [AuthController::class, 'firebaseLogin']);

// ==================================================
// USER SYNC (LOGIN / REGISTER)
// ==================================================
Route::post('/sync-user', [UserSyncController::class, 'syncUser']);
Route::post('/save-fcm-token', [UserSyncController::class, 'saveFcmToken']);

// ==================================================
// 🔐 PROFILE (AUTH REQUIRED)
// ==================================================
Route::middleware('auth:sanctum')->group(function () {

    // GET PROFILE
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    // UPDATE PROFILE
    Route::post('/profile/update', [UserSyncController::class, 'updateProfile']);

    // DELETE PROFILE
    Route::delete('/profile/delete', [UserSyncController::class, 'deleteProfile']);

});

// ==================================================
// 🔓 OFFERS (PUBLIC)
// ==================================================
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{id}', [OfferController::class, 'show'])->whereNumber('id');

// ==================================================
// 🔓 REQUESTS (PUBLIC)
// ==================================================
Route::get('/requests', [RequestController::class, 'index']);
Route::get('/requests/{id}', [RequestController::class, 'show'])->whereNumber('id');

// ==================================================
// 🔐 PROTECTED ROUTES
// ==================================================
Route::middleware('auth:sanctum')->group(function () {

    // ================= OFFERS =================
    Route::post('/offers', [OfferController::class, 'store']);
    Route::get('/offers/my', [OfferController::class, 'getMyOffers']);
    Route::put('/offers/{id}', [OfferController::class, 'update'])->whereNumber('id');
    Route::delete('/offers/{id}', [OfferController::class, 'destroy'])->whereNumber('id');

    // ================= CLAIM OFFER =================
    Route::post('/claim-offers/store', [ClaimOfferController::class, 'store']);
    Route::get('/claim-offers/my', [ClaimOfferController::class, 'myClaims']);
    Route::post('/claim-offers/cancel', [ClaimOfferController::class, 'cancelClaim']);
    Route::post('/claim-offers/received', [ClaimOfferController::class, 'markReceived']);
    Route::post('/claim-offers/collected', [ClaimOfferController::class, 'markCollected']);

    // ================= REQUESTS =================
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/my', [RequestController::class, 'myRequests']);
    Route::put('/requests/{id}', [RequestController::class, 'update'])->whereNumber('id');
    Route::delete('/requests/{id}', [RequestController::class, 'destroy'])->whereNumber('id');

    // ================= CLAIM REQUEST =================
    Route::post('/claim-requests/store', [ClaimRequestController::class, 'store']);
    Route::get('/claim-requests/my', [ClaimRequestController::class, 'myClaims']);
    Route::post('/claim-requests/cancel', [ClaimRequestController::class, 'cancelClaim']);
    Route::post('/claim-requests/fulfill', [ClaimRequestController::class, 'markFulfilled']);

    // ================= 💬 CHAT =================
    Route::post('/chat/start', [ChatController::class, 'startConversation']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/messages/{conversationId}', [ChatController::class, 'getMessages'])
        ->whereNumber('conversationId');
    Route::get('/chat/conversations', [ChatController::class, 'myConversations']);

    // ================= ⭐ RATING =================
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::get('/ratings/received', [RatingController::class, 'received']);
    Route::get('/ratings/summary', [RatingController::class, 'summary']);
});
