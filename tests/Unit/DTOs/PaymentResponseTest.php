<?php

namespace Tests\Unit\DTOs;

use App\DTOs\PaymentResponse;
use App\Enums\PaymentType;
use App\Exceptions\PaymentValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentResponseTest extends TestCase
{
    #[Test]
    public function it_creates_valid_redirect_response_with_url()
    {
        $response = new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: ['order_id' => '123'],
            redirectUrl: 'https://example.com/pay'
        );

        $this->assertEquals(PaymentType::REDIRECT, $response->type);
        $this->assertEquals('https://example.com/pay', $response->redirectUrl);
        $this->assertTrue($response->isRedirect());
    }

    #[Test]
    public function it_creates_valid_redirect_response_with_form_html()
    {
        $response = new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: ['order_id' => '123'],
            formHtml: '<form>...</form>'
        );

        $this->assertEquals(PaymentType::REDIRECT, $response->type);
        $this->assertEquals('<form>...</form>', $response->formHtml);
    }

    #[Test]
    public function it_throws_exception_for_redirect_without_url_or_form()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: ['order_id' => '123']
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_redirect_url()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: ['order_id' => '123'],
            redirectUrl: 'not-a-url'
        );
    }

    #[Test]
    public function it_creates_valid_api_response()
    {
        $response = new PaymentResponse(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123'],
            clientSecret: 'pi_123_secret_abc'
        );

        $this->assertEquals(PaymentType::API, $response->type);
        $this->assertEquals('pi_123_secret_abc', $response->clientSecret);
        $this->assertTrue($response->isApi());
    }

    #[Test]
    public function it_throws_exception_for_api_without_client_secret()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3006);

        new PaymentResponse(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123']
        );
    }

    #[Test]
    public function it_throws_exception_for_api_with_empty_client_secret()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResponse(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123'],
            clientSecret: '   '
        );
    }

    #[Test]
    public function it_creates_valid_alternative_response()
    {
        $response = new PaymentResponse(
            type: PaymentType::ALTERNATIVE,
            data: ['qr_code' => 'data:image/png;base64,...']
        );

        $this->assertEquals(PaymentType::ALTERNATIVE, $response->type);
        $this->assertTrue($response->isAlternative());
    }

    #[Test]
    public function it_throws_exception_for_empty_data()
    {
        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionCode(3009);

        new PaymentResponse(
            type: PaymentType::API,
            data: [],
            clientSecret: 'secret'
        );
    }

    #[Test]
    public function it_has_helper_methods()
    {
        $redirect = new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: ['test' => 'data'],
            redirectUrl: 'https://example.com'
        );

        $api = new PaymentResponse(
            type: PaymentType::API,
            data: ['test' => 'data'],
            clientSecret: 'secret'
        );

        $alternative = new PaymentResponse(
            type: PaymentType::ALTERNATIVE,
            data: ['test' => 'data']
        );

        $this->assertTrue($redirect->isRedirect());
        $this->assertFalse($redirect->isApi());
        $this->assertFalse($redirect->isAlternative());

        $this->assertFalse($api->isRedirect());
        $this->assertTrue($api->isApi());
        $this->assertFalse($api->isAlternative());

        $this->assertFalse($alternative->isRedirect());
        $this->assertFalse($alternative->isApi());
        $this->assertTrue($alternative->isAlternative());
    }
}

