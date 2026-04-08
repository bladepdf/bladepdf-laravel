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

    public function render(string $view, array $data = []): PendingRender
    {
        return $this->make()->render($view, $data);
    }

    public function html(string $html): PendingRender
    {
        return $this->make()->html($html);
    }
}
