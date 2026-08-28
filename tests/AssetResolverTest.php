<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Assets\AssetResolver;
use BladePDF\Exceptions\AssetAccessDeniedException;

class AssetResolverTest extends TestCase
{
    public function test_service_provider_configures_public_and_storage_roots_without_base_path(): void
    {
        $public = $this->makeDirectory('bladepdf-public-');
        $storage = $this->makeDirectory('bladepdf-storage-');
        $outside = $this->makeDirectory('bladepdf-outside-');

        file_put_contents($public.'/public.png', 'public');
        file_put_contents($storage.'/stored.png', 'stored');
        file_put_contents($outside.'/.env', 'APP_KEY=secret');

        config()->set('bladepdf.document_root', $public);
        config()->set('bladepdf.asset_roots', [$public, $storage]);
        config()->set('bladepdf.local_hosts', ['localhost']);
        app()->forgetInstance(AssetResolver::class);

        $resolved = app(AssetResolver::class)->resolve(
            '<img src="/public.png"><img src="stored.png">',
        );

        $this->assertCount(2, $resolved->assets);

        $this->expectException(AssetAccessDeniedException::class);
        app(AssetResolver::class)->resolve('<img src="'.$outside.'/.env">');
    }

    public function test_javascript_and_svg_are_uploaded_as_opaque_external_files(): void
    {
        $public = $this->makeDirectory('bladepdf-assets-');
        file_put_contents($public.'/app.js', 'fetch("/runtime.json")');
        file_put_contents($public.'/sprite.svg', '<svg><image href="nested.png"/></svg>');

        config()->set('bladepdf.document_root', $public);
        config()->set('bladepdf.asset_roots', [$public]);
        app()->forgetInstance(AssetResolver::class);

        $resolved = app(AssetResolver::class)->resolve(
            '<script src="/app.js"></script><svg><use href="/sprite.svg#icon"></use></svg>',
        );

        $this->assertCount(2, $resolved->assets);
        $this->assertStringContainsString('#icon', $resolved->html);
        $this->assertSame('fetch("/runtime.json")', $resolved->assets[0]->contents);
        $this->assertStringContainsString('nested.png', $resolved->assets[1]->contents);
    }

    private function makeDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir().'/'.$prefix.uniqid('', true);
        mkdir($directory, 0777, true);

        return $directory;
    }
}
