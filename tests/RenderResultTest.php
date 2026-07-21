<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Tests;

use BladePDF\Laravel\RenderResult;

class RenderResultTest extends TestCase
{
    public function test_download_supports_unicode_filenames(): void
    {
        $response = (new RenderResult('pdf-bytes'))->download('Faktura č. 42.pdf');

        $disposition = (string) $response->headers->get('Content-Disposition');

        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString('filename="Faktura c. 42.pdf"', $disposition);
        $this->assertStringContainsString("filename*=utf-8''Faktura%20%C4%8D.%2042.pdf", $disposition);
    }

    public function test_inline_response_escapes_special_characters_in_filename(): void
    {
        $response = (new RenderResult('pdf-bytes'))->response('invoice "final".pdf');

        $this->assertSame(
            'inline; filename="invoice \\"final\\".pdf"',
            $response->headers->get('Content-Disposition'),
        );
    }

    public function test_download_normalizes_path_separators_and_empty_filenames(): void
    {
        $result = new RenderResult('pdf-bytes');

        $this->assertSame(
            'attachment; filename=reports_invoice.pdf',
            $result->download('reports/invoice.pdf')->headers->get('Content-Disposition'),
        );
        $this->assertSame(
            'attachment; filename=document.pdf',
            $result->download('')->headers->get('Content-Disposition'),
        );
    }
}
