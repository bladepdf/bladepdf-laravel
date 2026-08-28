<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Assets\AssetResolver;
use BladePDF\Assets\AssetResolverOptions;
use BladePDF\Contracts\RenderClient;
use BladePDF\Exceptions\InvalidRenderConfigurationException;
use BladePDF\Laravel\PendingRender;
use BladePDF\RenderRequest;
use BladePDF\RenderResult as CoreRenderResult;
use BladePDF\RenderSubmission;

class PendingRenderTest extends TestCase
{
    public function test_it_delegates_generic_options_to_the_core_request(): void
    {
        $client = new CapturingRenderClient();

        $result = $this->pendingRender($client)
            ->fromTemplate('invoice.standard', ['invoice' => ['number' => 'INV-1']])
            ->withData(['customer' => ['name' => 'Ada']])
            ->reference('INV-1')
            ->storePdf()
            ->webhook('https://example.com/bladepdf', 'whsec_request', ['pdf.rendered'])
            ->format('A4')
            ->margins(10, 10, 15, 10)
            ->showBackground()
            ->render();

        $request = $client->lastRequest;

        $this->assertNotNull($request);
        $this->assertSame(['type' => 'template', 'templateId' => 'invoice.standard'], $request->source);
        $this->assertSame([
            'invoice' => ['number' => 'INV-1'],
            'customer' => ['name' => 'Ada'],
        ], $request->context);
        $this->assertSame(['reference' => 'INV-1'], $request->metadata);
        $this->assertTrue($request->storePdf);
        $this->assertSame('A4', $request->pdfOptions['format']);
        $this->assertSame('15px', $request->pdfOptions['margin']['bottom']);
        $this->assertTrue($request->pdfOptions['printBackground']);
        $this->assertSame('pdf-bytes', $result->pdf());
        $this->assertSame('request-sync-1', $result->requestId());
    }

    public function test_blade_views_headers_and_footers_are_rendered_only_on_submission(): void
    {
        $client = new CapturingRenderClient();
        $viewDirectory = $this->makeDirectory('bladepdf-views-');

        file_put_contents($viewDirectory.'/invoice.blade.php', '<h1>{{ $invoice }}</h1>');
        file_put_contents($viewDirectory.'/header.blade.php', '<header>{{ $label }}</header>');
        file_put_contents($viewDirectory.'/footer.blade.php', '<footer>{{ $page }}</footer>');
        app('view')->addLocation($viewDirectory);

        $pending = $this->pendingRender($client)
            ->fromView('invoice', ['invoice' => 'original'])
            ->withData(['invoice' => 'INV-42'])
            ->withHeader('header', ['label' => 'Invoice'])
            ->withFooter('footer', ['page' => '1']);

        file_put_contents($viewDirectory.'/invoice.blade.php', '<h1>Deferred {{ $invoice }}</h1>');

        $this->assertNull($client->lastRequest);

        $pending->render();

        $this->assertSame('<h1>Deferred INV-42</h1>', trim((string) $client->lastRequest?->html));
        $this->assertSame('<header>Invoice</header>', trim((string) $client->lastRequest?->headerHtml));
        $this->assertSame('<footer>1</footer>', trim((string) $client->lastRequest?->footerHtml));
    }

    public function test_html_assets_use_the_configured_core_pipeline(): void
    {
        $root = $this->makeDirectory('bladepdf-public-');
        mkdir($root.'/css');
        file_put_contents($root.'/logo.png', 'image-bytes');
        file_put_contents($root.'/css/app.css', 'body{background:url("../logo.png")}');

        $client = new CapturingRenderClient();
        $pending = new PendingRender(
            $client,
            new AssetResolver(new AssetResolverOptions(documentRoot: $root, searchRoots: [$root])),
            app('view'),
        );

        $pending->fromHtml('<link rel="stylesheet" href="/css/app.css"><img src="/logo.png">')->render();

        $this->assertStringNotContainsString('/css/app.css', (string) $client->lastRequest?->html);
        $this->assertStringContainsString('asset:///', (string) $client->lastRequest?->html);
        $this->assertCount(2, $client->lastRequest?->assets ?? []);
    }

    public function test_async_render_requires_storage_and_returns_core_submission(): void
    {
        $client = new CapturingRenderClient();

        $submission = $this->pendingRender($client)
            ->fromTemplate('invoice.standard')
            ->storePdf()
            ->async();

        $this->assertSame('request-async-1', $submission->requestId);
        $this->assertTrue($client->async);

        $this->expectException(InvalidRenderConfigurationException::class);
        $this->pendingRender(new CapturingRenderClient())->fromHtml('<p>No store</p>')->async();
    }

    private function pendingRender(RenderClient $client): PendingRender
    {
        return new PendingRender($client, new AssetResolver(), app('view'));
    }

    private function makeDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir().'/'.$prefix.uniqid('', true);
        mkdir($directory, 0777, true);

        return $directory;
    }
}

final class CapturingRenderClient implements RenderClient
{
    public ?RenderRequest $lastRequest = null;

    public bool $async = false;

    public function render(RenderRequest $request): CoreRenderResult
    {
        $this->lastRequest = $request;

        return new CoreRenderResult(
            pdf: 'pdf-bytes',
            storedPdfUrl: 'https://app.bladepdf.test/pdf/request-sync-1.pdf',
            requestId: 'request-sync-1',
        );
    }

    public function renderAsync(RenderRequest $request): RenderSubmission
    {
        $this->lastRequest = $request;
        $this->async = true;

        return new RenderSubmission('request-async-1', $request->metadata['reference'] ?? null);
    }
}
