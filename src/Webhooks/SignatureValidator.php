<?php

declare(strict_types=1);

namespace BladePDF\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class SignatureValidator
{
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Verify that a Laravel request contains a valid BladePDF webhook signature.
     *
     * Pass a secret explicitly for per-request webhooks. When omitted, the
     * validator uses the bladepdf.webhook_secret configuration value.
     */
    public static function isValid(
        Request $request,
        ?string $secret = null,
        ?int $tolerance = null,
    ): bool {
        $timestamp = $request->header('BladePDF-Timestamp');
        $signature = $request->header('BladePDF-Signature');
        $secret ??= config('bladepdf.webhook_secret');
        $tolerance ??= (int) config('bladepdf.webhook_tolerance', self::DEFAULT_TOLERANCE);

        if (
            ! is_string($timestamp)
            || ! is_string($signature)
            || ! is_string($secret)
            || trim($secret) === ''
            || $tolerance < 0
        ) {
            return false;
        }

        $timestamp = trim($timestamp);
        $signature = trim($signature);

        if (
            preg_match('/\A[1-9][0-9]*\z/D', $timestamp) !== 1
            || preg_match('/\Av1=[a-f0-9]{64}\z/D', $signature) !== 1
        ) {
            return false;
        }

        $timestampValue = filter_var(
            $timestamp,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($timestampValue === false) {
            return false;
        }

        if (
            $tolerance > 0
            && abs(Date::now()->getTimestamp() - $timestampValue) > $tolerance
        ) {
            return false;
        }

        $expected = 'v1='.hash_hmac(
            'sha256',
            $timestamp.'.'.$request->getContent(),
            $secret,
        );

        return hash_equals($expected, $signature);
    }
}
