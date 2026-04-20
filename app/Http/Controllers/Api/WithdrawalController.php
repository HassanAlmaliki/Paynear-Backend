<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    protected WithdrawalService $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Verify withdrawal OTP from mobile app.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|integer|exists:withdrawals,id',
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $withdrawal = Withdrawal::where('id', $request->withdrawal_id)
            ->whereHas('wallet', function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->where('owner_type', 'user');
            })
            ->first();

        if (!$withdrawal) {
            return response()->json([
                'message' => 'عملية السحب غير موجودة',
            ], 404);
        }

        try {
            $result = $this->withdrawalService->verifyAndComplete($withdrawal, $request->otp);

            return response()->json([
                'message' => 'تم السحب بنجاح',
                'withdrawal' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
