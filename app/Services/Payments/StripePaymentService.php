<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentProvider;
use App\Enums\PaymentType;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePaymentService implements PaymentGateway
{
    private StripeClient $stripe;

    public function __construct(?string $apiKey = null)
    {
        $key = $apiKey ?? config('payments.stripe.secret_key');

        if (! $key) {
            throw PaymentConfigurationException::missingCredentials('Stripe', 'secret_key');
        }

        try {
            $this->stripe = new StripeClient($key);
        } catch (\Exception $e) {
            throw PaymentConfigurationException::invalidApiKey('Stripe');
        }
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($request->amount * 100), // Convertir a centavos
                'currency' => strtolower($request->currency),
                'metadata' => array_merge($request->metadata, [
                    'order_id' => $request->orderId,
                ]),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return new PaymentResponse(
                type: PaymentType::API,
                data: [
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                ],
                clientSecret: $paymentIntent->client_secret
            );
        } catch (ApiErrorException $e) {
            throw PaymentProviderException::apiError(
                PaymentProvider::STRIPE,
                $e->getMessage(),
                $e->getStripeCode(),
                $e
            );
        }
    }

    public function capture(string $paymentId): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($paymentId);

            if ($intent->status === 'succeeded') {
                return new PaymentResult(
                    success: true,
                    status: 'completed',
                    paymentId: $paymentId,
                    transactionId: $intent->charges->data[0]->id ?? $paymentId,
                    message: 'Payment captured successfully.'
                );
            }

            return new PaymentResult(
                success: false,
                status: $intent->status,
                paymentId: $paymentId,
                message: 'Payment not completed. Current status: '.$intent->status
            );
        } catch (ApiErrorException $e) {
            if ($e->getHttpStatus() === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::STRIPE, $paymentId);
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::STRIPE,
                $e->getMessage(),
                $e->getStripeCode(),
                $e
            );
        }
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        try {
            // Determinar si es un payment_intent o un charge
            $refundData = [];

            if (str_starts_with($paymentId, 'pi_')) {
                // Es un Payment Intent ID
                $refundData['payment_intent'] = $paymentId;
            } elseif (str_starts_with($paymentId, 'ch_')) {
                // Es un Charge ID
                $refundData['charge'] = $paymentId;
            } else {
                // Asumir que es un Payment Intent
                $refundData['payment_intent'] = $paymentId;
            }

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = $this->stripe->refunds->create($refundData);

            if ($refund->status === 'succeeded') {
                return new PaymentResult(
                    success: true,
                    status: 'refunded',
                    transactionId: $refund->id,
                    message: 'Refund processed successfully.'
                );
            }

            return new PaymentResult(
                success: false,
                status: $refund->status,
                message: 'Refund status: '.$refund->status
            );
        } catch (ApiErrorException $e) {
            if ($e->getHttpStatus() === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::STRIPE, $paymentId);
            }

            // Stripe puede rechazar reembolsos por varias razones
            if (str_contains($e->getMessage(), 'has already been refunded')) {
                throw PaymentProviderException::refundNotAvailable(
                    PaymentProvider::STRIPE,
                    'Payment has already been refunded'
                );
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::STRIPE,
                $e->getMessage(),
                $e->getStripeCode(),
                $e
            );
        }
    }

    public function getStatus(string $paymentId): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($paymentId);

            return new PaymentResult(
                success: $intent->status === 'succeeded',
                status: $intent->status,
                paymentId: $paymentId,
                transactionId: $intent->charges->data[0]->id ?? null,
                data: [
                    'amount' => $intent->amount / 100,
                    'currency' => strtoupper($intent->currency),
                    'created' => date('Y-m-d H:i:s', $intent->created),
                ]
            );
        } catch (ApiErrorException $e) {
            if ($e->getHttpStatus() === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::STRIPE, $paymentId);
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::STRIPE,
                $e->getMessage(),
                $e->getStripeCode(),
                $e
            );
        }
    }

    /**
     * Verificar callback de retorno
     * Stripe no usa callbacks tradicionales, usa webhooks
     */
    public function verifyCallback(array $postData): PaymentResult
    {
        return new PaymentResult(
            success: false,
            status: 'not_supported',
            message: 'Stripe uses webhooks instead of callbacks'
        );
    }
}
