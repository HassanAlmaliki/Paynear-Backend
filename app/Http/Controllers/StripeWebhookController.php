<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\WalletService;
use App\Events\WalletUpdated;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, WalletService $walletService)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            Log::error('Stripe Webhook Signature Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload or signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $walletId = $paymentIntent->metadata->wallet_id ?? null;
            
            if ($walletId) {
                $wallet = Wallet::find($walletId);
                if ($wallet) {
                    $amount = $paymentIntent->amount / 100; // Convert cents to standard unit
                    
                    try {
                        // Using WalletService database transaction securely
                        $transaction = $walletService->deposit($wallet, $amount, 'deposit');
                        Log::info("Stripe Deposit successful for Wallet ID: {$wallet->id}, Amount: {$amount}");

                        // Trigger the Echo/Reverb WebSocket Event
                        $ownerId = $wallet->owner_id;
                        broadcast(new \App\Events\WalletUpdated($ownerId, $wallet->balance));

                    } catch (Exception $e) {
                        Log::error("Stripe Deposit failed for Wallet ID: {$wallet->id}. Error: " . $e->getMessage());
                        return response()->json(['error' => 'Deposit processing failed'], 500);
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
