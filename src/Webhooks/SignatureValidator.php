<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Webhooks;

use BladePDF\Webhooks\SignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class SignatureValidator
{
    public const DEFAULT_TOLERANCE = SignatureVerifier::DEFAULT_TOLERANCE;

    public static function isValid(
        Request $request,
        ?string $secret = null,
        ?int $tolerance = null,
    ): bool {
        $secret ??= config('bladepdf.webhook_secret');
        $tolerance ??= (int) config('bladepdf.webhook_tolerance', self::DEFAULT_TOLERANCE);

        if (! is_string($secret)) {
            return false;
        }

        $timestamp = $request->header('BladePDF-Timestamp');
        $signature = $request->header('BladePDF-Signature');

        return SignatureVerifier::isValid(
            rawBody: $request->getContent(),
            timestamp: is_string($timestamp) ? $timestamp : null,
            signature: is_string($signature) ? $signature : null,
            secret: $secret,
            tolerance: $tolerance,
            currentTimestamp: Date::now()->getTimestamp(),
        );
    }
}
