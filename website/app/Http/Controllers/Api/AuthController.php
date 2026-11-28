<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Sign in to the app with a UAE mobile number and a one-time code (OTP). */
class AuthController extends Controller
{
    private function phone(Request $request): string
    {
        $request->validate(['phone' => 'required|string|max:25']);
        return Customer::normalizePhone($request->input('phone'))
            ?? throw ValidationException::withMessages(['phone' => __('site.auth.bad_phone')]);
    }

    /** POST /auth/otp {phone} */
    public function requestCode(Request $request, OtpSender $otp)
    {
        $phone = $this->phone($request);
        $gap = 'otp-gap:'.$phone;
        $hour = 'otp-hour:'.$phone;
        if (RateLimiter::tooManyAttempts($gap, 1)) {
            throw ValidationException::withMessages(['phone' => __('site.auth.wait', ['seconds' => RateLimiter::availableIn($gap)])]);
        }
        if (RateLimiter::tooManyAttempts($hour, (int) config('otp.max_per_hour'))) {
            throw ValidationException::withMessages(['phone' => __('site.auth.too_many')]);
        }

        try {
            $otp->send($phone, app()->getLocale());
        } catch (OtpException) {
            throw ValidationException::withMessages(['phone' => __('site.auth.send_failed')]);
        }
        RateLimiter::hit($gap, (int) config('otp.resend_seconds'));
        RateLimiter::hit($hour, 3600);

        return response()->json(['sent' => true, 'phone' => '+'.$phone, 'resend_in' => (int) config('otp.resend_seconds')]);
    }

    /** POST /auth/verify {phone, code, device?} -> {token, customer, is_new} */
    public function verify(Request $request, OtpSender $otp)
    {
        $phone = $this->phone($request);
        $data = $request->validate(['code' => 'required|digits_between:4,8', 'device' => 'nullable|string|max:100']);

        $tries = 'otp-verify:'.$phone;
        if (RateLimiter::tooManyAttempts($tries, (int) config('otp.max_attempts')) || ! $otp->verify($phone, $data['code'])) {
            RateLimiter::hit($tries, 900);
            throw ValidationException::withMessages(['code' => __('site.auth.bad_code')]);
        }
        RateLimiter::clear($tries);

        $customer = Customer::firstOrCreate(['phone' => $phone]);
        $token = $customer->issueToken($data['device'] ?? $request->userAgent());

        return response()->json(['token' => $token, 'customer' => $customer->toApi(), 'is_new' => $customer->wasRecentlyCreated]);
    }

    /** POST /auth/logout: ends this device's sign-in only. */
    public function logout(Request $request)
    {
        $request->attributes->get('customer_token')->delete();
        return response()->json(['ok' => true]);
    }
}
