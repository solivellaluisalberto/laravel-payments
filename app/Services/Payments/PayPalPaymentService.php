<?php

namespace App\Services\Payments;

use App\Concerns\LogsPayments;
use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentProvider;
use App\Enums\PaymentType;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpException;

class PayPalPaymentService implements PaymentGateway
{
    use LogsPayments;
    private PayPalHttpClient $client;

    private string $environment;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $environment = null
    ) {
        $clientId = $clientId ?? config('payments.paypal.client_id');
        $clientSecret = $clientSecret ?? config('payments.paypal.client_secret');
        $this->environment = $environment ?? config('payments.paypal.environment', 'sandbox');

        if (! $clientId) {
            throw PaymentConfigurationException::missingCredentials('PayPal', 'client_id');
        }
        if (! $clientSecret) {
            throw PaymentConfigurationException::missingCredentials('PayPal', 'client_secret');
        }

        // Validar entorno
        if (! in_array($this->environment, ['sandbox', 'live'])) {
            throw PaymentConfigurationException::invalidEnvironment('PayPal', $this->environment);
        }

        try {
            $env = $this->environment === 'live'
                ? new ProductionEnvironment($clientId, $clientSecret)
                : new SandboxEnvironment($clientId, $clientSecret);

            $this->client = new PayPalHttpClient($env);
        } catch (\Exception $e) {
            throw PaymentConfigurationException::invalidApiKey('PayPal');
        }
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $this->logPaymentAttempt(PaymentProvider::PAYPAL, $request);

        try {
            $paypalRequest = new OrdersCreateRequest;
            $paypalRequest->prefer('return=representation');

            $paypalRequest->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $request->orderId,
                    'amount' => [
                        'currency_code' => strtoupper($request->currency),
                        'value' => number_format($request->amount, 2, '.', ''),
                    ],
                    'description' => $request->metadata['description'] ?? 'Order '.$request->orderId,
                ]],
                'application_context' => [
                    'cancel_url' => $request->cancelUrl ?? config('app.url').'/payments/paypal/cancel',
                    'return_url' => $request->returnUrl ?? config('app.url').'/payments/paypal/return',
                    'brand_name' => config('app.name'),
                    'locale' => 'es-ES',
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = $this->client->execute($paypalRequest);

            // Buscar el link de aprobación
            $approveLink = null;
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    $approveLink = $link->href;
                    break;
                }
            }

            if (! $approveLink) {
                throw PaymentProviderException::invalidResponse(
                    PaymentProvider::PAYPAL,
                    'Approval link not found in response'
                );
            }

            $paymentResponse = new PaymentResponse(
                type: PaymentType::REDIRECT,
                data: [
                    'order_id' => $response->result->id,
                    'status' => $response->result->status,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                ],
                redirectUrl: $approveLink
            );

            $this->logPaymentInitiated(PaymentProvider::PAYPAL, $request, $paymentResponse);

            return $paymentResponse;
        } catch (HttpException $e) {
            $this->logPaymentError(PaymentProvider::PAYPAL, $e, $request->orderId);

            $errorCode = $e->statusCode ?? null;
            throw PaymentProviderException::apiError(
                PaymentProvider::PAYPAL,
                $e->getMessage(),
                $errorCode ? (string) $errorCode : null,
                $e
            );
        }
    }

    public function capture(string $paymentId): PaymentResult
    {
        try {
            $request = new OrdersCaptureRequest($paymentId);
            $request->prefer('return=representation');

            $response = $this->client->execute($request);

            $success = $response->result->status === 'COMPLETED';

            $result = new PaymentResult(
                success: $success,
                status: $success ? 'completed' : $response->result->status,
                paymentId: $paymentId,
                transactionId: $response->result->purchase_units[0]->payments->captures[0]->id ?? null,
                message: $success ? 'Payment captured successfully' : 'Payment status: '.$response->result->status
            );

            if ($result->success) {
                $this->logPaymentSuccess(PaymentProvider::PAYPAL, $result);
            } else {
                $this->logPaymentFailed(PaymentProvider::PAYPAL, $result);
            }

            return $result;
        } catch (HttpException $e) {
            $this->logPaymentError(PaymentProvider::PAYPAL, $e, $paymentId);

            if ($e->statusCode === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::PAYPAL, $paymentId);
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::PAYPAL,
                $e->getMessage(),
                $e->statusCode ? (string) $e->statusCode : null,
                $e
            );
        }
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        $this->logRefundAttempt(PaymentProvider::PAYPAL, $paymentId, $amount);

        try {
            // Primero obtener el capture ID
            $orderRequest = new OrdersGetRequest($paymentId);
            $orderResponse = $this->client->execute($orderRequest);

            $captureId = $orderResponse->result->purchase_units[0]->payments->captures[0]->id ?? null;

            if (! $captureId) {
                throw PaymentProviderException::refundNotAvailable(
                    PaymentProvider::PAYPAL,
                    'No capture found for this payment'
                );
            }

            // Crear el refund
            $request = new CapturesRefundRequest($captureId);
            $request->prefer('return=representation');

            if ($amount !== null) {
                $request->body = [
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => 'EUR',
                    ],
                ];
            }

            $response = $this->client->execute($request);

            $success = $response->result->status === 'COMPLETED';

            $result = new PaymentResult(
                success: $success,
                status: $success ? 'refunded' : $response->result->status,
                transactionId: $response->result->id,
                message: $success ? 'Refund processed successfully' : 'Refund status: '.$response->result->status
            );

            if ($result->success) {
                $this->logRefundSuccess(PaymentProvider::PAYPAL, $result);
            } else {
                $this->logRefundFailed(PaymentProvider::PAYPAL, $result);
            }

            return $result;
        } catch (HttpException $e) {
            $this->logPaymentError(PaymentProvider::PAYPAL, $e, $paymentId);

            if ($e->statusCode === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::PAYPAL, $paymentId);
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::PAYPAL,
                $e->getMessage(),
                $e->statusCode ? (string) $e->statusCode : null,
                $e
            );
        } catch (PaymentProviderException $e) {
            $this->logPaymentError(PaymentProvider::PAYPAL, $e, $paymentId);
            throw $e;
        }
    }

    public function getStatus(string $paymentId): PaymentResult
    {
        $this->logStatusCheck(PaymentProvider::PAYPAL, $paymentId);

        try {
            $request = new OrdersGetRequest($paymentId);
            $response = $this->client->execute($request);

            $success = $response->result->status === 'COMPLETED';

            return new PaymentResult(
                success: $success,
                status: $response->result->status,
                paymentId: $paymentId,
                transactionId: $response->result->purchase_units[0]->payments->captures[0]->id ?? null,
                data: [
                    'status' => $response->result->status,
                    'amount' => $response->result->purchase_units[0]->amount->value ?? null,
                    'currency' => $response->result->purchase_units[0]->amount->currency_code ?? null,
                ]
            );
        } catch (HttpException $e) {
            $this->logPaymentError(PaymentProvider::PAYPAL, $e, $paymentId);

            if ($e->statusCode === 404) {
                throw PaymentProviderException::paymentNotFound(PaymentProvider::PAYPAL, $paymentId);
            }

            throw PaymentProviderException::apiError(
                PaymentProvider::PAYPAL,
                $e->getMessage(),
                $e->statusCode ? (string) $e->statusCode : null,
                $e
            );
        }
    }

    /**
     * Verificar callback de retorno
     * PayPal usa el método capture() directamente en el callback
     */
    public function verifyCallback(array $postData): PaymentResult
    {
        // PayPal no necesita verificación separada, el token viene en la URL
        // y se usa capture() directamente
        return new PaymentResult(
            success: false,
            status: 'not_supported',
            message: 'PayPal uses capture() method for callbacks'
        );
    }
}
