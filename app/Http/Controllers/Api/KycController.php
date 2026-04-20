<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\Request;

class KycController extends Controller
{
    protected KycService $kycService;

    public function __construct(KycService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * Submit KYC documents.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'id_type' => 'required|string|in:national_id,passport,driving_license',
            'id_number' => 'required|string|max:50',
            'id_expiry_date' => 'required|date|after:today',
            'nationality' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'dob' => 'required|date|before:today',
            'id_front_image' => 'required|image|max:5120', // 5MB max
            'id_back_image' => 'required|image|max:5120',
        ]);

        $user = $request->user();

        // Check if already submitted
        if ($user->profile && $user->profile->verification_status === 'pending_verification') {
            return response()->json([
                'message' => 'طلب التحقق قيد المراجعة',
            ], 422);
        }

        if ($user->profile && $user->profile->verification_status === 'approved') {
            return response()->json([
                'message' => 'حسابك مفعل بالفعل',
            ], 422);
        }

        $profile = $this->kycService->submit(
            $user,
            $request->only(['id_type', 'id_number', 'id_expiry_date', 'nationality', 'address', 'dob']),
            $request->file('id_front_image'),
            $request->file('id_back_image')
        );

        return response()->json([
            'message' => 'تم إرسال طلب التحقق. سيتم مراجعته في أقرب وقت.',
            'profile' => $profile,
        ]);
    }

    /**
     * Get KYC status.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'status' => 'not_submitted',
                'message' => 'لم يتم إرسال طلب التحقق بعد',
            ]);
        }

        return response()->json([
            'status' => $profile->verification_status,
            'rejection_reason' => $profile->rejection_reason,
        ]);
    }
}
