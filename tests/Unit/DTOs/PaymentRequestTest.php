<?php

namespace Tests\Unit\DTOs;

use App\DTOs\PaymentRequest;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    #[Test]
    public function it_creates_valid_payment_request()
    {
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123'
        );

        $this->assertEquals(50.00, $request->amount);
        $this->assertEquals('EUR', $request->currency);
        $this->assertEquals('ORDER-123', $request->orderId);
    }

    #[Test]
    public function it_throws_exception_for_negative_amount()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3001);

        new PaymentRequest(
            amount: -10.00,
            currency: 'EUR',
            orderId: 'ORDER-123'
        );
    }

    #[Test]
    public function it_throws_exception_for_zero_amount()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3001);

        new PaymentRequest(
            amount: 0,
            currency: 'EUR',
            orderId: 'ORDER-123'
        );
    }

    #[Test]
    public function it_throws_exception_for_excessive_amount()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3001);

        new PaymentRequest(
            amount: 1000000.00,
            currency: 'EUR',
            orderId: 'ORDER-123'
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_currency_length()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3002);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EU',
            orderId: 'ORDER-123'
        );
    }

    #[Test]
    public function it_throws_exception_for_currency_with_numbers()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3002);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EU1',
            orderId: 'ORDER-123'
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_order_id()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3003);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: ''
        );
    }

    #[Test]
    public function it_throws_exception_for_order_id_only_whitespace()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3003);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: '   '
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_order_id()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: str_repeat('A', 256)
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_return_url()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3004);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            returnUrl: 'not-a-valid-url'
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_cancel_url()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            cancelUrl: 'invalid'
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_notification_url()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            notificationUrl: 'invalid'
        );
    }

    #[Test]
    public function it_accepts_valid_urls()
    {
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            notificationUrl: 'https://example.com/notify'
        );

        $this->assertEquals('https://example.com/return', $request->returnUrl);
        $this->assertEquals('https://example.com/cancel', $request->cancelUrl);
        $this->assertEquals('https://example.com/notify', $request->notificationUrl);
    }

    #[Test]
    public function it_throws_exception_for_too_long_description()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            metadata: [
                'description' => str_repeat('A', 501),
            ]
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_email_in_metadata()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3007);

        new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            metadata: [
                'customer_email' => 'not-an-email',
            ]
        );
    }

    #[Test]
    public function it_accepts_valid_email_in_metadata()
    {
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            metadata: [
                'customer_email' => 'test@example.com',
            ]
        );

        $this->assertEquals('test@example.com', $request->metadata['customer_email']);
    }

    #[Test]
    public function it_accepts_payment_method()
    {
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-123',
            paymentMethod: PaymentMethod::CARD
        );

        $this->assertEquals(PaymentMethod::CARD, $request->paymentMethod);
    }

    #[Test]
    public function it_accepts_common_currencies()
    {
        $currencies = ['EUR', 'USD', 'GBP', 'JPY', 'CHF'];

        foreach ($currencies as $currency) {
            $request = new PaymentRequest(
                amount: 50.00,
                currency: $currency,
                orderId: 'ORDER-123'
            );

            $this->assertEquals($currency, $request->currency);
        }
    }

    #[Test]
    public function it_accepts_decimal_amounts()
    {
        $amounts = [0.01, 1.99, 10.50, 999.99, 999999.99];

        foreach ($amounts as $amount) {
            $request = new PaymentRequest(
                amount: $amount,
                currency: 'EUR',
                orderId: 'ORDER-123'
            );

            $this->assertEquals($amount, $request->amount);
        }
    }
}

