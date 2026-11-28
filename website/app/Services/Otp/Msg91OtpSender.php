<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MSG91 OTP API (v5). MSG91 makes the code, sends it with the approved template and
 * checks it, so we never store codes for this driver.
 * Needs MSG91_AUTH_KEY and MSG91_OTP_TEMPLATE_ID in .env.
 */
class Msg91OtpSender implements OtpSender
{
    private const BASE = 'https://control.msg91.com/api/v5/otp';

    public function send(string $phone, string $locale): void
    {
        $res = Http::withHeaders(['authkey' => config('otp.msg91.auth_key')])->acceptJson()->timeout(15)
            ->post(self::BASE.'?'.http_build_query([
                'template_id' => config('otp.msg91.template_id'),
                'mobile' => $phone,
                'otp_length' => 6,
                'otp_expiry' => (int) config('otp.expiry_minutes'),
            ]));
        if (! $res->successful() || $res->json('type') !== 'success') {
            Log::warning('MSG91 send failed', ['phone' => $phone, 'status' => $res->status(), 'body' => $res->json() ?? $res->body()]);
            throw new OtpException('send_failed');
        }
    }

    public function verify(string $phone, string $code): bool
    {
        $res = Http::withHeaders(['authkey' => config('otp.msg91.auth_key')])->acceptJson()->timeout(15)
            ->get(self::BASE.'/verify', ['mobile' => $phone, 'otp' => $code]);
        if ($res->successful() && $res->json('type') === 'success') return true;
        Log::info('MSG91 verify refused', ['phone' => $phone, 'body' => $res->json() ?? $res->body()]);
        return false;
    }
}
