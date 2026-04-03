<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\EmailVerificationController;
use App\Http\Controllers\API\Admin;
use App\Http\Controllers\API\Guest;
use App\Http\Controllers\API\Guest\PaymentController;

// ── Public webhook endpoints (no auth — verified by signature) ─────
Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook'])
    ->name('payments.webhook');

// Telegram webhooks (no auth required)
Route::post('/telegram-webhook', [\App\Http\Controllers\API\Telegram\WebhookController::class, 'handle'])
    ->name('telegram.webhook');
Route::post('/telegram-webhook/admin', [\App\Http\Controllers\API\Telegram\AdminWebhookController::class, 'handle'])
    ->name('telegram.admin.webhook');

// ── Public ─────────────────────────────────────────
Route::post('/register',        [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login',           [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password',  [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

// ── Google OAuth ────────────────────────────────────
Route::get('/auth/google/redirect', [\App\Http\Controllers\API\Auth\GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [\App\Http\Controllers\API\Auth\GoogleAuthController::class, 'callback']);

// ── Public room browsing (no auth required) ────────
Route::prefix('guest')->group(function () {
    Route::get('/stats',                   [Guest\RoomController::class, 'stats']);
    Route::get('/rooms/availability',      [Guest\RoomController::class, 'availability']);
    Route::get('/rooms',                   [Guest\RoomController::class, 'index']);
    Route::get('/rooms/{id}',              [Guest\RoomController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/rooms/{id}/booked-dates', [Guest\RoomController::class, 'bookedDates'])->where('id', '[0-9]+');
    Route::get('/reviews',                 [Guest\ReviewController::class, 'index']);
});

// ── Authenticated ───────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',           [AuthController::class, 'me']);
    Route::put('/profile',          [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    Route::post('/logout',      [AuthController::class, 'logout']);
    Route::post('/logout-all',  [AuthController::class, 'logoutAll']);

    // Email verification
    Route::prefix('email')->group(function () {
        Route::get('/status',    [EmailVerificationController::class, 'status']);
        Route::post('/send-otp', [EmailVerificationController::class, 'sendOtp']);
        Route::post('/verify',   [EmailVerificationController::class, 'verify']);
    });

    // Guest routes
    Route::prefix('guest')->group(function () {
        Route::get('/bookings',                   [Guest\BookingController::class, 'index']);
        Route::get('/bookings/preview',           [Guest\BookingController::class, 'preview']);
        Route::get('/bookings/by-reference/{ref}',[Guest\BookingController::class, 'byReference']);
        Route::get('/bookings/{id}',              [Guest\BookingController::class, 'show']);
        Route::get('/bookings/{id}/checkin-token',[Guest\BookingController::class, 'checkinToken']);
        Route::post('/bookings',                  [Guest\BookingController::class, 'store']);
        Route::delete('/bookings/{id}',           [Guest\BookingController::class, 'cancel']);

        // Payments
        Route::get('/payments/gateways',                  [PaymentController::class, 'gateways']);
        Route::post('/payments/initiate',                 [PaymentController::class, 'initiate']);
        Route::post('/payments/verify-by-reference',      [PaymentController::class, 'verifyByReference']);
        Route::get('/payments/verify/{transactionId}',    [PaymentController::class, 'verify']);
        Route::get('/payments',                           [PaymentController::class, 'index']);
        Route::get('/payments/{id}/receipt',              [PaymentController::class, 'receipt']);

        // Reviews
        Route::post('/reviews',       [Guest\ReviewController::class, 'store']);
        Route::put('/reviews/{id}',   [Guest\ReviewController::class, 'update']);
        Route::delete('/reviews/{id}',[Guest\ReviewController::class, 'destroy']);
    });

    // Admin routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Image upload
        Route::post('/upload/image',   [\App\Http\Controllers\API\Admin\UploadController::class, 'image']);
        Route::delete('/upload/image', [\App\Http\Controllers\API\Admin\UploadController::class, 'delete']);

        Route::apiResource('rooms',    Admin\RoomController::class);
        Route::apiResource('bookings', Admin\BookingController::class);
        Route::patch('bookings/{id}/status', [Admin\BookingController::class, 'updateStatus']);

        // Guests
        Route::get('/guests',                          [\App\Http\Controllers\API\Admin\GuestController::class, 'index']);
        Route::get('/guests/{guest}',                  [\App\Http\Controllers\API\Admin\GuestController::class, 'show']);
        Route::patch('/guests/{guest}/toggle-active',  [\App\Http\Controllers\API\Admin\GuestController::class, 'toggleActive']);
        Route::delete('/guests/{guest}',               [\App\Http\Controllers\API\Admin\GuestController::class, 'destroy']);

        // Payments
        Route::get('/payments',              [\App\Http\Controllers\API\Admin\PaymentController::class, 'index']);
        Route::get('/payments/{id}',         [\App\Http\Controllers\API\Admin\PaymentController::class, 'show']);
        Route::post('/payments/{id}/refund', [\App\Http\Controllers\API\Admin\PaymentController::class, 'refund']);
        Route::post('/payments/{id}/verify', [\App\Http\Controllers\API\Admin\PaymentController::class, 'verify']);

        // Maintenance
        Route::get('/maintenance/rooms-list', [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'roomsList']);
        Route::get('/maintenance/staff-list', [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'staffList']);
        Route::get('/maintenance',            [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'index']);
        Route::post('/maintenance',           [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'store']);
        Route::put('/maintenance/{maintenance}',    [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'update']);
        Route::delete('/maintenance/{maintenance}', [\App\Http\Controllers\API\Admin\MaintenanceController::class, 'destroy']);

        // Reviews
        Route::get('/reviews',                          [\App\Http\Controllers\API\Admin\ReviewController::class, 'index']);
        Route::patch('/reviews/{review}/approve',       [\App\Http\Controllers\API\Admin\ReviewController::class, 'approve']);
        Route::patch('/reviews/{review}/reject',        [\App\Http\Controllers\API\Admin\ReviewController::class, 'reject']);
        Route::delete('/reviews/{review}',              [\App\Http\Controllers\API\Admin\ReviewController::class, 'destroy']);

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\API\Admin\AnalyticsController::class, 'dashboard']);
            Route::get('/revenue',   [\App\Http\Controllers\API\Admin\AnalyticsController::class, 'revenue']);
            Route::get('/occupancy', [\App\Http\Controllers\API\Admin\AnalyticsController::class, 'occupancy']);
            Route::get('/payments',  [\App\Http\Controllers\API\Admin\AnalyticsController::class, 'payments']);
            Route::get('/rooms',     [\App\Http\Controllers\API\Admin\AnalyticsController::class, 'rooms']);
        });
    });
});
