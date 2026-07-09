<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Exceptions\MissingApiKeyException;
use BladePDF\Laravel\Exceptions\RenderFailedException;
use BladePDF\Laravel\Support\ResolvedAsset;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BladePdfClient
{
    /**
     * @var array<string, array{filename:string,content_type:string,json?:bool}>
     */
    protected const FILE_FIELDS = [
        'html' => [
            'filename' => 'html.html',
            'content_type' => 'text/html; charset=UTF-8',
        ],
        'header_html' => [
            'filename' => 'header.html',
            'content_type' => 'text/html; charset=UTF-8',
        ],
        'footer_html' => [
            'filename' => 'footer.html',
            'content_type' => 'text/html; charset=UTF-8',
        ],
        'context' => [
            'filename' => 'context.json',
            'content_type' => 'application/json; charset=UTF-8',
            'json' => true,
        ],
    ];

    public function __construct(protected ConfigRepository $config)
    {
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, ResolvedAsset>  $assets
     */
    public function render(array $fields, array $assets = []): RenderResult
    {
        $response = $this->sendRenderRequest($fields, $assets);

        if (! $response->successful()) {
            throw RenderFailedException::fromResponse($response->status(), $response->body());
        }

        return new RenderResult(
            pdf: $response->body(),
            storedPdfUrl: $this->storedPdfUrlFromResponse($response),
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, ResolvedAsset>  $assets
     */
    public function renderAsync(array $fields, array $assets = []): RenderSubmission
    {
        $response = $this->sendRenderRequest($fields, $assets, true);

        if ($response->status() !== 202) {
            throw RenderFailedException::fromResponse($response->status(), $response->body());
        }

        $payload = $response->json();
        $requestId = is_array($payload) ? ($payload['request_id'] ?? null) : null;

        if (! is_string($requestId) || trim($requestId) === '') {
            throw new RenderFailedException('BladePDF async render response is missing a valid request_id.');
        }

        $reference = $payload['reference'] ?? null;

        return new RenderSubmission(
            requestId: $requestId,
            reference: is_string($reference) ? $reference : null,
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, ResolvedAsset>  $assets
     */
    protected function sendRenderRequest(array $fields, array $assets = [], bool $async = false): Response
    {
        $baseUrl = rtrim((string) $this->config->get('bladepdf.base_url', 'https://api.bladepdf.com'), '/');
        $apiKey = trim((string) $this->config->get('bladepdf.api_key'));

        if ($apiKey === '') {
            throw new MissingApiKeyException('Missing BladePDF API Key. Set BLADEPDF_API_KEY in your environment.');
        }

        $multipart = [];

        foreach ($fields as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (isset(self::FILE_FIELDS[$name])) {
                $fileField = self::FILE_FIELDS[$name];

                $multipart[] = [
                    'name' => $name,
                    'contents' => ($fileField['json'] ?? false)
                        ? $this->encodeJson($value, $name === 'context')
                        : (string) $value,
                    'filename' => $fileField['filename'],
                    'headers' => [
                        'Content-Type' => $fileField['content_type'],
                    ],
                ];

                continue;
            }

            $multipart[] = [
                'name' => $name,
                'contents' => $this->encodeField($value),
            ];
        }

        foreach ($assets as $asset) {
            $multipart[] = $asset->toMultipartPart();
        }

        $request = Http::withToken($apiKey)
            ->accept($async ? 'application/json' : 'application/pdf')
            ->timeout((int) $this->config->get('bladepdf.timeout', 60))
            ->connectTimeout((int) $this->config->get('bladepdf.connect_timeout', 10))
            ->retry(
                (int) $this->config->get('bladepdf.retry_times', 1),
                (int) $this->config->get('bladepdf.retry_sleep', 200),
            )
            ->withUserAgent((string) $this->config->get('bladepdf.user_agent', 'bladepdf-laravel/1.0'))
            ->withOptions([
                'verify' => (bool) $this->config->get('bladepdf.verify_ssl', true),
                'multipart' => $multipart,
            ]);

        if ($async) {
            $request = $request->withHeaders([
                'Prefer' => 'respond-async',
            ]);
        }

        return $request->send('POST', $this->endpointUrl($baseUrl, '/render'));
    }

    protected function endpointUrl(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    protected function storedPdfUrlFromResponse(Response $response): ?string
    {
        $link = $response->header('Link');
        if (! is_string($link) || trim($link) === '') {
            return null;
        }

        foreach (explode(',', $link) as $part) {
            if (
                preg_match('/<([^>]+)>/', $part, $urlMatch) === 1
                && preg_match('/;\s*rel="?stored-pdf"?/i', $part) === 1
            ) {
                return $urlMatch[1];
            }
        }

        return null;
    }

    protected function encodeField(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || $value instanceof \JsonSerializable || $value instanceof \stdClass) {
            return $this->encodeJson($value);
        }

        return (string) $value;
    }

    protected function encodeJson(mixed $value, bool $emptyArrayAsObject = false): string
    {
        if ($emptyArrayAsObject && is_array($value) && $value === []) {
            return '{}';
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
