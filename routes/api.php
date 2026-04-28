<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DevicePaymentController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\StripeDepositController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Paynear
|--------------------------------------------------------------------------
*/

// Public routes (no auth)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Device payment (API Key auth, not Sanctum)
Route::post('/device/process-payment', [DevicePaymentController::class, 'processPayment']);

// Webhooks
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Wallet
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::post('/transfer/p2p', [WalletController::class, 'transfer']);
    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/lookup', [WalletController::class, 'lookupRecipient']);
    Route::post('/wallet/link-card', [WalletController::class, 'linkCard']);

    // Stripe Deposit
    Route::post('/wallet/deposit/stripe', [StripeDepositController::class, 'createPaymentIntent']);

    // KYC
    Route::post('/kyc/submit', [KycController::class, 'submit']);
    Route::get('/kyc/status', [KycController::class, 'status']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Withdrawal OTP verification (from mobile app)
    Route::post('/withdrawal/verify-otp', [WithdrawalController::class, 'verifyOtp']);
});
