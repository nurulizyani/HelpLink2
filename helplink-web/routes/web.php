<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Offer;
use App\Models\Request as RequestModel;

// Controllers
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DashboardApiController;
use App\Http\Controllers\Admin\DashboardActivityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\NotificationController;


/*
|--------------------------------------------------------------------------
| DEFAULT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {return redirect()->route('admin.login');});

/*
|--------------------------------------------------------------------------
| ADMIN AUTH
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:admin')->prefix('admin')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // REAL-TIME DASHBOARD API
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('admin.dashboard.stats');
    Route::get('/dashboard/activities',[DashboardActivityController::class, 'latest'])->name('admin.dashboard.activities');

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users-export', [UserController::class, 'export'])->name('admin.users.export');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('admin.users.show');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    /*
    |--------------------------------------------------------------------------
    | OFFERS
    |--------------------------------------------------------------------------
    */
    Route::get('/offers', [AdminOfferController::class, 'index'])->name('admin.offers.index');
    Route::get('/offers/{offer}', [AdminOfferController::class, 'show'])->name('admin.offers.show');
    Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy'])->name('admin.offers.destroy');
    Route::get('/offers-export', [AdminOfferController::class, 'export'])->name('admin.offers.export');
    Route::put('/offers/{offer}/flag',[AdminOfferController::class, 'flag'])->name('admin.offers.flag');
    Route::put('/offers/{offer}/unflag',[AdminOfferController::class, 'unflag'])->name('admin.offers.unflag');

    /*
    |--------------------------------------------------------------------------
    | REQUESTS
    |--------------------------------------------------------------------------
    */
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('admin.requests.index');
    Route::get('/requests/{id}', [AdminRequestController::class, 'show'])->name('admin.requests.show');
    Route::put('/requests/{id}/status', [AdminRequestController::class, 'updateStatus'])->name('admin.requests.updateStatus');
    Route::get('/requests-export', [AdminRequestController::class, 'export'])->name('admin.requests.export');
    //Route::post('/requests/{id}/approve', [AdminRequestController::class, 'approve'])->name('admin.requests.approve');
    //Route::post('/requests/{id}/reject', [AdminRequestController::class, 'reject'])->name('admin.requests.reject');
    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */
    Route::get('/reports', [ReportController::class, 'adminIndex'])->name('admin.reports.index');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications',[NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications/read-all',[NotificationController::class, 'readAll'])->name('admin.notifications.readAll');
    Route::post('/notifications/{id}/read',[NotificationController::class, 'markAsRead'])->name('admin.notifications.read');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('admin.notifications.unread');
});
/*
|--------------------------------------------------------------------------
| TELEGRAM & FIREBASE
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\TelegramController;
use Kreait\Firebase\Factory;

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

Route::get('/firebase-test', function () {
    $factory = (new Factory)
        ->withServiceAccount(storage_path('app/firebase-key.json'))
        ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

    $database = $factory->createDatabase();

    $database->getReference('test_connection')->set([
        'status' => 'connected',
        'time' => now()->toDateTimeString(),
    ]);

    return 'Firebase connection successful';
});

Route::get('/_test', function () {
    return 'OK';
});