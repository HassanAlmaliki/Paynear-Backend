<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Device;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class PaymentService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Process an NFC payment from POS device (physical RFID card).
     */
    public function processDevicePayment(string $apiKey, string $nfcUid, float $amount): Transaction
    {
        // 1. Authenticate the device
        $device = $this->authenticateDevice($apiKey);

        // 2. Find the card/wallet by NFC UID
        $card = Card::where('nfc_uid', $nfcUid)->first();
        if (!$card) {
            throw new \Exception('بطاقة غير مسجلة');
        }

        if (!$card->isActive()) {
            throw new \Exception('البطاقة غير صالحة أو منتهية');
        }

        $fromWallet = $card->wallet;
        if (!$fromWallet || $fromWallet->status !== 'active') {
            throw new \Exception('محفظة المستخدم غير نشطة');
        }

        // 3. Get merchant's wallet & process
        return $this->processToMerchant($device, $fromWallet, $amount);
    }

    /**
     * Process an HCE payment from POS device (phone tap with Sanctum token).
     */
    public function processHcePayment(string $apiKey, string $paymentToken, float $amount): Transaction
    {
        // 1. Authenticate the device
        $device = $this->authenticateDevice($apiKey);

        // 2. Resolve the user from the Sanctum bearer token
        $user = $this->resolveUserFromToken($paymentToken);

        // 3. Get user's wallet
        $fromWallet = $user->wallet;
        if (!$fromWallet || $fromWallet->status !== 'active') {
            throw new \Exception('محفظة المستخدم غير نشطة');
        }

        // 4. Get merchant's wallet & process
        return $this->processToMerchant($device, $fromWallet, $amount);
    }

    /**
     * Authenticate the POS device by API key.
     */
    private function authenticateDevice(string $apiKey): Device
    {
        $device = Device::where('api_key', $apiKey)->first();
        if (!$device) {
            throw new \Exception('جهاز غير مسجل');
        }

        if ($device->status !== 'active') {
            throw new \Exception('الجهاز غير نشط');
        }

        if (!$device->merchant_id) {
            throw new \Exception('الجهاز غير مرتبط بتاجر');
        }

        return $device;
    }

    /**
     * Resolve a User from a Sanctum plain-text token.
     *
     * Sanctum tokens have the format "{id}|{raw_hash}".
     * PersonalAccessToken::findToken() handles hashing
     * and looking up the token record.
     */
    private function resolveUserFromToken(string $plainToken): User
    {
        $accessToken = PersonalAccessToken::findToken($plainToken);

        if (!$accessToken) {
            throw new \Exception('رمز الدفع غير صالح أو منتهي الصلاحية');
        }

        $user = $accessToken->tokenable;

        if (!$user || !($user instanceof User)) {
            throw new \Exception('لم يتم العثور على المستخدم');
        }

        if ($user->status !== 'active') {
            throw new \Exception('حساب المستخدم غير نشط');
        }

        return $user;
    }

    /**
     * Transfer funds from user's wallet to merchant's wallet.
     */
    private function processToMerchant(Device $device, $fromWallet, float $amount): Transaction
    {
        $merchant = $device->merchant;
        $toWallet = $merchant->getOrCreateWallet();

        if ($toWallet->status !== 'active') {
            throw new \Exception('محفظة التاجر غير نشطة');
        }

        return $this->walletService->processPayment($fromWallet, $toWallet, $amount);
    }
}
