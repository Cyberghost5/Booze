<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BulkSmsNigeriaService;
use App\Services\PhoneNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PhoneAuthController extends Controller
{
    public function __construct(
        protected BulkSmsNigeriaService $smsService
    ) {}

    /**
     * Fast modal-based phone + password login.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);

        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $validated['phone'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password. Please check your credentials.',
            ], 422);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'message' => 'Logged in successfully!',
        ]);
    }

    /**
     * Fast modal-based registration + OTP trigger.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:10',
            'password' => 'required|string|min:6',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);

        $existingUser = User::where('phone', $normalizedPhone)->first();

        if ($existingUser && $existingUser->phone_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this phone number already exists. Please click Login instead.',
            ], 422);
        }

        $otp = (string) rand(100000, 999999);
        $otpExpiresAt = now()->addMinutes(10);

        if ($existingUser) {
            $existingUser->update([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'phone_otp' => $otp,
                'phone_otp_expires_at' => $otpExpiresAt,
            ]);
            $user = $existingUser;
        } else {
            $cleanDigits = ltrim(preg_replace('/[^0-9]/', '', $normalizedPhone), '+');
            $user = User::create([
                'name' => $validated['name'],
                'email' => "user_{$cleanDigits}@booze.app",
                'phone' => $normalizedPhone,
                'role' => 'consumer',
                'password' => Hash::make($validated['password']),
                'phone_otp' => $otp,
                'phone_otp_expires_at' => $otpExpiresAt,
            ]);
        }

        // Send OTP via BulkSMS Nigeria API
        $smsMessage = "BoozeApp OTP: Your phone verification code is {$otp}. It expires in 10 minutes.";
        $this->smsService->sendSms($user->phone, $smsMessage);

        return response()->json([
            'success' => true,
            'requires_otp' => true,
            'phone' => $user->phone,
            'otp' => $otp, // Included for testing SMS modal display
            'message' => "Verification OTP sent to {$user->phone} via SMS.",
        ]);
    }

    /**
     * Fast modal-based OTP verification & immediate login.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);

        $user = User::where('phone', $normalizedPhone)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found. Please register again.',
            ], 422);
        }

        if (empty($user->phone_otp) || $user->phone_otp !== $validated['otp']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP verification code. Please check the SMS sent to your phone.',
            ], 422);
        }

        if ($user->phone_otp_expires_at && now()->greaterThan($user->phone_otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification OTP has expired. Please click Resend OTP to get a new code.',
            ], 422);
        }

        // Mark phone as verified and log user in
        $user->update([
            'phone_verified_at' => now(),
            'phone_otp' => null,
            'phone_otp_expires_at' => null,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'message' => 'Phone verified & logged in successfully!',
        ]);
    }

    /**
     * Resend verification OTP.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);
        $user = User::where('phone', $normalizedPhone)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 422);
        }

        $otp = (string) rand(100000, 999999);
        $user->update([
            'phone_otp' => $otp,
            'phone_otp_expires_at' => now()->addMinutes(10),
        ]);

        $smsMessage = "BoozeApp OTP: Your new phone verification code is {$otp}. It expires in 10 minutes.";
        $this->smsService->sendSms($user->phone, $smsMessage);

        return response()->json([
            'success' => true,
            'otp' => $otp, // Included for testing SMS modal display
            'message' => 'A new verification OTP has been sent to your phone via SMS.',
        ]);
    }

    /**
     * Send password reset OTP to phone.
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);

        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $validated['phone'])
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this phone number. Please check the number or register.',
            ], 422);
        }

        $otp = (string) rand(100000, 999999);
        $user->update([
            'phone_otp' => $otp,
            'phone_otp_expires_at' => now()->addMinutes(10),
        ]);

        $smsMessage = "BoozeApp OTP: Your password reset code is {$otp}. It expires in 10 minutes.";
        $this->smsService->sendSms($user->phone, $smsMessage);

        return response()->json([
            'success' => true,
            'requires_reset_otp' => true,
            'phone' => $user->phone,
            'otp' => $otp, // Included for testing SMS modal display
            'message' => "Password recovery OTP sent to {$user->phone} via SMS.",
        ]);
    }

    /**
     * Verify password reset OTP & update password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $normalizedPhone = PhoneNumberService::normalize($validated['phone']);

        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $validated['phone'])
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 422);
        }

        if (empty($user->phone_otp) || $user->phone_otp !== $validated['otp']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP verification code. Please check the SMS sent to your phone.',
            ], 422);
        }

        if ($user->phone_otp_expires_at && now()->greaterThan($user->phone_otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset OTP has expired. Please request a new code.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'phone_otp' => null,
            'phone_otp_expires_at' => null,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'message' => 'Password reset successfully! You are now logged in.',
        ]);
    }
}
