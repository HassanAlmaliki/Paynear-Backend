<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class  AuthController extends Controller
{
    /**
     * Maximum login attempts before lockout.
     */
    private const MAX_LOGIN_ATTEMPTS = 3;

    /**
     * Lockout duration in seconds (5 minutes).
     */
    private const LOCKOUT_DURATION = 300;

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
     * Generate the throttle key for login rate limiting.
     */
    private function throttleKey(Request $request): string
    {
        $phone = $this->formatPhone($request->phone);
        $userType = $request->user_type ?? 'customer';
        return 'login:' . $phone . ':' . $userType;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->formatPhone($request->phone)]);
        }

        $table = $request->user_type === 'merchant' ? 'merchants' : 'users';

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => "required|string|max:20|unique:{$table},phone",
            'password' => 'required|string|min:6|confirmed',
            'user_type' => 'required|string|in:customer,merchant',
            'gender' => 'required_if:user_type,customer|string|in:male,female',
            'firebase_token' => 'required|string',
        ]);

        // Validate firebase token using REST API
        $apiKey = config('services.firebase.api_key');
        if (!$apiKey) {
            throw ValidationException::withMessages(['firebase_token' => ['لم يتم إعداد مفتاح API لفايربيس في السيرفر.']]);
        }

        $response = \Illuminate\Support\Facades\Http::post("https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}", [
            'idToken' => $request->firebase_token,
        ]);

        if (!$response->successful() || !isset($response->json('users')[0]['phoneNumber'])) {
            throw ValidationException::withMessages(['firebase_token' => ['رمز التحقق من Firebase غير صالح أو منتهي الصلاحية']]);
        }

        $verifiedPhone = $response->json('users')[0]['phoneNumber'];

        // Normalize verified phone
        if (str_starts_with($verifiedPhone, '0')) {
            $verifiedPhone = '+967' . substr($verifiedPhone, 1);
        } elseif (str_starts_with($verifiedPhone, '7')) {
            $verifiedPhone = '+967' . $verifiedPhone;
        }

        if ($verifiedPhone !== $request->phone) {
            throw ValidationException::withMessages(['phone' => ['رقم الهاتف المدخل لا يتطابق مع الرقم الذي تم التحقق منه عبر فايربيس.']]);
        }

        if ($request->user_type === 'merchant') {
            $user = Merchant::create([
                'merchant_name' => $request->full_name,
                'phone' => $request->phone,
                'password' => $request->password,
                'status' => 'active',
                'is_verified' => false,
            ]);
        } else {
            $user = User::create([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'password' => $request->password,
                'role' => 'user',
                'gender' => $request->gender,
            ]);
        }

        // Create wallet for the user/merchant
        $user->getOrCreateWallet();

        $token = $user->createToken('mobile-app')->plainTextToken;

        $userModelData = $user->load('wallet', 'profile')->toArray();
        $userModelData['user_type'] = $request->user_type; // Inject user_type for frontend

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',
            'user' => $userModelData,
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
            'user_type' => 'required|string|in:customer,merchant',
        ]);

        $throttleKey = $this->throttleKey($request);

        // Check if the user is rate-limited
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $retryAfter = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'تم تجاوز عدد المحاولات المسموح بها. حاول بعد ' . ceil($retryAfter / 60) . ' دقائق.',
                'retry_after' => $retryAfter,
                'lockout_until' => now()->addSeconds($retryAfter)->toIso8601String(),
            ], 429);
        }

        if ($request->user_type === 'merchant') {
            $user = Merchant::where('phone', $request->phone)->first();
        } else {
            $user = User::where('phone', $request->phone)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Record a failed attempt (decays after LOCKOUT_DURATION seconds)
            RateLimiter::hit($throttleKey, self::LOCKOUT_DURATION);

            $attemptsLeft = RateLimiter::remaining($throttleKey, self::MAX_LOGIN_ATTEMPTS);

            throw ValidationException::withMessages([
                'phone' => ["بيانات الدخول غير صحيحة. المحاولات المتبقية: {$attemptsLeft}"],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'حسابك غير نشط. يرجى التواصل مع الدعم الفني.',
            ], 403);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($throttleKey);

        $token = $user->createToken('mobile-app')->plainTextToken;

        $userModelData = $user->load('wallet', 'profile')->toArray();
        $userModelData['user_type'] = $request->user_type; // Inject user_type for frontend

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => $userModelData,
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

    }

    /**
     * Reset or set initial password.
     */
    public function resetPassword(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->formatPhone($request->phone)]);
        }

        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'user_type' => 'required|string|in:customer,merchant',
            'firebase_token' => 'required|string',
        ]);

        // Validate firebase token using REST API
        $apiKey = config('services.firebase.api_key');
        if (!$apiKey) {
            throw ValidationException::withMessages(['firebase_token' => ['لم يتم إعداد مفتاح API لفايربيس في السيرفر.']]);
        }

        $response = \Illuminate\Support\Facades\Http::post("https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}", [
            'idToken' => $request->firebase_token,
        ]);

        if (!$response->successful() || !isset($response->json('users')[0]['phoneNumber'])) {
            throw ValidationException::withMessages(['firebase_token' => ['رمز التحقق من Firebase غير صالح أو منتهي الصلاحية']]);
        }

        $verifiedPhone = $response->json('users')[0]['phoneNumber'];

        // Normalize verified phone
        if (str_starts_with($verifiedPhone, '0')) {
            $verifiedPhone = '+967' . substr($verifiedPhone, 1);
        } elseif (str_starts_with($verifiedPhone, '7')) {
            $verifiedPhone = '+967' . $verifiedPhone;
        }

        if ($verifiedPhone !== $request->phone) {
            throw ValidationException::withMessages(['phone' => ['رقم الهاتف المدخل لا يتطابق مع الرقم الذي تم التحقق منه عبر فايربيس.']]);
        }

        if ($request->user_type === 'merchant') {
            $user = Merchant::where('phone', $request->phone)->first();
        } else {
            $user = User::where('phone', $request->phone)->first();
        }

        if (!$user) {
            throw ValidationException::withMessages(['phone' => ['المستخدم غير موجود.']]);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'تم تعيين كلمة المرور بنجاح',
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
