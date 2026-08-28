<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Client\BladePdfClient;
use BladePDF\Client\ClientOptions;
use BladePDF\Contracts\RenderClient;

class BladePdfClientTest extends TestCase
{
    public function test_service_provider_maps_transport_configuration_and_contract_alias(): void
    {
        config()->set('bladepdf.base_url', 'https://gateway.example.test');
        config()->set('bladepdf.timeout', 45);
        config()->set('bladepdf.connect_timeout', 7);
        config()->set('bladepdf.retry_times', 3);
        config()->set('bladepdf.retry_sleep', 250);
        config()->set('bladepdf.verify_ssl', false);
        config()->set('bladepdf.user_agent', 'bladepdf-laravel/test bladepdf-php/test');

        $options = app(ClientOptions::class);

        $this->assertSame('https://gateway.example.test', $options->baseUrl);
        $this->assertSame(45, $options->timeout);
        $this->assertSame(7, $options->connectTimeout);
        $this->assertSame(3, $options->retryTimes);
        $this->assertSame(250, $options->retrySleepMilliseconds);
        $this->assertFalse($options->verifySsl);
        $this->assertSame('bladepdf-laravel/test bladepdf-php/test', $options->userAgent);
        $this->assertSame(app(BladePdfClient::class), app(RenderClient::class));
    }
}
