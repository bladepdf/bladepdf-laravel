<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\Support\AssetResolver;

class AssetResolverTest extends TestCase
{
    public function test_it_rewrites_local_html_assets_into_asset_scheme(): void
    {
        $publicPath = __DIR__.'/Fixtures/public';

        app()->usePublicPath($publicPath);

        file_put_contents($publicPath.'/logo.png', 'image-bytes');
        file_put_contents($publicPath.'/css/app.css', 'body{background:url("../logo.png");}@font-face{src:url("../fonts/test.woff2");}');
        file_put_contents($publicPath.'/fonts/test.woff2', 'font-bytes');

        $resolver = app(AssetResolver::class);

        $result = $resolver->resolve('<html><head><link rel="stylesheet" href="/css/app.css"></head><body><img src="/logo.png"></body></html>');

        $this->assertStringContainsString('asset:///', $result['html']);
        $this->assertCount(3, $result['assets']);
    }

    public function test_it_can_skip_automatic_asset_resolution(): void
    {
        $publicPath = __DIR__.'/Fixtures/public';

        app()->usePublicPath($publicPath);

        file_put_contents($publicPath.'/logo.png', 'image-bytes');

        $resolver = app(AssetResolver::class);

        $result = $resolver->resolve('<img src="/logo.png">', autoResolveAssets: false);

        $this->assertSame('<img src="/logo.png">', $result['html']);
        $this->assertCount(0, $result['assets']);
    }
}
