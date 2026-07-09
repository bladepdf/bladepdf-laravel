<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use Illuminate\Http\Response;

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
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
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
}
