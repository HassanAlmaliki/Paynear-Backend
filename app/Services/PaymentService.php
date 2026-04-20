<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Process an NFC payment from POS device.
     */
    public function processDevicePayment(string $apiKey, string $nfcUid, float $amount): Transaction
    {
        // 1. Authenticate the device
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

        // 3. Get merchant's wallet
        $merchant = $device->merchant;
        $toWallet = $merchant->getOrCreateWallet();

        if ($toWallet->status !== 'active') {
            throw new \Exception('محفظة التاجر غير نشطة');
        }

        // 4. Process the payment
        return $this->walletService->processPayment($fromWallet, $toWallet, $amount);
    }
}
