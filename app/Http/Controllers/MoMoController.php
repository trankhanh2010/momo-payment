<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Builder\Builder;

class MoMoController extends Controller
{
    public function createQrCode()
    {
        // Lấy thông tin từ .env
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $endpoint = env('MOMO_ENDPOINT') . '/v2/gateway/api/create';
        $returnUrl = env('MOMO_RETURN_URL');
        $notifyUrl = env('MOMO_NOTIFY_URL');

        // Thông tin giao dịch
        $orderId = 'order_' . time(); // Mã đơn hàng
        $requestId = 'req_' . time(); // Mã yêu cầu
        $amount = '10000'; // Số tiền (VND)
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
        dump($orderId);
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

                // Tạo mã QR
                $qrCode = Builder::create()
                    ->data($qrCodeUrl)
                    ->size(300)
                    ->build();

                // Lưu QR code vào file hoặc base64
                $qrCodeImage = $qrCode->getDataUri();

                return view('qrcode', compact('qrCodeImage', 'payUrl', 'deeplink'));
            }

            return response()->json(['error' => 'Không tạo được mã QR'], 400);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }
    }
    public function checkTransactionStatus($orderId)
    {
        // Lấy thông tin từ .env
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $endpoint = env('MOMO_ENDPOINT') . '/v2/gateway/api/query'; // URL kiểm tra trạng thái giao dịch

        $requestId = 'req_' . time(); // Mã yêu cầu mới để kiểm tra giao dịch

        // Tạo chữ ký (signature)
        $rawSignature = "accessKey=$accessKey&orderId=$orderId&partnerCode=$partnerCode&requestId=$requestId";
        $signature = hash_hmac('sha256', $rawSignature, $secretKey);

        // Tạo dữ liệu gửi đến API MoMo
        $data = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'signature' => $signature,
        ];

        // Gửi request đến API MoMo
        try {
            $client = new Client();
            $response = $client->post($endpoint, ['json' => $data]);
            $body = json_decode($response->getBody(), true); // Chuyển kết quả thành mảng
            dd($body);
            if ($body['resultCode'] === 0) {
                // Giao dịch thành công
                return response()->json([
                    'status' => 'success',
                    'message' => 'Giao dịch thành công',
                    'data' => $body,
                ]);
            } else {
                // Giao dịch thất bại hoặc chưa hoàn thành
                return response()->json([
                    'status' => 'error',
                    'message' => 'Giao dịch thất bại hoặc chưa hoàn thành',
                    'data' => $body,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            ], 500);
        }
    }
}
