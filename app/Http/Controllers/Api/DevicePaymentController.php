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
     *
     * Supports two payment methods:
     *  - RFID card:  payment_method=rfid  + nfc_uid
     *  - HCE phone:  payment_method=hce   + payment_token (Sanctum bearer token)
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'api_key'        => 'required|string',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'sometimes|string|in:rfid,hce',
            'nfc_uid'        => 'required_without:payment_token|nullable|string',
            'payment_token'  => 'required_without:nfc_uid|nullable|string',
        ]);

        try {
            $method = $request->input('payment_method', 'rfid');

            if ($method === 'hce' && $request->filled('payment_token')) {
                $transaction = $this->paymentService->processHcePayment(
                    $request->api_key,
                    $request->payment_token,
                    (float) $request->amount
                );
            } else {
                $transaction = $this->paymentService->processDevicePayment(
                    $request->api_key,
                    $request->nfc_uid,
                    (float) $request->amount
                );
            }

            return response()->json([
                'message' => __('messages.payment_successful'),
                'transaction' => [
                    'reference'  => $transaction->reference,
                    'amount'     => $transaction->original_amount,
                    'status'     => $transaction->status,
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
