<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Assets\AssetResolver;
use BladePDF\Assets\AssetResolverOptions;
use BladePDF\Client\BladePdfClient;
use BladePDF\Client\ClientOptions;
use BladePDF\Contracts\RenderClient;
use Illuminate\Support\ServiceProvider;

class BladePdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bladepdf.php', 'bladepdf');

        $this->app->singleton(ClientOptions::class, function ($app): ClientOptions {
            $config = $app['config'];

            return new ClientOptions(
                baseUrl: (string) $config->get('bladepdf.base_url', 'https://api.bladepdf.com'),
                timeout: (int) $config->get('bladepdf.timeout', 60),
                connectTimeout: (int) $config->get('bladepdf.connect_timeout', 10),
                retryTimes: (int) $config->get('bladepdf.retry_times', 1),
                retrySleepMilliseconds: (int) $config->get('bladepdf.retry_sleep', 1000),
                verifySsl: (bool) $config->get('bladepdf.verify_ssl', true),
                userAgent: (string) $config->get('bladepdf.user_agent', 'bladepdf-laravel/2.0 bladepdf-php/1.0'),
            );
        });

        $this->app->singleton(BladePdfClient::class, function ($app): BladePdfClient {
            return new BladePdfClient(
                (string) $app['config']->get('bladepdf.api_key', ''),
                $app->make(ClientOptions::class),
            );
        });

        $this->app->alias(BladePdfClient::class, RenderClient::class);

        $this->app->singleton(AssetResolver::class, function ($app): AssetResolver {
            $config = $app['config'];
            $documentRoot = $config->get('bladepdf.document_root');
            $assetRoots = $config->get('bladepdf.asset_roots', []);
            $localHosts = $config->get('bladepdf.local_hosts', []);

            return new AssetResolver(new AssetResolverOptions(
                documentRoot: is_string($documentRoot) && is_dir($documentRoot) ? $documentRoot : null,
                searchRoots: array_values(array_filter(
                    is_array($assetRoots) ? $assetRoots : [],
                    static fn (mixed $root): bool => is_string($root) && is_dir($root),
                )),
                localHosts: array_values(array_filter(
                    is_array($localHosts) ? $localHosts : [],
                    static fn (mixed $host): bool => is_string($host) && trim($host) !== '',
                )),
                autoResolve: (bool) $config->get('bladepdf.auto_resolve_assets', true),
            ));
        });

        $this->app->singleton('bladepdf', function ($app): BladePdfFactory {
            return new BladePdfFactory(
                $app->make(RenderClient::class),
                $app->make(AssetResolver::class),
                $app['view']
            );
        });

        $this->app->alias('bladepdf', BladePdfFactory::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/bladepdf.php' => config_path('bladepdf.php'),
        ], 'bladepdf-config');
    }
}
