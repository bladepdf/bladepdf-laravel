<?php

declare(strict_types=1);

namespace BladePDF\Laravel;

use BladePDF\Laravel\Exceptions\MissingTokenException;
use BladePDF\Laravel\Exceptions\RenderFailedException;
use BladePDF\Laravel\Support\ResolvedAsset;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;

class BladePdfClient
{
    public function __construct(protected ConfigRepository $config)
    {
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, ResolvedAsset>  $assets
     */
    public function render(array $fields, array $assets = []): string
    {
        $token = trim((string) $this->config->get('bladepdf.token'));

        if ($token === '') {
            throw new MissingTokenException('Missing BladePDF API token. Set BLADEPDF_TOKEN in your environment.');
        }

        $multipart = [];

        foreach ($fields as $name => $value) {
            if ($value === null) {
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

        $response = Http::withToken($token)
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
            ->send('POST', rtrim((string) $this->config->get('bladepdf.base_url', 'https://api.bladepdf.com'), '/').'/render');

        if (! $response->successful()) {
            throw RenderFailedException::fromResponse($response->status(), $response->body());
        }

        return $response->body();
    }
}
