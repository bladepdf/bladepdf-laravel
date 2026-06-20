<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Exceptions\MissingApiKeyException;
use BladePDF\Laravel\Exceptions\RenderFailedException;
use BladePDF\Laravel\Support\ResolvedAsset;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;

class BladePdfClient
{
    /**
     * @var array<string, string>
     */
    protected const HTML_FILE_FIELDS = [
        'html' => 'html.html',
        'header_html' => 'header.html',
        'footer_html' => 'footer.html',
    ];

    public function __construct(protected ConfigRepository $config)
    {
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, ResolvedAsset>  $assets
     */
    public function render(array $fields, array $assets = []): string
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

            if (isset(self::HTML_FILE_FIELDS[$name])) {
                $multipart[] = [
                    'name' => $name,
                    'contents' => (string) $value,
                    'filename' => self::HTML_FILE_FIELDS[$name],
                    'headers' => [
                        'Content-Type' => 'text/html; charset=UTF-8',
                    ],
                ];

                continue;
            }

            $multipart[] = [
                'name' => $name,
                'contents' => is_array($value)
                    ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string) $value,
            ];
        }

        foreach ($assets as $asset) {
            $multipart[] = $asset->toMultipartPart();
        }

        $response = Http::withToken($apiKey)
            ->accept('application/pdf')
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
            ])
            ->send('POST', $this->endpointUrl($baseUrl, '/render'));

        if (! $response->successful()) {
            throw RenderFailedException::fromResponse($response->status(), $response->body());
        }

        return $response->body();
    }

    protected function endpointUrl(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }
}
