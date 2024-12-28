<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function sendOtp(Request $request)
    {

        $otp = rand(100000, 999999);
        $phone = $request->phone;

        // Lưu OTP vào cache (có thời hạn 5 phút)
        // Cache::put('otp_' . $phone, $otp, now()->addMinutes(5));

        // Gửi OTP qua SMS

        $success = $this->otpService->sendOtp($phone, $otp);

        if ($success) {
            return response()->json(['message' => 'OTP sent successfully.'], 200);
        }

        return response()->json(['message' => 'Failed to send OTP.'], 500);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'otp' => 'required|digits:6',
        ]);

        $cachedOtp = Cache::get('otp_' . $request->phone);

        if (!$cachedOtp) {
            return response()->json(['message' => 'OTP expired or invalid.'], 400);
        }

        if ($cachedOtp == $request->otp) {
            // Xóa OTP sau khi xác nhận
            Cache::forget('otp_' . $request->phone);

            return response()->json(['message' => 'OTP verified successfully.'], 200);
        }

        return response()->json(['message' => 'Incorrect OTP.'], 400);
    }
}
