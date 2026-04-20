<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DevicePaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process a payment from POS device.
     * Authenticated via API key (no Sanctum).
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'nfc_uid' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $transaction = $this->paymentService->processDevicePayment(
                $request->api_key,
                $request->nfc_uid,
                (float) $request->amount
            );

            return response()->json([
                'message' => __('messages.payment_successful'),
                'transaction' => [
                    'reference' => $transaction->reference,
                    'amount' => $transaction->original_amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
