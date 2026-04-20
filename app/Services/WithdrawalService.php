<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\AgentCommissionTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WithdrawalService
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Initiate a withdrawal request.
     * Creates a pending withdrawal and generates an OTP.
     */
    public function initiate(Agent $agent, Wallet $wallet, float $requestedAmount): array
    {
        $commission = $this->commissionService->calculateCommission($agent, $requestedAmount);
        $totalDeducted = $requestedAmount + $commission;

        // Check if the wallet has sufficient balance (including commission)
        if (!$wallet->hasSufficientBalance($totalDeducted)) {
            throw new \Exception(__('messages.insufficient_balance', ['requested' => $requestedAmount, 'commission' => $commission, 'total' => $totalDeducted]));
        }

        $phone = $wallet->owner ? $wallet->owner->phone : null;
        if (!$phone) {
            throw new \Exception(__('messages.phone_not_found'));
        }

        // Format phone to international format (assumes Yemen +967) if local
        $formattedPhone = preg_match('/^7\d{8}$/', $phone) ? '+967' . $phone : $phone;

        $apiKey = env('FIREBASE_API_KEY');
        if (!$apiKey) {
            throw new \Exception('FIREBASE_API_KEY is not configured.');
        }

        // Send OTP via Google Identity Toolkit
        $response = \Illuminate\Support\Facades\Http::post("https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key={$apiKey}", [
            'phoneNumber' => $formattedPhone,
        ]);

        if (!$response->successful() || !isset($response->json()['sessionInfo'])) {
            $error = $response->json()['error']['message'] ?? 'Unknown error';
            \Illuminate\Support\Facades\Log::error("Firebase Send SMS Error: " . $error);
            throw new \Exception(__('messages.firebase_send_error', ['error' => $error]));
        }

        $sessionInfo = $response->json()['sessionInfo'];

        $withdrawal = DB::transaction(function () use ($agent, $wallet, $requestedAmount, $commission, $totalDeducted, $sessionInfo) {
            return Withdrawal::create([
                'agent_id' => $agent->id,
                'wallet_id' => $wallet->id,
                'requested_amount' => $requestedAmount,
                'commission_amount' => $commission,
                'total_deducted_amount' => $totalDeducted,
                'commission_type' => $agent->commission_type,
                'commission_value' => $agent->commission_value,
                'status' => 'pending',
                'verification_code' => Hash::make(Str::random(10)), // Placeholder value
                'firebase_session_info' => $sessionInfo,
                'expires_at' => now()->addMinutes(15),
            ]);
        });

        return [
            'withdrawal' => $withdrawal,
            'commission_amount' => $commission,
            'total_deducted' => $totalDeducted,
        ];
    }

    /**
     * Verify OTP and complete the withdrawal.
     */
    public function verifyAndComplete(Withdrawal $withdrawal, string $otp): Withdrawal
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception(__('messages.withdrawal_not_pending'));
        }

        if ($withdrawal->isExpired()) {
            $withdrawal->update(['status' => 'failed']);
            throw new \Exception(__('messages.verification_expired'));
        }

        if (!$withdrawal->firebase_session_info) {
            throw new \Exception(__('messages.invalid_session'));
        }

        $apiKey = env('FIREBASE_API_KEY');
        
        // Authenticate with Firebase using sessionInfo and code
        $response = \Illuminate\Support\Facades\Http::post("https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key={$apiKey}", [
            'sessionInfo' => $withdrawal->firebase_session_info,
            'code' => $otp,
        ]);

        if (!$response->successful() || !isset($response->json()['idToken'])) {
            $error = $response->json()['error']['message'] ?? __('messages.invalid_code');
            throw new \Exception(__('messages.verification_failed', ['error' => $error]));
        }

        $idToken = $response->json()['idToken'];

        try {
            // Double check cryptographically with Kreait Firebase SDK
            // Resolve from the service container instead of Facade to avoid static analysis unresolvable warnings.
            $verifiedIdToken = app('firebase.auth')->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            if (!$uid) {
                throw new \Exception(__('messages.no_valid_id'));
            }
        } catch (\Exception $e) {
            throw new \Exception(__('messages.admin_sdk_auth_failed', ['error' => $e->getMessage()]));
        }

        return DB::transaction(function () use ($withdrawal) {
            $wallet = $withdrawal->wallet;
            $agent = $withdrawal->agent;

            // Verify balance again
            if (!$wallet->hasSufficientBalance((float) $withdrawal->total_deducted_amount)) {
                $withdrawal->update(['status' => 'failed']);
                throw new \Exception(__('messages.insufficient_balance_simple'));
            }

            // Deduct from user's wallet (withdrawal + commission)
            $wallet->debit((float) $withdrawal->total_deducted_amount);

            // Create the transaction record
            Transaction::create([
                'from_wallet_id' => $wallet->id,
                'original_amount' => $withdrawal->requested_amount,
                'commission_amount' => $withdrawal->commission_amount,
                'total_amount' => $withdrawal->total_deducted_amount,
                'type' => 'withdrawal',
                'status' => 'completed',
            ]);

            // Credit agent's commission wallet
            $agentWallet = $agent->getOrCreateAgentWallet();
            $agentWallet->credit((float) $withdrawal->commission_amount);

            // Record commission transaction
            AgentCommissionTransaction::create([
                'agent_id' => $agent->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->commission_amount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Update withdrawal status
            $withdrawal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $withdrawal->fresh();
        });
    }

    /**
     * Cancel a pending withdrawal.
     */
    public function cancel(Withdrawal $withdrawal): Withdrawal
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception(__('messages.cannot_cancel_unpending'));
        }

        $withdrawal->update(['status' => 'cancelled']);

        return $withdrawal;
    }
}
