<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Página principal
Route::get('/', [PaymentController::class, 'index'])->name('home');

// Stripe
Route::prefix('payments/stripe')->name('payments.stripe.')->group(function () {
    Route::get('/', [PaymentController::class, 'stripeExample'])->name('example');
    Route::post('/initiate', [PaymentController::class, 'stripeInitiate'])->name('initiate');
    Route::post('/verify', [PaymentController::class, 'stripeVerify'])->name('verify');
});

// Redsys
Route::prefix('payments/redsys')->name('payments.redsys.')->group(function () {
    Route::get('/', [PaymentController::class, 'redsysExample'])->name('example');
    Route::post('/initiate', [PaymentController::class, 'redsysInitiate'])->name('initiate');
    Route::any('/return', [PaymentController::class, 'redsysReturn'])->name('return');
    Route::get('/cancel', [PaymentController::class, 'redsysCancel'])->name('cancel');
});

// PayPal
Route::prefix('payments/paypal')->name('payments.paypal.')->group(function () {
    Route::get('/', [PaymentController::class, 'paypalExample'])->name('example');
    Route::post('/initiate', [PaymentController::class, 'paypalInitiate'])->name('initiate');
    Route::get('/return', [PaymentController::class, 'paypalReturn'])->name('return');
    Route::get('/cancel', [PaymentController::class, 'paypalCancel'])->name('cancel');
});

// Comparativa
Route::get('payments/comparative', [PaymentController::class, 'comparative'])->name('payments.comparative');

// Sistema de eventos
Route::get('payments/events', [PaymentController::class, 'events'])->name('payments.events');

// Reembolsos
Route::prefix('payments/refund')->name('payments.refund.')->group(function () {
    Route::get('/', [PaymentController::class, 'refundExample'])->name('example');
    Route::post('/process', [PaymentController::class, 'processRefund'])->name('process');
});
