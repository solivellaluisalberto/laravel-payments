<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentProvider;
use App\Enums\PaymentType;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;

class RedsysPaymentService implements PaymentGateway
{
    private string $merchantCode;

    private string $secretKey;

    private string $terminal;

    private string $environment;

    public function __construct(
        ?string $merchantCode = null,
        ?string $secretKey = null,
        ?string $terminal = null,
        ?string $environment = null
    ) {
        $this->merchantCode = $merchantCode ?? config('payments.redsys.merchant_code');
        $this->secretKey = $secretKey ?? config('payments.redsys.secret_key');
        $this->terminal = $terminal ?? config('payments.redsys.terminal', '1');
        $this->environment = $environment ?? config('payments.redsys.environment', 'test');

        if (! $this->merchantCode) {
            throw PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');
        }
        if (! $this->secretKey) {
            throw PaymentConfigurationException::missingCredentials('Redsys', 'secret_key');
        }

        // Validar entorno
        if (! in_array($this->environment, ['test', 'live'])) {
            throw PaymentConfigurationException::invalidEnvironment('Redsys', $this->environment);
        }
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        try {
            $tpv = new Tpv;

            // Configuración básica
            $tpv->setAmount($request->amount);
            $tpv->setOrder($request->orderId);
            $tpv->setMerchantcode($this->merchantCode);
            $tpv->setCurrency('978'); // EUR
            $tpv->setTransactiontype('0'); // 0 = Autorización
            $tpv->setTerminal($this->terminal);
            $tpv->setVersion('HMAC_SHA256_V1');

            // URLs de retorno
            $returnUrl = $request->returnUrl ?? route('payments.redsys.return');
            $tpv->setUrlOK($returnUrl);
            $tpv->setUrlKO($returnUrl); // Ambas van a la misma URL, se diferencia por el resultado

            // Método de pago (tarjeta, Bizum, etc.)
            $paymentMethodCode = $request->paymentMethod?->getRedsysCode() ?? 'C';
            $tpv->setMethod($paymentMethodCode);

            // Nombre del producto
            $tpv->setProductDescription($request->metadata['description'] ?? 'Pedido '.$request->orderId);

            // Entorno
            $tpv->setEnvironment($this->environment === 'live' ? 'live' : 'test');

            // Firma
            $signature = $tpv->generateMerchantSignature($this->secretKey);
            $tpv->setMerchantSignature($signature);

            // Generar formulario HTML
            $formHtml = $tpv->createForm();

            return new PaymentResponse(
                type: PaymentType::REDIRECT,
                data: [
                    'order_id' => $request->orderId,
                    'amount' => $request->amount,
                    'merchant_code' => $this->merchantCode,
                    'payment_method' => $paymentMethodCode,
                ],
                formHtml: $formHtml
            );
        } catch (TpvException $e) {
            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                $e->getMessage(),
                null,
                $e
            );
        }
    }

    public function capture(string $paymentId): PaymentResult
    {
        // Redsys no requiere captura separada, el pago se confirma automáticamente
        // Este método se usa para verificar la respuesta del callback

        return new PaymentResult(
            success: true,
            status: 'completed',
            transactionId: $paymentId,
            message: 'Redsys payment confirmed.'
        );
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        try {
            $tpv = new Tpv;
            $tpv->setAmount($amount);
            $tpv->setOrder($paymentId);
            $tpv->setMerchantcode($this->merchantCode);
            $tpv->setCurrency('978'); // EUR
            $tpv->setTransactiontype('3'); // 3 = Devolución
            $tpv->setTerminal($this->terminal);
            $tpv->setVersion('HMAC_SHA256_V1');
            $tpv->setEnvironment($this->environment === 'live' ? 'restLive' : 'restTest');

            $signature = $tpv->generateMerchantSignature($this->secretKey);
            $tpv->setMerchantSignature($signature);

            $response = json_decode($tpv->send(), true);

            if (isset($response['errorCode'])) {
                throw PaymentProviderException::apiError(
                    PaymentProvider::REDSYS,
                    'Refund failed',
                    $response['errorCode']
                );
            }

            $parameters = $tpv->getMerchantParameters($response['Ds_MerchantParameters']);
            $dsResponse = (int) $parameters['Ds_Response'];

            if ($tpv->check($this->secretKey, $response) && $dsResponse <= 99) {
                return new PaymentResult(
                    success: true,
                    status: 'refunded',
                    transactionId: $parameters['Ds_AuthorisationCode'] ?? $paymentId,
                    message: 'Refund processed successfully.'
                );
            } else {
                throw PaymentProviderException::refundNotAvailable(
                    PaymentProvider::REDSYS,
                    'Response code: '.$dsResponse
                );
            }
        } catch (TpvException $e) {
            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                $e->getMessage(),
                null,
                $e
            );
        } catch (PaymentProviderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                'Error processing refund: '.$e->getMessage(),
                null,
                $e
            );
        }
    }

    public function getStatus(string $paymentId): PaymentResult
    {
        // Redsys no tiene API REST para consultar estado directamente
        // El estado se obtiene del callback de notificación

        return new PaymentResult(
            success: false,
            status: 'unavailable',
            message: 'Redsys does not support direct status queries. Use notification callback.'
        );
    }

    /**
     * Verificar la respuesta de Redsys (callback)
     */
    public function verifyCallback(array $postData): PaymentResult
    {
        try {
            $tpv = new Tpv;

            if (! isset($postData['Ds_MerchantParameters']) || ! isset($postData['Ds_Signature'])) {
                throw PaymentProviderException::invalidResponse(
                    PaymentProvider::REDSYS,
                    'Missing required parameters'
                );
            }

            // Verificar firma
            if (! $tpv->check($this->secretKey, $postData)) {
                throw PaymentProviderException::signatureVerificationFailed(PaymentProvider::REDSYS);
            }

            // Decodificar parámetros
            $parameters = $tpv->getMerchantParameters($postData['Ds_MerchantParameters']);
            $dsResponse = (int) ($parameters['Ds_Response'] ?? 9999);

            // Respuestas 0-99 son exitosas en Redsys
            $success = $dsResponse >= 0 && $dsResponse <= 99;

            if (! $success && $dsResponse !== 9999) {
                throw PaymentProviderException::paymentDeclined(
                    PaymentProvider::REDSYS,
                    'Payment declined by bank',
                    (string) $dsResponse
                );
            }

            return new PaymentResult(
                success: $success,
                status: $success ? 'completed' : 'failed',
                paymentId: $parameters['Ds_Order'] ?? null,
                transactionId: $parameters['Ds_AuthorisationCode'] ?? null,
                message: $success ? 'Payment completed successfully' : 'Payment failed',
                data: $parameters
            );
        } catch (PaymentProviderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw PaymentProviderException::invalidResponse(
                PaymentProvider::REDSYS,
                $e->getMessage()
            );
        }
    }
}
