<?php

namespace App\Http\Controllers;

use App\DTOs\PaymentRequest;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Events\PaymentCompleted;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentManager $paymentManager
    ) {}

    /**
     * Mostrar página principal con ejemplos
     */
    public function index()
    {
        return view('payments.index');
    }

    /**
     * Ejemplo de pago con Stripe
     */
    public function stripeExample()
    {
        return view('payments.stripe', [
            'publicKey' => config('payments.stripe.public_key'),
        ]);
    }

    /**
     * Iniciar pago con Stripe
     */
    public function stripeInitiate(Request $request)
    {
        try {
            $gateway = $this->paymentManager->driver(PaymentProvider::STRIPE);

            $paymentRequest = new PaymentRequest(
                amount: $request->input('amount', 50.00),
                currency: 'EUR',
                orderId: 'ORDER-'.time(),
                metadata: [
                    'description' => 'Pago de prueba Stripe',
                ]
            );

            $response = $gateway->initiate($paymentRequest);

            return response()->json([
                'success' => true,
                'clientSecret' => $response->clientSecret,
                'data' => $response->data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar estado de pago Stripe
     */
    public function stripeVerify(Request $request)
    {
        try {
            $gateway = $this->paymentManager->driver(PaymentProvider::STRIPE);
            $paymentIntent = $request->input('payment_intent');
            $result = $gateway->capture($paymentIntent);

            // Si el pago fue exitoso, disparar evento
            if ($result->success) {
                event(new PaymentCompleted(
                    provider: PaymentProvider::STRIPE,
                    result: $result,
                    orderId: $paymentIntent, // Stripe usa el payment_intent como order_id
                    amount: $request->input('amount', 0),
                    currency: 'EUR',
                    metadata: $request->input('metadata', []),
                    customerEmail: $request->input('customer_email')
                ));
            }

            return response()->json([
                'success' => $result->success,
                'message' => $result->message,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejemplo de pago con Redsys
     */
    public function redsysExample()
    {
        return view('payments.redsys');
    }

    /**
     * Iniciar pago con Redsys
     */
    public function redsysInitiate(Request $request)
    {
        try {
            $gateway = $this->paymentManager->driver(PaymentProvider::REDSYS);

            $paymentMethod = $request->input('payment_method');
            $method = $paymentMethod === 'bizum' ? PaymentMethod::BIZUM : PaymentMethod::CARD;

            $paymentRequest = new PaymentRequest(
                amount: $request->input('amount', 50.00),
                currency: 'EUR',
                orderId: str_pad((string) time(), 12, '0', STR_PAD_LEFT),
                metadata: [
                    'description' => 'Pago de prueba Redsys',
                ],
                returnUrl: route('payments.redsys.return'),
                cancelUrl: route('payments.redsys.cancel'),
                paymentMethod: $method
            );

            $response = $gateway->initiate($paymentRequest);

            return view('payments.redsys-form', [
                'formHtml' => $response->formHtml,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Callback de retorno de Redsys
     */
    public function redsysReturn(Request $request)
    {
        try {
            $gateway = $this->paymentManager->driver(PaymentProvider::REDSYS);
            $result = $gateway->verifyCallback($request->all());

            if ($result->success) {
                // Disparar evento de pago completado
                event(new PaymentCompleted(
                    provider: PaymentProvider::REDSYS,
                    result: $result,
                    orderId: $result->data['order_id'] ?? 'unknown',
                    amount: floatval($result->data['amount'] ?? 0),
                    currency: 'EUR',
                    metadata: $result->data,
                    customerEmail: $result->data['customer_email'] ?? null
                ));

                return view('payments.success', [
                    'provider' => 'Redsys',
                    'result' => $result,
                ]);
            } else {
                return view('payments.error', [
                    'provider' => 'Redsys',
                    'result' => $result,
                ]);
            }
        } catch (\Exception $e) {
            return view('payments.error', [
                'provider' => 'Redsys',
                'result' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Callback de cancelación de Redsys
     */
    public function redsysCancel()
    {
        return view('payments.cancelled', [
            'provider' => 'Redsys',
        ]);
    }

    /**
     * Ejemplo de pago con PayPal
     */
    public function paypalExample()
    {
        return view('payments.paypal');
    }

    /**
     * Iniciar pago con PayPal
     */
    public function paypalInitiate(Request $request)
    {
        try {
            $gateway = $this->paymentManager->driver(PaymentProvider::PAYPAL);

            $paymentRequest = new PaymentRequest(
                amount: $request->input('amount', 50.00),
                currency: 'EUR',
                orderId: 'ORDER-'.time(),
                metadata: [
                    'description' => 'Pago de prueba PayPal',
                ],
                returnUrl: route('payments.paypal.return'),
                cancelUrl: route('payments.paypal.cancel')
            );

            $response = $gateway->initiate($paymentRequest);

            return redirect($response->redirectUrl);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Callback de retorno de PayPal
     */
    public function paypalReturn(Request $request)
    {
        try {
            $orderId = $request->query('token');

            if (! $orderId) {
                throw new \Exception('No order ID provided');
            }

            $gateway = $this->paymentManager->driver(PaymentProvider::PAYPAL);
            $result = $gateway->capture($orderId);

            if ($result->success) {
                // Disparar evento de pago completado
                event(new PaymentCompleted(
                    provider: PaymentProvider::PAYPAL,
                    result: $result,
                    orderId: $orderId,
                    amount: floatval($result->data['amount'] ?? 0),
                    currency: $result->data['currency'] ?? 'EUR',
                    metadata: $result->data,
                    customerEmail: $result->data['payer_email'] ?? null
                ));

                return view('payments.success', [
                    'provider' => 'PayPal',
                    'result' => $result,
                ]);
            } else {
                return view('payments.error', [
                    'provider' => 'PayPal',
                    'result' => $result,
                ]);
            }
        } catch (\Exception $e) {
            return view('payments.error', [
                'provider' => 'PayPal',
                'result' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Callback de cancelación de PayPal
     */
    public function paypalCancel()
    {
        return view('payments.cancelled', [
            'provider' => 'PayPal',
        ]);
    }

    /**
     * Comparativa de proveedores
     */
    public function comparative()
    {
        return view('payments.comparative');
    }

    /**
     * Documentación del sistema de eventos
     */
    public function events()
    {
        return view('payments.events');
    }

    /**
     * Ejemplo de reembolsos
     */
    public function refundExample()
    {
        return view('payments.refund');
    }

    /**
     * Procesar reembolso
     */
    public function processRefund(Request $request)
    {
        try {
            $provider = PaymentProvider::from($request->input('provider'));
            $gateway = $this->paymentManager->driver($provider);

            $result = $gateway->refund(
                paymentId: $request->input('payment_id'),
                amount: $request->input('amount') ? (float) $request->input('amount') : null
            );

            return response()->json([
                'success' => $result->success,
                'message' => $result->message,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
