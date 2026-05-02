<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Card;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet balance.
     * Returns full wallet object matching Flutter WalletModel expectations.
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'user_id' => (string) $wallet->owner_id,
                'balance' => (float) $wallet->balance,
                'limit_amount' => 500000.00, // Configurable limit
                'frozen' => $wallet->status === 'frozen',
                'currency' => $wallet->currency,
                'status' => $wallet->status,
            ],
        ]);
    }

    /**
     * P2P transfer with optional note.
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $fromWallet = $user->getOrCreateWallet();

        $toWallet = $this->walletService->findWalletByPhone($request->phone);

        if (!$toWallet) {
            return response()->json([
                'message' => 'لم يتم العثور على مستخدم بهذا الرقم',
            ], 404);
        }

        if ($fromWallet->id === $toWallet->id) {
            return response()->json([
                'message' => 'لا يمكن التحويل لنفس المحفظة',
            ], 422);
        }

        try {
            $transaction = $this->walletService->transfer(
                $fromWallet,
                $toWallet,
                (float) $request->amount,
                $request->note
            );

            return response()->json([
                'message' => 'تم التحويل بنجاح',
                'transaction' => $transaction,
                'new_balance' => (float) $fromWallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get transaction history.
     * Returns format matching Flutter Transaction entity: id, agentName, amount, type, date
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $query = Transaction::where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id);

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->with(['fromWallet.owner', 'toWallet.owner'])
            ->latest()
            ->paginate(20);

        // Transform to match Flutter entity format
        $transformed = $transactions->through(function ($tx) use ($wallet) {
            $isIncoming = $tx->to_wallet_id === $wallet->id;

            // Determine the other party's name
            if ($isIncoming && $tx->fromWallet && $tx->fromWallet->owner) {
                $otherParty = $tx->fromWallet->owner;
            } elseif (!$isIncoming && $tx->toWallet && $tx->toWallet->owner) {
                $otherParty = $tx->toWallet->owner;
            } else {
                $otherParty = null;
            }

            $agentName = $otherParty
                ? ($otherParty->full_name ?? $otherParty->merchant_name ?? 'مستخدم PayNear')
                : ($tx->type === 'deposit' ? "تم إيداع " . (float)$tx->original_amount : 'عملية');

            // Map backend type to Flutter-friendly type
            $flutterType = match ($tx->type) {
                'deposit' => 'incoming',
                'p2p' => $isIncoming ? 'incoming' : 'outgoing',
                'payment' => $isIncoming ? 'incoming' : 'outgoing',
                'withdrawal' => 'outgoing',
                default => $isIncoming ? 'incoming' : 'outgoing',
            };

            return [
                'id' => $tx->id,
                'agentName' => $agentName,
                'amount' => (float) $tx->original_amount,
                'type' => $flutterType,
                'date' => $tx->created_at->format('Y-m-d H:i'),
                'reference' => $tx->reference,
                'note' => $tx->note,
                'status' => $tx->status,
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Lookup recipient by phone (for P2P).
     */
    public function lookupRecipient(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $wallet = $this->walletService->findWalletByPhone($request->phone);

        if (!$wallet) {
            return response()->json([
                'message' => 'لم يتم العثور على مستخدم بهذا الرقم',
            ], 404);
        }

        $owner = $wallet->owner;
        $name = $owner instanceof User ? $owner->full_name : ($owner->merchant_name ?? 'مستخدم');

        return response()->json([
            'name' => $name,
            'phone' => $request->phone,
        ]);
    }

    /**
     * Link an NFC card to the authenticated user's wallet.
     */
    public function linkCard(Request $request)
    {
        $request->validate([
            'nfc_uid' => 'required|string',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Check if card is already linked to another wallet
        $existingCard = Card::where('nfc_uid', $request->nfc_uid)->first();

        if ($existingCard) {
            if ($existingCard->wallet_id === $wallet->id) {
                return response()->json([
                    'message' => 'هذه البطاقة مرتبطة بحسابك بالفعل',
                ], 422);
            }
            return response()->json([
                'message' => 'هذه البطاقة مرتبطة بحساب آخر',
            ], 422);
        }

        // Create new card for this wallet
        $card = Card::create([
            'nfc_uid' => $request->nfc_uid,
            'wallet_id' => $wallet->id,
            'status' => 'active',
            'expires_at' => now()->addYears(5),
        ]);

        return response()->json([
            'message' => 'تم ربط البطاقة بحسابك بنجاح',
            'card' => $card,
        ]);
    }
}
