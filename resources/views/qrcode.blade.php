<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo</title>
</head>
<body>
    <h1>Quét mã QR để thanh toán</h1>

    <p>Quét mã QR để thanh toán đơn hàng của bạn:</p>
    <img src="momo://app?action=payWithApp&isScanQR=true&serviceType=qr&sid={{ urlencode($deeplink) }}&v=3.0" alt="QR Code">

    <p>Hoặc bạn có thể thanh toán bằng cách nhấp vào liên kết sau:</p>
    <a href="{{ $payUrl }}" target="_blank">Thanh toán qua MoMo</a>
</body>
</html>
