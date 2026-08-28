<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Sources;

use BladePDF\Contracts\HtmlSource;
use Illuminate\Contracts\View\Factory as ViewFactory;

final readonly class BladeViewSource implements HtmlSource
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private ViewFactory $viewFactory,
        private string $view,
        private array $data = [],
    ) {
    }

    public function render(): string
    {
        return $this->viewFactory->make($this->view, $this->data)->render();
    }

    public function baseDirectory(): ?string
    {
        return null;
    }

    /** @param array<string, mixed> $data */
    public function withData(array $data): self
    {
        return new self($this->viewFactory, $this->view, array_replace_recursive($this->data, $data));
    }
}
