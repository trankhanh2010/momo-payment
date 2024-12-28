<?php

namespace App\Services;

use Twilio\Rest\Client;

class OtpService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
    }

    public function sendOtp($phoneNumber, $otp)
    {
        try {
            $this->twilio->messages->create($phoneNumber, [
                'from' => env('TWILIO_PHONE_NUMBER'),
                'body' => "Your OTP is: $otp",
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
