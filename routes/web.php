<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MoMoController;

Route::get('/momo/qr', [MoMoController::class, 'createQrCode']);
Route::get('/check-transaction/{orderId}', [MoMoController::class, 'checkTransactionStatus']);

