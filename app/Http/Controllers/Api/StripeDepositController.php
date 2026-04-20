<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;

class StripeDepositController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a Stripe PaymentIntent for wallet deposit.
     * Called from Flutter before presenting the PaymentSheet.
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                (float) $request->amount,
                $wallet,
                'usd' // USD currency as Stripe doesn't support YER
            );

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $request->amount,
                'currency' => 'usd',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل في إنشاء عملية الدفع: ' . $e->getMessage(),
            ], 422);
        }
    }
}
