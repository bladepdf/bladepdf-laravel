<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Support;

class CssAssetRewriter
{
    public function __construct(protected AssetResolver $resolver)
    {
    }

    public function rewrite(string $css, AssetBag $assetBag, ?string $baseDirectory = null): string
    {
        $css = preg_replace_callback(
            '/url\(\s*([\"\']?)(.*?)\1\s*\)/i',
            function (array $matches) use ($assetBag, $baseDirectory): string {
                $reference = trim($matches[2]);

                $replacement = $this->resolver->registerReference(
                    reference: $reference,
                    assetBag: $assetBag,
                    baseDirectory: $baseDirectory,
                );

                if ($replacement === null) {
                    return $matches[0];
                }

                return 'url('.$replacement.')';
            },
            $css,
        ) ?? $css;

        $css = preg_replace_callback(
            '/@import\s+(?:url\()?\s*([\"\']?)(.*?)\1\s*\)?\s*;/i',
            function (array $matches) use ($assetBag, $baseDirectory): string {
                $reference = trim($matches[2]);

                $replacement = $this->resolver->registerReference(
                    reference: $reference,
                    assetBag: $assetBag,
                    baseDirectory: $baseDirectory,
                );

                if ($replacement === null) {
                    return $matches[0];
                }

                return '@import url('.$replacement.');';
            },
            $css,
        ) ?? $css;

        return $css;
    }
}
