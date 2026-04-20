<?php

namespace App\Services;

use App\Models\Wallet;
use Exception;
use Stripe\StripeClient;
use Stripe\PaymentIntent;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Payment Intent.
     * $amount is multiplied by 100 to convert to cents (smallest currency unit).
     */
    public function createPaymentIntent(float $amount, Wallet $wallet, string $currency = 'usd'): PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100),
                'currency' => $currency,
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                ],
            ]);
        } catch (Exception $e) {
            throw new Exception('Stripe Payment Creation Failed: ' . $e->getMessage());
        }
    }
}
