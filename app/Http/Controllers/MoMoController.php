<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class MoMoController extends Controller
{
    public function createQrCode()
    {
        // Lấy thông tin từ .env
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $endpoint = env('MOMO_ENDPOINT');
        $returnUrl = env('MOMO_RETURN_URL');
        $notifyUrl = env('MOMO_NOTIFY_URL');
    
        // Thông tin giao dịch
        $orderId = 'order_' . time(); // Mã đơn hàng
        $requestId = 'req_' . time(); // Mã yêu cầu
        $amount = '100000'; // Số tiền (VND)
        $orderInfo = 'Thanh toán đơn hàng #12345';
        $extraData = ''; // Thông tin thêm, có thể để trống
    
        // Tạo chữ ký (signature)
        $rawSignature = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$notifyUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$returnUrl&requestId=$requestId&requestType=captureWallet";
        $signature = hash_hmac('sha256', $rawSignature, $secretKey);
    
        // Tạo dữ liệu gửi đến API MoMo
        $data = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature,
        ];
    
        // Gửi request đến MoMo
        try {
            $client = new Client();
            $response = $client->post($endpoint, ['json' => $data]);
            $body = json_decode($response->getBody(), true); // Chuyển kết quả thành mảng
            // Kiểm tra nếu có URL QR code
            if (isset($body['qrCodeUrl'])) {
                $qrCodeUrl = $body['qrCodeUrl']; // Lấy URL mã QR
                $payUrl = $body['payUrl']; // Lấy URL thanh toán
                $deeplink = $body['deeplink']; // Lấy deeplink
    
                return view('qrcode', compact('qrCodeUrl', 'payUrl', 'deeplink'));
            }
    
            return response()->json(['error' => 'Không tạo được mã QR'], 400);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }
    }
    
}
