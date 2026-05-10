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
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Device payment (API Key auth, not Sanctum)
Route::post('/device/process-payment', [DevicePaymentController::class, 'processPayment']);

// Webhooks
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle']);

// Temporary: Firebase diagnostic (DELETE after testing)
Route::get('/debug/firebase', function () {
    $result = ['step' => 'start'];
    
    // 1. Check env var
    $creds = env('FIREBASE_CREDENTIALS');
    $result['credentials_type'] = $creds ? (str_starts_with(trim($creds), '{') ? 'JSON_STRING' : 'FILE_PATH:' . $creds) : 'NOT_SET';
    
    // 2. Check if file exists (if it's a path)
    if ($creds && !str_starts_with(trim($creds), '{')) {
        $result['file_exists'] = file_exists(base_path($creds));
    }
    
    // 3. Try to create Messaging instance
    try {
        $messaging = app(\Kreait\Firebase\Contract\Messaging::class);
        $result['messaging'] = 'OK';
    } catch (\Exception $e) {
        $result['messaging'] = 'ERROR: ' . $e->getMessage();
        return response()->json($result);
    }
    
    // 4. Try sending to a test token
    try {
        $token = \DB::table('users')->whereNotNull('fcm_token')->value('fcm_token');
        $result['has_token'] = $token ? true : false;
        if ($token) {
            $msg = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create('تشخيص', 'اختبار من Vercel'));
            $messaging->send($msg);
            $result['send'] = 'OK';
        }
    } catch (\Exception $e) {
        $result['send'] = 'ERROR: ' . $e->getMessage();
    }
    
    return response()->json($result);
});

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
    Route::get('/wallet/cards', [WalletController::class, 'cards']);
    Route::post('/wallet/link-card', [WalletController::class, 'linkCard']);
    Route::delete('/wallet/cards/{id}', [WalletController::class, 'unlinkCard']);

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
