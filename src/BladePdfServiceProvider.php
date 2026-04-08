<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Support\AssetResolver;
use Illuminate\Support\ServiceProvider;

class BladePdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bladepdf.php', 'bladepdf');

        $this->app->singleton(BladePdfClient::class, function ($app): BladePdfClient {
            return new BladePdfClient($app['config']);
        });

        $this->app->singleton(AssetResolver::class, function ($app): AssetResolver {
            return new AssetResolver($app['config']);
        });

        $this->app->singleton('bladepdf', function ($app): BladePdfFactory {
            return new BladePdfFactory(
                $app->make(BladePdfClient::class),
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
