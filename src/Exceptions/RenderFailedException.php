<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Exceptions;

class RenderFailedException extends BladePdfException
{
    public static function fromResponse(int $status, string $body): self
    {
        return new self(sprintf('BladePDF render request failed with status %d. Response: %s', $status, $body));
    }
}
