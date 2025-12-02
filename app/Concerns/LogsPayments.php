<?php

namespace App\Concerns;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentProvider;
use Illuminate\Support\Facades\Log;

trait LogsPayments
{
    protected function getLogChannel(): string
    {
        return config('payments.logging.channel', 'payments');
    }

    protected function isLoggingEnabled(): bool
    {
        return config('payments.logging.enabled', true);
    }

    protected function logPaymentAttempt(PaymentProvider $provider, PaymentRequest $request): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment attempt', [
            'provider' => $provider->value,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'order_id' => $request->orderId,
            'payment_method' => $request->paymentMethod?->value,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logPaymentInitiated(PaymentProvider $provider, PaymentRequest $request, PaymentResponse $response): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment initiated', [
            'provider' => $provider->value,
            'order_id' => $request->orderId,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'type' => $response->type->value,
            'has_redirect_url' => $response->redirectUrl !== null,
            'has_client_secret' => $response->clientSecret !== null,
            'has_form_html' => $response->formHtml !== null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logPaymentSuccess(PaymentProvider $provider, PaymentResult $result): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment successful', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logPaymentFailed(PaymentProvider $provider, PaymentResult $result): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->warning('Payment failed', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'status' => $result->status,
            'message' => $result->message,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logPaymentError(PaymentProvider $provider, \Throwable $exception, ?string $orderId = null): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->error('Payment error', [
            'provider' => $provider->value,
            'order_id' => $orderId,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logRefundAttempt(PaymentProvider $provider, string $paymentId, ?float $amount = null): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Refund attempt', [
            'provider' => $provider->value,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'full_refund' => $amount === null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logRefundSuccess(PaymentProvider $provider, PaymentResult $result): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Refund successful', [
            'provider' => $provider->value,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logRefundFailed(PaymentProvider $provider, PaymentResult $result): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->warning('Refund failed', [
            'provider' => $provider->value,
            'status' => $result->status,
            'message' => $result->message,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logStatusCheck(PaymentProvider $provider, string $paymentId): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->debug('Status check', [
            'provider' => $provider->value,
            'payment_id' => $paymentId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logCallbackReceived(PaymentProvider $provider, array $data): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Callback received', [
            'provider' => $provider->value,
            'data_keys' => array_keys($data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function logMetric(string $metric, array $data = []): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->debug($metric, array_merge($data, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}

