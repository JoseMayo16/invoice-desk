<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicInvoiceController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:login')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', DashboardController::class);

    Route::apiResource('clients', ClientController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::controller(InvoiceController::class)->prefix('invoices/{invoice}')->group(function () {
        Route::post('/send', 'send');
        Route::post('/duplicate', 'duplicate');
        Route::post('/void', 'void');
        Route::get('/pdf', 'pdf');
    });
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
});

Route::middleware('throttle:public')->prefix('public/invoices/{token}')
    ->controller(PublicInvoiceController::class)
    ->whereUuid('token')
    ->group(function () {
        Route::get('/', 'show');
        Route::get('/pdf', 'pdf');
        Route::post('/checkout', 'checkout');
    });

Route::post('/stripe/webhook', StripeWebhookController::class);
