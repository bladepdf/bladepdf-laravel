<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\BladePdfClient;
use BladePDF\Laravel\Exceptions\InvalidRenderConfigurationException;
use BladePDF\Laravel\PendingRender;
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
            ->pdf();

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

    public function test_it_renders_local_views_as_html_source(): void
    {
        $client = new CapturingBladePdfClient();
        $viewPath = $this->createTemporaryView('<h1>Invoice {{ $invoice }}</h1>');

        app('view')->addLocation(dirname($viewPath));

        $this->pendingRender($client)
            ->fromView('invoice', ['invoice' => 'INV-1'])
            ->pdf();

        $this->assertSame(['type' => 'html'], $client->fields['source']);
        $this->assertSame('<h1>Invoice INV-1</h1>', trim($client->fields['html']));
    }

    public function test_template_render_rejects_header_and_footer_overrides(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromTemplate('invoice.standard')
            ->withHeaderHtml('<p>Header</p>')
            ->pdf();
    }

    public function test_template_render_rejects_template_name_metadata(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromTemplate('invoice.standard')
            ->templateName('Should not be accepted')
            ->pdf();
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
            ->pdf();
    }

    public function test_webhook_events_are_validated(): void
    {
        $this->expectException(InvalidRenderConfigurationException::class);

        $this->pendingRender(new CapturingBladePdfClient())
            ->fromHtml('<h1>Hello</h1>')
            ->webhook('https://example.com/hook', 'whsec_test', ['invoice.paid'])
            ->pdf();
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

    public function render(array $fields, array $assets = []): string
    {
        $this->fields = array_filter($fields, static fn (mixed $value): bool => $value !== null);
        $this->assets = $assets;

        return 'pdf-bytes';
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
