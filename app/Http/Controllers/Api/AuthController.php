<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class  AuthController extends Controller
{
    /**
     * Format phone number to standard format.
     */
    private function formatPhone(?string $phone)
    {
        if (blank($phone)) return $phone;
        if (str_starts_with($phone, '+967')) return $phone;
        if (str_starts_with($phone, '0')) return '+967' . substr($phone, 1);
        if (str_starts_with($phone, '7')) return '+967' . $phone;
        return $phone;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->formatPhone($request->phone)]);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|string|in:user,pos',
            'gender' => 'required|string|in:male,female',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'password' => $request->password,
            'role' => $request->role ?? 'user',
            'gender' => $request->gender,
        ]);

        // Create wallet for the user
        $user->getOrCreateWallet();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',
            'user' => $user->load('wallet', 'profile'),
            'token' => $token,
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->formatPhone($request->phone)]);
        }

        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'حسابك غير نشط. يرجى التواصل مع الدعم الفني.',
            ], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => $user->load('wallet', 'profile'),
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('wallet', 'profile');

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة'],
            ]);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    /**
     * Update FCM Token.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'تم تحديث توكن الإشعارات بنجاح',
        ]);
    }
}
