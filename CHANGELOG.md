# Changelog

All notable changes to `bladepdf/laravel` are documented here.

## 2.0.0

- Require PHP 8.2+, Laravel 11-13, and `bladepdf/php:^1.0`.
- Keep the public facade workflow while moving transport, requests, assets, results, exceptions, and webhook verification into the framework-agnostic core.
- Render Blade body, header, and footer views only when `render()` or `async()` builds the request.
- Restrict automatic local assets to `public_path()`, `storage_path('app')`, and explicitly configured `asset_roots`.
- Add request IDs to render results and throw on failed PDF writes through the core result API.
- Move the Laravel webhook wrapper to `BladePDF\Laravel\Webhooks\SignatureValidator`.
- Remove the 1.x internal client, support classes, exception copies, and Laravel-specific render submission.

See the [2.0 upgrade guide](https://docs.bladepdf.com/upgrading/laravel-v2) for the namespace and configuration migration.
