<?php

namespace Tests\Unit\DTOs;

use App\DTOs\PaymentResult;
use App\Exceptions\PaymentValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentResultTest extends TestCase
{
    #[Test]
    public function it_creates_valid_successful_result_with_payment_id()
    {
        $result = new PaymentResult(
            success: true,
            status: 'completed',
            paymentId: 'pi_123456789'
        );

        $this->assertTrue($result->success);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals('pi_123456789', $result->paymentId);
    }

    #[Test]
    public function it_creates_valid_successful_result_with_transaction_id()
    {
        $result = new PaymentResult(
            success: true,
            status: 'completed',
            transactionId: 'txn_123456789'
        );

        $this->assertTrue($result->success);
        $this->assertEquals('txn_123456789', $result->transactionId);
    }

    #[Test]
    public function it_creates_valid_failed_result()
    {
        $result = new PaymentResult(
            success: false,
            status: 'failed',
            message: 'Card declined'
        );

        $this->assertFalse($result->success);
        $this->assertEquals('failed', $result->status);
        $this->assertEquals('Card declined', $result->message);
    }

    #[Test]
    public function it_throws_exception_for_empty_status()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResult(
            success: false,
            status: ''
        );
    }

    #[Test]
    public function it_throws_exception_for_whitespace_status()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResult(
            success: false,
            status: '   '
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_status()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentResult(
            success: false,
            status: str_repeat('A', 51)
        );
    }

    #[Test]
    public function it_throws_exception_for_successful_payment_without_ids()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResult(
            success: true,
            status: 'completed'
        );
    }

    #[Test]
    public function it_accepts_common_statuses()
    {
        $statuses = [
            'pending', 'processing', 'completed', 'failed', 'cancelled',
            'refunded', 'partial_refund', 'disputed', 'expired',
            'authorized', 'requires_action', 'requires_payment_method',
            'requires_confirmation', 'requires_capture', 'error'
        ];

        foreach ($statuses as $status) {
            $result = new PaymentResult(
                success: false,
                status: $status
            );

            $this->assertEquals($status, $result->status);
        }
    }

    #[Test]
    public function it_accepts_status_with_underscores_and_hyphens()
    {
        $result1 = new PaymentResult(
            success: false,
            status: 'requires_payment_method'
        );

        $result2 = new PaymentResult(
            success: false,
            status: 'requires-payment-method'
        );

        $this->assertEquals('requires_payment_method', $result1->status);
        $this->assertEquals('requires-payment-method', $result2->status);
    }

    #[Test]
    public function it_throws_exception_for_status_with_invalid_characters()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResult(
            success: false,
            status: 'status with spaces!'
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_payment_id()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentResult(
            success: true,
            status: 'completed',
            paymentId: str_repeat('A', 256)
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_transaction_id()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentResult(
            success: true,
            status: 'completed',
            paymentId: 'pi_123',
            transactionId: str_repeat('A', 256)
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_message()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3008);

        new PaymentResult(
            success: false,
            status: 'failed',
            message: str_repeat('A', 1001)
        );
    }

    #[Test]
    public function it_accepts_additional_data()
    {
        $result = new PaymentResult(
            success: true,
            status: 'completed',
            paymentId: 'pi_123',
            data: [
                'amount' => 50.00,
                'currency' => 'EUR',
                'customer' => 'cus_123',
            ]
        );

        $this->assertEquals(50.00, $result->data['amount']);
        $this->assertEquals('EUR', $result->data['currency']);
        $this->assertEquals('cus_123', $result->data['customer']);
    }

    #[Test]
    public function it_accepts_both_payment_id_and_transaction_id()
    {
        $result = new PaymentResult(
            success: true,
            status: 'completed',
            paymentId: 'pi_123',
            transactionId: 'txn_456'
        );

        $this->assertEquals('pi_123', $result->paymentId);
        $this->assertEquals('txn_456', $result->transactionId);
    }
}

