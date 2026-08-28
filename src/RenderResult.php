<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

final readonly class RenderResult extends \BladePDF\RenderResult
{
    public function response(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->contentDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $filename,
            ),
        ]);
    }

    public function download(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->contentDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
            ),
        ]);
    }

    private function contentDisposition(string $disposition, ?string $filename): string
    {
        $filename = $filename === null || trim($filename) === ''
            ? 'document.pdf'
            : $filename;

        $filename = str_replace(['/', '\\'], '_', $filename);
        $fallback = Str::ascii($filename);
        $fallback = preg_replace('/[^\x20-\x7e]/', '', $fallback) ?? '';
        $fallback = str_replace('%', '_', $fallback);

        if (trim($fallback) === '') {
            $fallback = 'document.pdf';
        }

        return HeaderUtils::makeDisposition($disposition, $filename, $fallback);
    }
}
