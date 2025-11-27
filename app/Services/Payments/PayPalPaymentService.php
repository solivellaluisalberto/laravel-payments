<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentType;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpException;

class PayPalPaymentService implements PaymentGateway
{
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

        if (!$clientId) {
            throw new \Exception(
                'PayPal Client ID not configured. ' .
                'Set PAYPAL_CLIENT_ID in .env or pass it to constructor.'
            );
        }
        if (!$clientSecret) {
            throw new \Exception(
                'PayPal Client Secret not configured. ' .
                'Set PAYPAL_CLIENT_SECRET in .env or pass it to constructor.'
            );
        }
        
        $env = $this->environment === 'live'
            ? new ProductionEnvironment($clientId, $clientSecret)
            : new SandboxEnvironment($clientId, $clientSecret);
        
        $this->client = new PayPalHttpClient($env);
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        try {
            $paypalRequest = new OrdersCreateRequest();
            $paypalRequest->prefer('return=representation');
            
            $paypalRequest->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $request->orderId,
                    'amount' => [
                        'currency_code' => strtoupper($request->currency),
                        'value' => number_format($request->amount, 2, '.', '')
                    ],
                    'description' => $request->metadata['description'] ?? 'Order ' . $request->orderId,
                ]],
                'application_context' => [
                    'cancel_url' => $request->cancelUrl ?? config('app.url') . '/payments/paypal/cancel',
                    'return_url' => $request->returnUrl ?? config('app.url') . '/payments/paypal/return',
                    'brand_name' => config('app.name'),
                    'locale' => 'es-ES',
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                ]
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

            return new PaymentResponse(
                type: PaymentType::REDIRECT,
                data: [
                    'order_id' => $response->result->id,
                    'status' => $response->result->status,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                ],
                redirectUrl: $approveLink
            );
        } catch (HttpException $e) {
            throw new \Exception('PayPal API Error: ' . $e->getMessage());
        }
    }

    public function capture(string $paymentId): PaymentResult
    {
        try {
            $request = new OrdersCaptureRequest($paymentId);
            $request->prefer('return=representation');
            
            $response = $this->client->execute($request);
            
            $success = $response->result->status === 'COMPLETED';
            
            return new PaymentResult(
                success: $success,
                status: $success ? 'completed' : $response->result->status,
                paymentId: $paymentId,
                transactionId: $response->result->purchase_units[0]->payments->captures[0]->id ?? null,
                message: $success ? 'Payment captured successfully' : 'Payment status: ' . $response->result->status
            );
        } catch (HttpException $e) {
            return new PaymentResult(
                success: false,
                status: 'error',
                message: 'Error capturing PayPal payment: ' . $e->getMessage()
            );
        }
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        try {
            // Primero obtener el capture ID
            $orderRequest = new OrdersGetRequest($paymentId);
            $orderResponse = $this->client->execute($orderRequest);
            
            $captureId = $orderResponse->result->purchase_units[0]->payments->captures[0]->id ?? null;
            
            if (!$captureId) {
                throw new \Exception('No capture found for this payment');
            }
            
            // Crear el refund
            $request = new CapturesRefundRequest($captureId);
            $request->prefer('return=representation');
            
            if ($amount !== null) {
                $request->body = [
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => 'EUR'
                    ]
                ];
            }
            
            $response = $this->client->execute($request);
            
            $success = $response->result->status === 'COMPLETED';
            
            return new PaymentResult(
                success: $success,
                status: $success ? 'refunded' : $response->result->status,
                transactionId: $response->result->id,
                message: $success ? 'Refund processed successfully' : 'Refund status: ' . $response->result->status
            );
        } catch (HttpException $e) {
            return new PaymentResult(
                success: false,
                status: 'error',
                message: 'Error processing PayPal refund: ' . $e->getMessage()
            );
        }
    }

    public function getStatus(string $paymentId): PaymentResult
    {
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
            return new PaymentResult(
                success: false,
                status: 'error',
                message: 'Error retrieving PayPal status: ' . $e->getMessage()
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

