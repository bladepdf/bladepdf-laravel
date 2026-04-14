<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use BladePDF\Laravel\Support\AssetResolver;

class PendingRender
{
    protected ?string $view = null;

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?string $rawHtml = null;

    protected ?string $headerView = null;

    /**
     * @var array<string, mixed>
     */
    protected array $headerData = [];

    protected ?string $headerHtml = null;

    protected ?string $footerView = null;

    /**
     * @var array<string, mixed>
     */
    protected array $footerData = [];

    protected ?string $footerHtml = null;

    /**
     * @var array<string, mixed>
     */
    protected array $pdfOptions = [];

    protected ?string $action = 'return';

    protected ?string $waitUntil = null;

    protected ?string $waitFunction = null;

    protected ?string $emulateMedia = null;

    /**
     * @var array<int, array{path:string,target?:string,mime?:string}>
     */
    protected array $manualAssets = [];

    public function __construct(
        protected BladePdfClient $client,
        protected AssetResolver $assetResolver,
        protected ViewFactory $viewFactory,
    ) {
    }

    public function render(string $view, array $data = []): self
    {
        $this->view = $view;
        $this->data = $data;
        $this->rawHtml = null;

        return $this;
    }

    public function html(string $html): self
    {
        $this->rawHtml = $html;
        $this->view = null;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function withHeader(string $view, array $data = []): self
    {
        $this->headerView = $view;
        $this->headerData = $data;
        $this->headerHtml = null;

        return $this;
    }

    public function withHeaderHtml(string $html): self
    {
        $this->headerHtml = $html;
        $this->headerView = null;

        return $this;
    }

    public function withFooter(string $view, array $data = []): self
    {
        $this->footerView = $view;
        $this->footerData = $data;
        $this->footerHtml = null;

        return $this;
    }

    public function withFooterHtml(string $html): self
    {
        $this->footerHtml = $html;
        $this->footerView = null;

        return $this;
    }

    public function withOptions(array $options): self
    {
        $this->pdfOptions = array_replace_recursive($this->pdfOptions, $options);

        return $this;
    }

    public function withAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function waitUntil(?string $waitUntil): self
    {
        $this->waitUntil = $waitUntil;

        return $this;
    }

    public function waitFunction(?string $waitFunction): self
    {
        $this->waitFunction = $waitFunction;

        return $this;
    }

    public function emulateMedia(?string $media): self
    {
        $this->emulateMedia = $media;

        return $this;
    }

    public function withAsset(string $path, ?string $target = null, ?string $mime = null): self
    {
        $asset = ['path' => $path];

        if ($target !== null) {
            $asset['target'] = $target;
        }

        if ($mime !== null) {
            $asset['mime'] = $mime;
        }

        $this->manualAssets[] = $asset;

        return $this;
    }

    public function pdf(): string
    {
        $bodyHtml = $this->rawHtml ?? $this->renderView($this->view, $this->data);
        $headerHtml = $this->headerHtml ?? ($this->headerView ? $this->renderView($this->headerView, $this->headerData) : null);
        $footerHtml = $this->footerHtml ?? ($this->footerView ? $this->renderView($this->footerView, $this->footerData) : null);

        $resolved = $this->assetResolver->resolve($bodyHtml, $headerHtml, $footerHtml, $this->manualAssets);

        return $this->client->render([
            'action' => $this->action,
            'wait_until' => $this->waitUntil,
            'wait_function' => $this->waitFunction,
            'emulate_media' => $this->emulateMedia,
            'html' => $resolved['html'],
            'header_html' => $resolved['header_html'],
            'footer_html' => $resolved['footer_html'],
            'pdf_options' => $this->pdfOptions,
        ], $resolved['assets']);
    }

    public function response(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(?string $filename = 'document.pdf'): Response
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function save(string $path): string
    {
        file_put_contents($path, $this->pdf());

        return $path;
    }

    protected function renderView(?string $view, array $data = []): string
    {
        if ($view === null) {
            return '';
        }

        return $this->viewFactory->make($view, $data)->render();
    }
}
