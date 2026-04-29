<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\AgentCommissionTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WithdrawalService
{
    protected CommissionService $commissionService;

    /**
     * Firebase test phone numbers with their predetermined verification codes.
     * These match the test numbers configured in Firebase Console.
     * Format: 'international_phone' => 'verification_code'
     */
    protected static array $testPhoneNumbers = [
        '+967774845570' => '705584',
        '+967777391592' => '123456',
        '+967713489161' => '654321',
        '+967776311002' => '200311',
        '+967777771032' => '777771',
    ];

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Check if a phone number is a Firebase test number.
     */
    protected function isTestPhoneNumber(string $phone): bool
    {
        return array_key_exists($phone, self::$testPhoneNumbers);
    }

    /**
     * Initiate a withdrawal request.
     * Creates a pending withdrawal and sends an OTP to the user's phone.
     *
     * For Firebase test numbers: uses predetermined codes (no SMS sent).
     * For real numbers: sends OTP via Firebase Identity Toolkit REST API.
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

        // Determine if this is a test number or real number
        if ($this->isTestPhoneNumber($formattedPhone)) {
            return $this->initiateWithTestNumber($agent, $wallet, $requestedAmount, $commission, $totalDeducted, $formattedPhone);
        }

        return $this->initiateWithFirebase($agent, $wallet, $requestedAmount, $commission, $totalDeducted, $formattedPhone);
    }

    /**
     * Initiate withdrawal for Firebase test numbers.
     * Uses predetermined codes without calling the Firebase REST API.
     */
    protected function initiateWithTestNumber(
        Agent $agent, Wallet $wallet, float $requestedAmount,
        float $commission, float $totalDeducted, string $formattedPhone
    ): array {
        $testCode = self::$testPhoneNumbers[$formattedPhone];

        // Use a special session marker to identify test number flows
        $sessionInfo = 'test_session_' . Str::random(32);

        Log::info("Withdrawal OTP: Using test number {$formattedPhone} with code {$testCode}");

        $withdrawal = DB::transaction(function () use ($agent, $wallet, $requestedAmount, $commission, $totalDeducted, $sessionInfo, $testCode) {
            return Withdrawal::create([
                'agent_id' => $agent->id,
                'wallet_id' => $wallet->id,
                'requested_amount' => $requestedAmount,
                'commission_amount' => $commission,
                'total_deducted_amount' => $totalDeducted,
                'commission_type' => $agent->commission_type,
                'commission_value' => $agent->commission_value,
                'status' => 'pending',
                'verification_code' => Hash::make($testCode),
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
     * Initiate withdrawal using Firebase Identity Toolkit REST API.
     * Used for real (non-test) phone numbers.
     */
    protected function initiateWithFirebase(
        Agent $agent, Wallet $wallet, float $requestedAmount,
        float $commission, float $totalDeducted, string $formattedPhone
    ): array {
        $apiKey = config('services.firebase.api_key');
        if (!$apiKey) {
            throw new \Exception('FIREBASE_API_KEY is not configured.');
        }

        // Send OTP via Google Identity Toolkit
        $response = Http::post("https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key={$apiKey}", [
            'phoneNumber' => $formattedPhone,
        ]);

        if (!$response->successful() || !isset($response->json()['sessionInfo'])) {
            $error = $response->json()['error']['message'] ?? 'Unknown error';
            Log::error("Firebase Send SMS Error: " . $error);
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
                'verification_code' => Hash::make(Str::random(10)),
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
     *
     * For test numbers: verifies code against the stored hash.
     * For real numbers: verifies via Firebase REST API.
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

        // Determine if this was a test number flow
        $isTestSession = str_starts_with($withdrawal->firebase_session_info, 'test_session_');

        if ($isTestSession) {
            $this->verifyTestOtp($withdrawal, $otp);
        } else {
            $this->verifyFirebaseOtp($withdrawal, $otp);
        }

        // OTP verified successfully — complete the withdrawal
        return $this->completeWithdrawal($withdrawal);
    }

    /**
     * Verify OTP for test phone numbers using the stored hash.
     */
    protected function verifyTestOtp(Withdrawal $withdrawal, string $otp): void
    {
        if (!Hash::check($otp, $withdrawal->verification_code)) {
            throw new \Exception(__('messages.verification_failed', ['error' => __('messages.invalid_code')]));
        }

        Log::info("Withdrawal #{$withdrawal->id}: Test OTP verified successfully.");
    }

    /**
     * Verify OTP via Firebase Identity Toolkit REST API.
     */
    protected function verifyFirebaseOtp(Withdrawal $withdrawal, string $otp): void
    {
        $apiKey = config('services.firebase.api_key');

        $response = Http::post("https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key={$apiKey}", [
            'sessionInfo' => $withdrawal->firebase_session_info,
            'code' => $otp,
        ]);

        if (!$response->successful() || !isset($response->json()['idToken'])) {
            $error = $response->json()['error']['message'] ?? __('messages.invalid_code');
            throw new \Exception(__('messages.verification_failed', ['error' => $error]));
        }

        Log::info("Withdrawal #{$withdrawal->id}: Firebase OTP verified successfully.");
    }

    /**
     * Complete the withdrawal after OTP verification.
     * Deducts balance, records transactions, credits agent commission.
     */
    protected function completeWithdrawal(Withdrawal $withdrawal): Withdrawal
    {
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
