<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Support;

use Illuminate\Support\Str;

class AssetBag
{
    /**
     * @var array<string, ResolvedAsset>
     */
    protected array $assets = [];

    /**
     * @var array<string, string>
     */
    protected array $sourceMap = [];

    public function put(string $contents, string $filename, ?string $mimeType = null, ?string $sourcePath = null, ?string $fieldName = null): string
    {
        $normalizedSource = $sourcePath ? (realpath($sourcePath) ?: $sourcePath) : null;

        if ($normalizedSource && isset($this->sourceMap[$normalizedSource])) {
            return $this->sourceMap[$normalizedSource];
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $fieldName ??= 'asset:///'.Str::ulid()->toBase32().($extension ? '.'.$extension : '');
        $mimeType ??= 'application/octet-stream';

        $this->assets[$fieldName] = new ResolvedAsset(
            fieldName: $fieldName,
            filename: $filename,
            contents: $contents,
            mimeType: $mimeType,
            sourcePath: $normalizedSource,
        );

        if ($normalizedSource) {
            $this->sourceMap[$normalizedSource] = $fieldName;
        }

        return $fieldName;
    }

    /**
     * @return array<int, ResolvedAsset>
     */
    public function all(): array
    {
        return array_values($this->assets);
    }
}
