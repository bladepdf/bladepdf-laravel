<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\BladePdfClient;
use BladePDF\Laravel\Exceptions\InvalidRenderConfigurationException;
use BladePDF\Laravel\PendingRender;
use BladePDF\Laravel\RenderResult;
use BladePDF\Laravel\RenderSubmission;
use BladePDF\Laravel\Support\AssetResolver;

class PendingRenderTest extends TestCase
{
    public function test_it_sends_cloud_template_source_context_metadata_store_flag_and_asset_overrides(): void
    {
        $client = new CapturingBladePdfClient();
        $assetPath = $this->createTemporaryAsset('logo-bytes');

        $pdf = $this->pendingRender($client)
            ->fromTemplate('invoice.standard', [
                'invoice' => ['number' => 'INV-1'],
            ])
            ->reference('INV-1')
            ->storePdf()
            ->webhook('https://example.com/bladepdf', 'whsec_request', ['pdf.rendered'])
            ->overrideAsset('logo.png', $assetPath, 'image/png')
            ->format('A4')
            ->margins(10, 10, 15, 10)
            ->showBackground()
            ->render()
            ->pdf();

        $this->assertSame('pdf-bytes', $pdf);
        $this->assertSame([
            'type' => 'template',
            'templateId' => 'invoice.standard',
        ], $client->fields['source']);
        $this->assertSame(['invoice' => ['number' => 'INV-1']], $client->fields['context']);
        $this->assertSame(['reference' => 'INV-1'], $client->fields['metadata']);
        $this->assertTrue($client->fields['store_pdf']);
        $this->assertSame([
            'url' => 'https://example.com/bladepdf',
            'secret' => 'whsec_request',
            'events' => ['pdf.rendered'],
        ], $client->fields['webhook']);
        $this->assertSame('A4', $client->fields['pdf_options']['format']);
        $this->assertSame('10px', $client->fields['pdf_options']['margin']['top']);
        $this->assertSame('15px', $client->fields['pdf_options']['margin']['bottom']);
        $this->assertTrue($client->fields['pdf_options']['printBackground']);
        $this->assertArrayNotHasKey('html', $client->fields);
        $this->assertArrayNotHasKey('header_html', $client->fields);
        $this->assertArrayNotHasKey('footer_html', $client->fields);
        $this->assertCount(1, $client->assets);
        $this->assertSame('asset:///logo.png', $client->assets[0]->fieldName);
    }

    public function test_it_sends_html_source_metadata_store_flag_and_fluent_options(): void
    {
        $client = new CapturingBladePdfClient();

        $this->pendingRender($client)
            ->fromHtml('<h1>Hello</h1>')
            ->templateName('Ad hoc invoice')
            ->reference('REF-1')
            ->dontStorePdf()
            ->landscape()
            ->scale(1.2)
            ->pages('1-2')
            ->taggedPdf()
            ->preferCssPageSize()
            ->waitForFonts()
            ->outline()
            ->render();

        $this->assertSame(['type' => 'html'], $client->fields['source']);
        $this->assertSame('<h1>Hello</h1>', $client->fields['html']);
        $this->assertSame([
            'reference' => 'REF-1',
            'template_name' => 'Ad hoc invoice',
        ], $client->fields['metadata']);
        $this->assertFalse($client->fields['store_pdf']);
        $this->assertTrue($client->fields['pdf_options']['landscape']);
        $this->assertSame(1.2, $client->fields['pdf_options']['scale']);
        $this->assertSame('1-2', $client->fields['pdf_options']['pageRanges']);
        $this->assertTrue($client->fields['pdf_options']['tagged']);
        $this->assertTrue($client->fields['pdf_options']['preferCSSPageSize']);
        $this->assertTrue($client->fields['pdf_options']['waitForFonts']);
        $this->assertTrue($client->fields['pdf_options']['outline']);
    }

    public function test_it_submits_an_async_render_and_returns_its_identifiers(): void
    {
        $client = new CapturingBladePdfClient();

        $submission = $this->pendingRender($client)
            ->fromTemplate('invoice.standard', [
                'invoice' => ['number' => 'INV-1'],
            ])
            ->reference('INV-1')
            ->storePdf()
            ->webhook('https://example.com/bladepdf', 'whsec_request')
            ->async();

        $this->assertSame('request-async-1', $submission->requestId);
        $this->assertSame('INV-1', $submission->reference);
        $this->assertSame([
            'type' => 'template',
            'templateId' => 'invoice.standard',
        ], $client->fields['source']);
        $this->assertSame(['invoice' => ['number' => 'INV-1']], $client->fields['context']);
        $this->assertSame(['reference' => 'INV-1'], $client->fields['metadata']);
        $this->assertTrue($client->fields['store_pdf']);
        $this->assertSame('https://example.com/bladepdf', $client->fields['webhook']['url']);
    }

    public function test_render_returns_pdf_bytes_and_stored_pdf_url(): void
    {
        $client = new CapturingBladePdfClient();

        $result = $this->pendingRender($client)
            ->fromHtml('<h1>Hello</h1>')
            ->storePdf()
            ->render();

        $this->assertSame('pdf-bytes', $result->pdf());
        $this->assertSame('https://app.bladepdf.test/pdf/workspace-1/request-1.pdf', $result->storedPdfUrl());
    }

    public function test_asset_resolution_can_be_disabled_for_a_single_render(): void
    {
        $client = new CapturingBladePdfClient();
        $publicPath = $this->createTemporaryPublicPath();

        app()->usePublicPath($publicPath);

        file_put_contents($publicPath.'/logo.png', 'image-bytes');

        $this->pendingRender($client)
            ->fromHtml('<img src="/logo.png">')
            ->withoutAssetResolution()
            ->render();

        $this->assertSame('<img src="/logo.png">', $client->fields['html']);
        $this->assertCount(0, $client->assets);
    }

    public function test_global_asset_resolution_config_can_be_disabled_while_manual_assets_still_upload(): void
    {
        config()->set('bladepdf.auto_resolve_assets', false);

        $client = new CapturingBladePdfClient();
        $publicPath = $this->createTemporaryPublicPath();
        $assetPath = $this->createTemporaryAsset('logo-bytes');

        app()->usePublicPath($publicPath);

        file_put_contents($publicPath.'/logo.png', 'image-bytes');

        $this->pendingRender($client)
            ->fromHtml('<img src="/logo.png"><img src="asset:///brand-logo.png">')
            ->withAsset($assetPath, 'brand-logo.png')
            ->render();

        $this->assertSame('<img src="/logo.png"><img src="asset:///brand-logo.png">', $client->fields['html']);
        $this->assertCount(1, $client->assets);
        $this->assertSame('asset:///brand-logo.png', $client->assets[0]->fieldName);
    }

    public function test_async_render_requires_pdf_storage(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);
        $this->expectExceptionMessage('BladePDF async renders require storePdf()');

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromTemplate('invoice.standard')
            ->async();
    }

    public function test_it_renders_local_views_as_html_source(): void
    {
        $client = new CapturingBladePdfClient();
        $viewPath = $this->createTemporaryView('<h1>Invoice {{ $invoice }}</h1>');

        app('view')->addLocation(dirname($viewPath));

        $this->pendingRender($client)
            ->fromView('invoice', ['invoice' => 'INV-1'])
            ->render();

        $this->assertSame(['type' => 'html'], $client->fields['source']);
        $this->assertSame('<h1>Invoice INV-1</h1>', trim($client->fields['html']));
    }

    public function test_template_render_rejects_header_and_footer_overrides(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromTemplate('invoice.standard')
            ->withHeaderHtml('<p>Header</p>')
            ->render();
    }

    public function test_template_render_rejects_template_name_metadata(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromTemplate('invoice.standard')
            ->templateName('Should not be accepted')
            ->render();
    }

    public function test_asset_override_targets_match_gateway_field_name_rules(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->overrideAsset('images/logo.png', __FILE__);
    }

    public function test_webhook_configuration_is_validated(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromHtml('<h1>Hello</h1>')
            ->webhook('ftp://example.com/hook', 'whsec_test')
            ->render();
    }

    public function test_webhook_events_are_validated(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromHtml('<h1>Hello</h1>')
            ->webhook('https://example.com/hook', 'whsec_test', ['invoice.paid'])
            ->render();
    }

    public function test_client_encodes_booleans_and_empty_context_for_multipart_fields(): void
    {
        $client = new EncodingBladePdfClient();

        $this->assertSame('false', $client->encodePublicField(false));
        $this->assertSame('true', $client->encodePublicField(true));
        $this->assertSame('{}', $client->encodePublicJson([], true));
    }

    protected function pendingRender(CapturingBladePdfClient $client): PendingRender
    {
        return new PendingRender($client, app(AssetResolver::class), app('view'));
    }

    protected function createTemporaryAsset(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bladepdf-asset-');

        file_put_contents($path, $contents);

        return $path;
    }

    protected function createTemporaryView(string $contents): string
    {
        $directory = sys_get_temp_dir().'/bladepdf-views-'.uniqid('', true);

        mkdir($directory);

        $path = $directory.'/invoice.blade.php';

        file_put_contents($path, $contents);

        return $path;
    }

    protected function createTemporaryPublicPath(): string
    {
        $directory = sys_get_temp_dir().'/bladepdf-public-'.uniqid('', true);

        mkdir($directory);

        return $directory;
    }
}

class CapturingBladePdfClient extends BladePdfClient
{
    /**
     * @var array<string, mixed>
     */
    public array $fields = [];

    /**
     * @var array<int, mixed>
     */
    public array $assets = [];

    public function __construct()
    {
    }

    public function render(array $fields, array $assets = []): RenderResult
    {
        $this->fields = array_filter($fields, static fn (mixed $value): bool => $value !== null);
        $this->assets = $assets;

        return new RenderResult(
            'pdf-bytes',
            'https://app.bladepdf.test/pdf/workspace-1/request-1.pdf',
        );
    }

    public function renderAsync(array $fields, array $assets = []): RenderSubmission
    {
        $this->fields = array_filter($fields, static fn (mixed $value): bool => $value !== null);
        $this->assets = $assets;

        return new RenderSubmission('request-async-1', $this->fields['metadata']['reference'] ?? null);
    }
}

class EncodingBladePdfClient extends BladePdfClient
{
    public function __construct()
    {
    }

    public function encodePublicField(mixed $value): string
    {
        return $this->encodeField($value);
    }

    public function encodePublicJson(mixed $value, bool $emptyArrayAsObject = false): string
    {
        return $this->encodeJson($value, $emptyArrayAsObject);
    }
}
