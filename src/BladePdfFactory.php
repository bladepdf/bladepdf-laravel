<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Support\AssetResolver;
use Illuminate\Contracts\View\Factory as ViewFactory;

class BladePdfFactory
{
    public function __construct(
        protected BladePdfClient $client,
        protected AssetResolver $assetResolver,
        protected ViewFactory $viewFactory,
    ) {
    }

    public function make(): PendingRender
    {
        return new PendingRender($this->client, $this->assetResolver, $this->viewFactory);
    }

    public function fromView(string $view, array $data = []): PendingRender
    {
        return $this->make()->fromView($view, $data);
    }

    public function fromHtml(string $html): PendingRender
    {
        return $this->make()->fromHtml($html);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function fromTemplate(string $templateId, array $context = []): PendingRender
    {
        return $this->make()->fromTemplate($templateId, $context);
    }
}
