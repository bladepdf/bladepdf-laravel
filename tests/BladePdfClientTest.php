<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\BladePdfClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class BladePdfClientTest extends TestCase
{
    public function test_it_requests_a_sync_render_and_reads_stored_pdf_link_header(): void
    {
        Http::fake([
            'http://localhost/render' => Http::response('pdf-bytes', 200, [
                'Link' => '<https://app.bladepdf.test/pdf/workspace-1/request-1.pdf?expires=1&signature=test>; rel="stored-pdf"; type="application/pdf"',
            ]),
        ]);

        $result = app(BladePdfClient::class)->render([
            'source' => ['type' => 'template', 'templateId' => 'invoice.standard'],
            'metadata' => ['reference' => 'INV-1'],
            'store_pdf' => true,
        ]);

        $this->assertSame('pdf-bytes', $result->pdf());
        $this->assertSame(
            'https://app.bladepdf.test/pdf/workspace-1/request-1.pdf?expires=1&signature=test',
            $result->storedPdfUrl(),
        );

        Http::assertSent(static function (Request $request): bool {
            return $request->url() === 'http://localhost/render'
                && $request->hasHeader('Accept', 'application/pdf');
        });
    }

    public function test_it_requests_and_parses_an_async_render_submission(): void
    {
        Http::fake([
            'http://localhost/render' => Http::response([
                'request_id' => 'request-1',
                'reference' => 'INV-1',
            ], 202),
        ]);

        $submission = app(BladePdfClient::class)->renderAsync([
            'source' => ['type' => 'template', 'templateId' => 'invoice.standard'],
            'metadata' => ['reference' => 'INV-1'],
        ]);

        $this->assertSame('request-1', $submission->requestId);
        $this->assertSame('INV-1', $submission->reference);

        Http::assertSent(static function (Request $request): bool {
            return $request->url() === 'http://localhost/render'
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('Prefer', 'respond-async');
        });
    }
}
