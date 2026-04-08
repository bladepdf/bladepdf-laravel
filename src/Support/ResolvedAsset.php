<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Support;

class ResolvedAsset
{
    public function __construct(
        public readonly string $fieldName,
        public readonly string $filename,
        public readonly string $contents,
        public readonly string $mimeType,
        public readonly ?string $sourcePath = null,
    ) {
    }

    public function toMultipartPart(): array
    {
        return [
            'name' => $this->fieldName,
            'contents' => $this->contents,
            'filename' => $this->filename,
            'headers' => [
                'Content-Type' => $this->mimeType,
            ],
        ];
    }
}
