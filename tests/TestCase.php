<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\BladePdfServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [BladePdfServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', env('APP_URL', 'http://localhost'));
        $app['config']->set('bladepdf.base_url', env('BLADEPDF_BASE_URL', 'http://localhost'));
        $app['config']->set('bladepdf.api_key', env('BLADEPDF_API_KEY', 'test-api-key'));
    }
}
