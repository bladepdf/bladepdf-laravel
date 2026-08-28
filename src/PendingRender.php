<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Assets\AssetResolver;
use BladePDF\Contracts\RenderClient;
use BladePDF\Laravel\Sources\BladeViewSource;
use Illuminate\Contracts\View\Factory as ViewFactory;

class PendingRender extends \BladePDF\PendingRender
{
    public function __construct(
        RenderClient $client,
        AssetResolver $assetResolver,
        private readonly ViewFactory $viewFactory,
    ) {
        parent::__construct($client, $assetResolver);
    }

    /** @param array<string, mixed> $data */
    public function fromView(string $view, array $data = []): static
    {
        return $this->fromHtmlSource(new BladeViewSource($this->viewFactory, $view, $data));
    }

    /** @param array<string, mixed> $data */
    public function withHeader(string $view, array $data = []): static
    {
        return $this->withHeaderSource(new BladeViewSource($this->viewFactory, $view, $data));
    }

    /** @param array<string, mixed> $data */
    public function withFooter(string $view, array $data = []): static
    {
        return $this->withFooterSource(new BladeViewSource($this->viewFactory, $view, $data));
    }

    /** @param array<string, mixed> $data */
    public function withData(array $data): static
    {
        if ($this->isTemplateSource()) {
            return $this->withContext($data);
        }

        $source = $this->bodySource();

        if ($source instanceof BladeViewSource) {
            $this->fromHtmlSource($source->withData($data));
        }

        return $this;
    }

    public function render(): RenderResult
    {
        $result = parent::render();

        return new RenderResult(
            pdf: $result->pdf(),
            storedPdfUrl: $result->storedPdfUrl(),
            requestId: $result->requestId(),
        );
    }
}
