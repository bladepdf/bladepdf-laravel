<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Support;

use BladePDF\Laravel\Exceptions\AssetNotFoundException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AssetResolver
{
    /**
     * MIME types that browsers require to be served consistently.
     *
     * PHP's fileinfo identifies text-based web assets such as CSS and JavaScript
     * as text/plain on some operating systems. Chromium then refuses to use the
     * asset when strict MIME checking is enabled, so prefer the file extension
     * for known web asset formats and keep fileinfo as the fallback.
     *
     * @var array<string, string>
     */
    protected const WEB_ASSET_MIME_TYPES = [
        'avif' => 'image/avif',
        'bmp' => 'image/bmp',
        'css' => 'text/css',
        'eot' => 'application/vnd.ms-fontobject',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'map' => 'application/json',
        'mjs' => 'text/javascript',
        'otf' => 'font/otf',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'ttf' => 'font/ttf',
        'wasm' => 'application/wasm',
        'webmanifest' => 'application/manifest+json',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    protected CssAssetRewriter $cssRewriter;

    public function __construct(protected ConfigRepository $config)
    {
        $this->cssRewriter = new CssAssetRewriter($this);
    }

    /**
     * @param  array<int, array{path:string,target?:string,mime?:string}>  $manualAssets
     * @return array{html:string,header_html:?string,footer_html:?string,assets:array<int, ResolvedAsset>}
     */
    public function resolve(
        string $html,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
        array $manualAssets = [],
        ?bool $autoResolveAssets = null,
    ): array
    {
        $assetBag = new AssetBag();

        $autoResolveAssets ??= $this->shouldAutoResolveAssets();

        if ($autoResolveAssets) {
            $html = $this->rewriteHtml($html, $assetBag);
            $headerHtml = $headerHtml !== null ? $this->rewriteHtml($headerHtml, $assetBag) : null;
            $footerHtml = $footerHtml !== null ? $this->rewriteHtml($footerHtml, $assetBag) : null;
        }

        foreach ($manualAssets as $manualAsset) {
            $path = $manualAsset['path'];
            $target = $manualAsset['target'] ?? null;
            $mime = $manualAsset['mime'] ?? null;

            $resolvedPath = realpath($path) ?: $path;

            if (! is_file($resolvedPath)) {
                throw new AssetNotFoundException(sprintf('Manual BladePDF asset [%s] was not found.', $path));
            }

            $contents = (string) file_get_contents($resolvedPath);
            $filename = basename($resolvedPath);
            $mimeType = $mime ?: ($this->guessMimeType($resolvedPath) ?: 'application/octet-stream');
            $fieldName = $target ? 'asset:///'.ltrim($target, '/') : null;

            if ($autoResolveAssets && $this->isCssFile($resolvedPath)) {
                $contents = $this->cssRewriter->rewrite($contents, $assetBag, dirname($resolvedPath));
            }

            $assetBag->put($contents, $filename, $mimeType, $resolvedPath, $fieldName);
        }

        return [
            'html' => $html,
            'header_html' => $headerHtml,
            'footer_html' => $footerHtml,
            'assets' => $assetBag->all(),
        ];
    }

    protected function shouldAutoResolveAssets(): bool
    {
        return (bool) $this->config->get('bladepdf.auto_resolve_assets', true);
    }

    public function rewriteHtml(string $html, AssetBag $assetBag): string
    {
        $html = preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            function (array $matches) use ($assetBag): string {
                $rewrittenCss = $this->cssRewriter->rewrite($matches[2], $assetBag, public_path());

                return $matches[1].$rewrittenCss.$matches[3];
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\sstyle=(\"|\')(.*?)\1/is',
            function (array $matches) use ($assetBag): string {
                $rewrittenCss = $this->cssRewriter->rewrite($matches[2], $assetBag, public_path());

                return ' style='.$matches[1].$rewrittenCss.$matches[1];
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\s(srcset)=(\"|\')(.*?)\2/is',
            function (array $matches) use ($assetBag): string {
                $candidates = array_map('trim', explode(',', $matches[3]));

                $rewritten = array_map(function (string $candidate) use ($assetBag): string {
                    $parts = preg_split('/\s+/', trim($candidate), 2) ?: [];
                    $reference = $parts[0] ?? '';
                    $descriptor = $parts[1] ?? '';
                    $replacement = $this->registerReference($reference, $assetBag, public_path());

                    if ($replacement === null) {
                        return $candidate;
                    }

                    return trim($replacement.' '.$descriptor);
                }, $candidates);

                return ' '.$matches[1].'='.$matches[2].implode(', ', $rewritten).$matches[2];
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\s(src|href|poster|data-src|data-href)=(\"|\')(.*?)\2/is',
            function (array $matches) use ($assetBag): string {
                $replacement = $this->registerReference($matches[3], $assetBag, public_path());

                if ($replacement === null) {
                    return $matches[0];
                }

                return ' '.$matches[1].'='.$matches[2].$replacement.$matches[2];
            },
            $html,
        ) ?? $html;

        return $html;
    }

    public function registerReference(string $reference, AssetBag $assetBag, ?string $baseDirectory = null): ?string
    {
        $reference = trim(html_entity_decode($reference, ENT_QUOTES | ENT_HTML5));

        if ($reference === '' || $this->shouldSkipReference($reference)) {
            return null;
        }

        $resolvedPath = $this->resolvePathForReference($reference, $baseDirectory);

        if ($resolvedPath === null) {
            return null;
        }

        $contents = (string) file_get_contents($resolvedPath);
        $filename = basename($resolvedPath);
        $mimeType = $this->guessMimeType($resolvedPath) ?: 'application/octet-stream';

        if ($this->isCssFile($resolvedPath)) {
            $contents = $this->cssRewriter->rewrite($contents, $assetBag, dirname($resolvedPath));
        }

        return $assetBag->put($contents, $filename, $mimeType, $resolvedPath);
    }

    protected function resolvePathForReference(string $reference, ?string $baseDirectory = null): ?string
    {
        $parsed = parse_url($reference);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));

        if ($scheme === 'file') {
            $path = $parsed['path'] ?? null;

            return $path && is_file($path) ? (realpath($path) ?: $path) : null;
        }

        if (in_array($scheme, ['http', 'https'], true) && ! $this->isLocalHost((string) ($parsed['host'] ?? ''))) {
            return null;
        }

        $path = $parsed['path'] ?? $reference;

        if ($path === '' || $path === '/') {
            return null;
        }

        $decodedPath = rawurldecode($path);

        $candidates = [];

        if ($this->looksLikeAbsoluteFilesystemPath($decodedPath)) {
            $candidates[] = $decodedPath;
        }

        if (Str::startsWith($decodedPath, '/')) {
            $candidates[] = public_path(ltrim($decodedPath, '/'));
            $candidates[] = base_path(ltrim($decodedPath, '/'));
        } else {
            if ($baseDirectory) {
                $candidates[] = $baseDirectory.DIRECTORY_SEPARATOR.$decodedPath;
            }

            $candidates[] = public_path($decodedPath);
            $candidates[] = base_path($decodedPath);
        }

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);

            if ($resolved && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    protected function shouldSkipReference(string $reference): bool
    {
        $lower = strtolower($reference);

        return Str::startsWith($lower, [
            '#',
            'data:',
            'blob:',
            'javascript:',
            'mailto:',
            'tel:',
            'asset:///',
        ]);
    }

    protected function looksLikeAbsoluteFilesystemPath(string $path): bool
    {
        return Str::startsWith($path, ['/']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }

    protected function isLocalHost(string $host): bool
    {
        if ($host === '') {
            return true;
        }

        $localHosts = array_map('strtolower', (array) $this->config->get('bladepdf.local_hosts', []));

        return in_array(strtolower($host), $localHosts, true);
    }

    protected function guessMimeType(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (isset(self::WEB_ASSET_MIME_TYPES[$extension])) {
            return self::WEB_ASSET_MIME_TYPES[$extension];
        }

        return File::mimeType($path) ?: null;
    }

    protected function isCssFile(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'css';
    }
}
