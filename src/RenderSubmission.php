<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

final readonly class RenderSubmission
{
    public function __construct(
        public string $requestId,
        public ?string $reference = null,
    ) {
    }
}
