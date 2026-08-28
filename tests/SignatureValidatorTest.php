<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\Webhooks\SignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class SignatureValidatorTest extends TestCase
{
    private const SECRET = 'whsec_test_signing_secret';

    protected function setUp(): void
    {
        parent::setUp();

        Date::setTestNow('2026-07-26 12:00:00 UTC');
    }

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_it_validates_a_signature_with_the_configured_secret(): void
    {
        config()->set('bladepdf.webhook_secret', self::SECRET);

        $request = $this->signedRequest('{"type":"pdf.rendered"}');

        $this->assertTrue(SignatureValidator::isValid($request));
    }

    public function test_it_accepts_an_explicit_secret_for_a_per_request_webhook(): void
    {
        config()->set('bladepdf.webhook_secret', 'different-secret');

        $request = $this->signedRequest(
            '{"type":"pdf.failed"}',
            secret: self::SECRET,
        );

        $this->assertTrue(SignatureValidator::isValid($request, self::SECRET));
        $this->assertFalse(SignatureValidator::isValid($request));
    }

    public function test_it_uses_the_exact_raw_request_body(): void
    {
        $request = $this->signedRequest('{"type":"pdf.rendered"}');
        $request->initialize(
            server: $request->server->all(),
            content: '{ "type": "pdf.rendered" }',
        );

        $this->assertFalse(SignatureValidator::isValid($request, self::SECRET));
    }

    public function test_it_rejects_missing_or_malformed_headers_and_secrets(): void
    {
        $request = Request::create('/webhooks/bladepdf', 'POST', content: '{}');

        $this->assertFalse(SignatureValidator::isValid($request, self::SECRET));

        $request->headers->set('BladePDF-Timestamp', 'not-a-timestamp');
        $request->headers->set('BladePDF-Signature', 'v1=invalid');

        $this->assertFalse(SignatureValidator::isValid($request, self::SECRET));
        $this->assertFalse(SignatureValidator::isValid($request, ''));
    }

    public function test_it_rejects_webhooks_outside_the_timestamp_tolerance(): void
    {
        $stale = $this->signedRequest(
            '{}',
            timestamp: Date::now()->getTimestamp() - 301,
        );
        $future = $this->signedRequest(
            '{}',
            timestamp: Date::now()->getTimestamp() + 301,
        );

        $this->assertFalse(SignatureValidator::isValid($stale, self::SECRET));
        $this->assertFalse(SignatureValidator::isValid($future, self::SECRET));
    }

    public function test_zero_tolerance_disables_the_freshness_check(): void
    {
        $request = $this->signedRequest('{}', timestamp: 1);

        $this->assertTrue(SignatureValidator::isValid($request, self::SECRET, 0));
    }

    private function signedRequest(
        string $body,
        string $secret = self::SECRET,
        ?int $timestamp = null,
    ): Request {
        $timestamp ??= Date::now()->getTimestamp();
        $timestampHeader = (string) $timestamp;
        $signature = 'v1='.hash_hmac(
            'sha256',
            $timestampHeader.'.'.$body,
            $secret,
        );

        return Request::create(
            '/webhooks/bladepdf',
            'POST',
            server: [
                'HTTP_BLADEPDF_TIMESTAMP' => $timestampHeader,
                'HTTP_BLADEPDF_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        );
    }
}
