<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Exceptions\InvalidRenderConfigurationException;
use BladePDF\Laravel\Support\AssetResolver;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;

class PendingRender
{
    protected const SOURCE_HTML = 'html';

    protected const SOURCE_TEMPLATE = 'template';

    protected string $source = self::SOURCE_HTML;

    protected ?string $view = null;

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?string $rawHtml = null;

    protected ?string $templateId = null;

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

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

    protected ?string $waitUntil = null;

    protected ?string $waitFunction = null;

    protected ?string $emulateMedia = null;

    protected ?string $reference = null;

    protected ?string $templateName = null;

    protected ?bool $storePdf = null;

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

    public function fromView(string $view, array $data = []): self
    {
        $this->source = self::SOURCE_HTML;
        $this->view = $view;
        $this->data = $data;
        $this->rawHtml = null;
        $this->templateId = null;
        $this->context = [];

        return $this;
    }

    public function fromHtml(string $html): self
    {
        $this->source = self::SOURCE_HTML;
        $this->rawHtml = $html;
        $this->view = null;
        $this->templateId = null;
        $this->context = [];

        return $this;
    }

    /**
     * Render a template stored in BladePDF cloud storage.
     *
     * @param  array<string, mixed>  $context
     */
    public function fromTemplate(string $templateId, array $context = []): self
    {
        if (trim($templateId) === '') {
            throw new InvalidRenderConfigurationException('BladePDF template id cannot be empty.');
        }

        $this->source = self::SOURCE_TEMPLATE;
        $this->templateId = $templateId;
        $this->context = $context;

        $this->view = null;
        $this->data = [];
        $this->rawHtml = null;
        $this->headerView = null;
        $this->headerData = [];
        $this->headerHtml = null;
        $this->footerView = null;
        $this->footerData = [];
        $this->footerHtml = null;

        return $this;
    }

    public function withData(array $data): self
    {
        if ($this->source === self::SOURCE_TEMPLATE) {
            return $this->withContext($data);
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Replace the context used by a cloud template render.
     *
     * @param  array<string, mixed>  $context
     */
    public function context(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Merge additional context into a cloud template render.
     *
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): self
    {
        $this->context = array_replace_recursive($this->context, $context);

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
            $asset['target'] = $this->normalizeAssetTarget($target);
        }

        if ($mime !== null) {
            $asset['mime'] = $mime;
        }

        $this->manualAssets[] = $asset;

        return $this;
    }

    public function overrideAsset(string $assetKey, string $path, ?string $mime = null): self
    {
        return $this->withAsset($path, $assetKey, $mime);
    }

    public function reference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function withReference(?string $reference): self
    {
        return $this->reference($reference);
    }

    public function templateName(?string $templateName): self
    {
        $this->templateName = $templateName;

        return $this;
    }

    public function withTemplateName(?string $templateName): self
    {
        return $this->templateName($templateName);
    }

    /**
     * @param  array{reference?:?string,template_name?:?string}  $metadata
     */
    public function metadata(array $metadata): self
    {
        return $this->withMetadata($metadata);
    }

    /**
     * @param  array{reference?:?string,template_name?:?string}  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        $unsupported = array_diff(array_keys($metadata), ['reference', 'template_name']);

        if ($unsupported !== []) {
            throw new InvalidRenderConfigurationException(sprintf(
                'Unsupported BladePDF metadata field(s): %s.',
                implode(', ', $unsupported),
            ));
        }

        if (array_key_exists('reference', $metadata)) {
            $this->reference($metadata['reference'] !== null ? (string) $metadata['reference'] : null);
        }

        if (array_key_exists('template_name', $metadata)) {
            $this->templateName($metadata['template_name'] !== null ? (string) $metadata['template_name'] : null);
        }

        return $this;
    }

    public function storePdf(bool $store = true): self
    {
        $this->storePdf = $store;

        return $this;
    }

    public function dontStorePdf(): self
    {
        return $this->storePdf(false);
    }

    public function format(string $format): self
    {
        return $this->withOptions(['format' => $format]);
    }

    public function paperSize(string|int|float $width, string|int|float $height, string $unit = 'px'): self
    {
        return $this->withOptions([
            'width' => $this->formatPdfLength($width, $unit),
            'height' => $this->formatPdfLength($height, $unit),
        ]);
    }

    public function margins(
        string|int|float $top,
        string|int|float $right,
        string|int|float $bottom,
        string|int|float $left,
        string $unit = 'px',
    ): self {
        return $this->withOptions([
            'margin' => [
                'top' => $this->formatPdfLength($top, $unit),
                'right' => $this->formatPdfLength($right, $unit),
                'bottom' => $this->formatPdfLength($bottom, $unit),
                'left' => $this->formatPdfLength($left, $unit),
            ],
        ]);
    }

    public function landscape(bool $landscape = true): self
    {
        return $this->withOptions(['landscape' => $landscape]);
    }

    public function portrait(): self
    {
        return $this->landscape(false);
    }

    public function showBackground(bool $show = true): self
    {
        return $this->withOptions(['printBackground' => $show]);
    }

    public function hideBackground(): self
    {
        return $this->showBackground(false);
    }

    public function transparentBackground(bool $transparent = true): self
    {
        return $this->withOptions(['omitBackground' => $transparent]);
    }

    public function scale(float $scale): self
    {
        if ($scale < 0.1 || $scale > 2.0) {
            throw new InvalidRenderConfigurationException('BladePDF scale must be between 0.1 and 2.0.');
        }

        return $this->withOptions(['scale' => $scale]);
    }

    public function pages(string $pageRanges): self
    {
        return $this->pageRanges($pageRanges);
    }

    public function pageRanges(string $pageRanges): self
    {
        return $this->withOptions(['pageRanges' => $pageRanges]);
    }

    public function taggedPdf(bool $tagged = true): self
    {
        return $this->withOptions(['tagged' => $tagged]);
    }

    public function preferCssPageSize(bool $prefer = true): self
    {
        return $this->withOptions(['preferCSSPageSize' => $prefer]);
    }

    public function waitForFonts(bool $wait = true): self
    {
        return $this->withOptions(['waitForFonts' => $wait]);
    }

    public function outline(bool $outline = true): self
    {
        return $this->withOptions(['outline' => $outline]);
    }

    public function pdf(): string
    {
        if ($this->source === self::SOURCE_TEMPLATE) {
            return $this->renderTemplatePdf();
        }

        $bodyHtml = $this->rawHtml ?? $this->renderView($this->view, $this->data);
        $headerHtml = $this->headerHtml ?? ($this->headerView ? $this->renderView($this->headerView, $this->headerData) : null);
        $footerHtml = $this->footerHtml ?? ($this->footerView ? $this->renderView($this->footerView, $this->footerData) : null);

        $resolved = $this->assetResolver->resolve($bodyHtml, $headerHtml, $footerHtml, $this->manualAssets);

        return $this->client->render([
            'source' => ['type' => self::SOURCE_HTML],
            'wait_until' => $this->waitUntil,
            'wait_function' => $this->waitFunction,
            'emulate_media' => $this->emulateMedia,
            'metadata' => $this->metadataForRequest(),
            'store_pdf' => $this->storePdf,
            'html' => $resolved['html'],
            'header_html' => $resolved['header_html'],
            'footer_html' => $resolved['footer_html'],
            'pdf_options' => $this->pdfOptionsForRequest(),
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

    public function base64Pdf(): string
    {
        return base64_encode($this->pdf());
    }

    public function base64(): string
    {
        return $this->base64Pdf();
    }

    protected function renderTemplatePdf(): string
    {
        if ($this->templateId === null) {
            throw new InvalidRenderConfigurationException('BladePDF template source requires a template id.');
        }

        if ($this->headerView !== null || $this->headerHtml !== null || $this->footerView !== null || $this->footerHtml !== null) {
            throw new InvalidRenderConfigurationException('BladePDF cloud template renders do not support header_html or footer_html overrides.');
        }

        $resolved = $this->assetResolver->resolve('', null, null, $this->manualAssets);

        return $this->client->render([
            'source' => [
                'type' => self::SOURCE_TEMPLATE,
                'templateId' => $this->templateId,
            ],
            'context' => $this->context,
            'wait_until' => $this->waitUntil,
            'wait_function' => $this->waitFunction,
            'emulate_media' => $this->emulateMedia,
            'metadata' => $this->metadataForRequest(),
            'store_pdf' => $this->storePdf,
            'pdf_options' => $this->pdfOptionsForRequest(),
        ], $resolved['assets']);
    }

    protected function renderView(?string $view, array $data = []): string
    {
        if ($view === null) {
            return '';
        }

        return $this->viewFactory->make($view, $data)->render();
    }

    /**
     * @return array{reference?:string,template_name?:string}|null
     */
    protected function metadataForRequest(): ?array
    {
        if ($this->source === self::SOURCE_TEMPLATE && $this->templateName !== null) {
            throw new InvalidRenderConfigurationException('BladePDF template_name metadata is only supported for HTML renders.');
        }

        $metadata = [];

        if ($this->reference !== null) {
            $metadata['reference'] = $this->reference;
        }

        if ($this->templateName !== null) {
            $metadata['template_name'] = $this->templateName;
        }

        return $metadata === [] ? null : $metadata;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function pdfOptionsForRequest(): ?array
    {
        return $this->pdfOptions === [] ? null : $this->pdfOptions;
    }

    protected function normalizeAssetTarget(string $target): string
    {
        $target = str_starts_with($target, 'asset:///')
            ? substr($target, strlen('asset:///'))
            : $target;

        $target = ltrim($target, '/');

        if ($target === '' || preg_match('/^[A-Za-z0-9._-]+$/', $target) !== 1) {
            throw new InvalidRenderConfigurationException(
                'BladePDF asset override targets may only contain letters, numbers, dots, underscores, and hyphens.',
            );
        }

        return $target;
    }

    protected function formatPdfLength(string|int|float $value, string $unit): string
    {
        if (is_string($value)) {
            return $value;
        }

        return $this->formatNumber($value).$unit;
    }

    protected function formatNumber(int|float $value): string
    {
        return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
    }
}
