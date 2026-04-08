# BladePDF Laravel

Official Laravel package for the BladePDF PDF generation API.

## Installation

```bash
composer require bladepdf/laravel
```

Publish config:

```bash
php artisan vendor:publish --tag=bladepdf-config
```

Set your API token:

```env
BLADEPDF_TOKEN=your_access_token
```

## Basic usage

```php
use BladePDF\Laravel\Facades\BladePDF;

return BladePDF::render('pdf.invoice', [
    'invoice' => $invoice,
])
    ->withHeader('pdf.header')
    ->withFooter('pdf.footer')
    ->withOptions([
        'format' => 'A4',
        'printBackground' => true,
        'displayHeaderFooter' => true,
        'margin' => [
            'top' => '80px',
            'bottom' => '60px',
        ],
    ])
    ->waitUntil('networkidle0')
    ->emulateMedia('screen')
    ->response('invoice.pdf');
```

## Supported multipart form fields

All fields are optional and are sent as `multipart/form-data` to `POST https://api.bladepdf.com/render`:

- `action`
- `wait_until`
- `wait_function`
- `emulate_media`
- `html`
- `header_template`
- `footer_template`
- `pdf_options` (JSON encoded)

## Asset pipeline

When the package renders your Blade templates, it automatically scans the HTML and CSS for local assets and uploads them as multipart fields using the `asset:///...` scheme.

It rewrites these asset references automatically:

- `src`, `href`, `poster`, `data-src`, `data-href`
- `srcset`
- inline `style="..."`
- `<style>...</style>` blocks
- CSS `url(...)`
- CSS `@import`

Examples of local references that can be rewritten:

- `/images/logo.png`
- `{{ asset('css/app.css') }}` when it resolves to your local app host
- relative CSS references like `../fonts/inter.woff2`
- absolute filesystem paths

External URLs such as CDN files are preserved as-is.

## Raw HTML usage

```php
return BladePDF::html('<h1>Hello world</h1>')
    ->withOptions(['format' => 'A4'])
    ->response();
```

## Manual assets

If you want to upload an asset yourself and reference it manually inside custom HTML, you can attach it explicitly:

```php
$html = '<html><body><img src="asset:///brand-logo.png"></body></html>';

return BladePDF::html($html)
    ->withAsset(public_path('images/logo.png'), 'brand-logo.png')
    ->response();
```

## Saving to disk

```php
BladePDF::render('pdf.report')->save(storage_path('app/report.pdf'));
```

## Suggested facade alias

```php
use BladePDF\Laravel\Facades\BladePDF;
```

## Development

```bash
composer install
composer lint
composer test
```

## License

This package is open-sourced software licensed under the MIT license.
