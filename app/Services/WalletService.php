<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Deposit money into a wallet.
     */
    public function deposit(Wallet $wallet, float $amount, ?string $type = 'deposit'): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $type) {
            $wallet->credit($amount);

            return Transaction::create([
                'to_wallet_id' => $wallet->id,
                'original_amount' => $amount,
                'commission_amount' => 0,
                'total_amount' => $amount,
                'type' => $type,
                'status' => 'completed',
            ]);
        });
    }

    /**
     * Withdraw money from a wallet (direct, without commission).
     */
    public function withdraw(Wallet $wallet, float $amount): Transaction
    {
        if (!$wallet->hasSufficientBalance($amount)) {
            throw new \Exception('الرصيد غير كافٍ');
        }

        return DB::transaction(function () use ($wallet, $amount) {
            $wallet->debit($amount);

            return Transaction::create([
                'from_wallet_id' => $wallet->id,
                'original_amount' => $amount,
                'commission_amount' => 0,
                'total_amount' => $amount,
                'type' => 'withdrawal',
                'status' => 'completed',
            ]);
        });
    }

    /**
     * Transfer money between two wallets (P2P).
     */
    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $note = null): Transaction
    {
        if (!$fromWallet->hasSufficientBalance($amount)) {
            throw new \Exception('الرصيد غير كافٍ');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new \Exception('لا يمكن التحويل لنفس المحفظة');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $note) {
            $fromWallet->debit($amount);
            $toWallet->credit($amount);

            return Transaction::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'original_amount' => $amount,
                'commission_amount' => 0,
                'total_amount' => $amount,
                'type' => 'p2p',
                'status' => 'completed',
                'note' => $note,
            ]);
        });
    }

    /**
     * Find a wallet by the owner's phone number.
     */
    public function findWalletByPhone(string $phone): ?Wallet
    {
        // Format phone number
        if (str_starts_with($phone, '0')) {
            $phone = '+967' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7')) {
            $phone = '+967' . $phone;
        }

        // Check users first
        $user = User::where('phone', $phone)->first();
        if ($user) {
            return $user->getOrCreateWallet();
        }

        // Check merchants
        $merchant = Merchant::where('phone', $phone)->first();
        if ($merchant) {
            return $merchant->getOrCreateWallet();
        }

        return null;
    }

    /**
     * Process NFC device payment.
     */
    public function processPayment(Wallet $fromWallet, Wallet $toWallet, float $amount): Transaction
    {
        if (!$fromWallet->hasSufficientBalance($amount)) {
            throw new \Exception('الرصيد غير كافٍ');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount) {
            $fromWallet->debit($amount);
            $toWallet->credit($amount);

            return Transaction::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'original_amount' => $amount,
                'commission_amount' => 0,
                'total_amount' => $amount,
                'type' => 'payment',
                'status' => 'completed',
            ]);
        });
    }
}
