<?php

namespace App\Services\Otp;

use App\Models\CustomerOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * For testing before MSG91 is set up: the code goes to storage/logs instead of a phone.
 * OTP_TEST_CODE fixes the code (e.g. 123456) so testers can sign in. Never use in production.
 */
class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $locale): void
    {
        $code = (string) (config('otp.test_code') ?: random_int(100000, 999999));
        CustomerOtp::where('phone', $phone)->whereNull('used_at')->update(['used_at' => now()]);
        CustomerOtp::create(['phone' => $phone, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes'))]);
        Log::info("OTP for {$phone}: {$code}");
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = CustomerOtp::where('phone', $phone)->whereNull('used_at')->where('expires_at', '>', now())->latest('id')->first();
        if (! $otp || $otp->attempts >= (int) config('otp.max_attempts')) return false;
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            return false;
        }
        $otp->update(['used_at' => now()]);
        return true;
    }
}
