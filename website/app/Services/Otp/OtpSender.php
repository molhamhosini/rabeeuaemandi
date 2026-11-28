<?php

namespace App\Services\Otp;

interface OtpSender
{
    /** Send a code to 9715XXXXXXXX. Throws OtpException when the provider refuses. */
    public function send(string $phone, string $locale): void;

    /** True when the code the customer typed is right. */
    public function verify(string $phone, string $code): bool;
}
