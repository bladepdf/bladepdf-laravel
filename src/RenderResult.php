<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

final readonly class RenderResult
{
    public function __construct(
        private string $pdf,
        public ?string $storedPdfUrl = null,
    ) {
    }

    public function pdf(): string
    {
        return $this->pdf;
    }

    public function storedPdfUrl(): ?string
    {
        return $this->storedPdfUrl;
    }

    public function response(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->contentDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $filename,
            ),
        ]);
    }

    public function download(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->contentDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
            ),
        ]);
    }

    public function save(string $path): string
    {
        file_put_contents($path, $this->pdf);

        return $path;
    }

    public function base64Pdf(): string
    {
        return base64_encode($this->pdf);
    }

    public function base64(): string
    {
        return $this->base64Pdf();
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
