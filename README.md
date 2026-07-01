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

Set your API Key:

```env
BLADEPDF_API_KEY=your_api_key
```

## Basic usage

```php
use BladePDF\Laravel\Facades\BladePDF;

return BladePDF::fromView('pdf.invoice', [
    'invoice' => $invoice,
])
    ->withHeader('pdf.header')
    ->withFooter('pdf.footer')
    ->templateName('Invoice')
    ->reference($invoice->uuid)
    ->format('A4')
    ->margins(80, 20, 60, 20)
    ->showBackground()
    ->waitUntil('networkidle0')
    ->emulateMedia('screen')
    ->response('invoice.pdf');
```

## Cloud templates

Render a template that is stored in BladePDF cloud storage by sending its template id and the JSON context used by the remote Blade renderer:

```php
return BladePDF::fromTemplate('invoice.standard', [
    'invoice' => $invoice,
    'customer' => $invoice->customer,
])
    ->reference($invoice->uuid)
    ->storePdf()
    ->format('A4')
    ->showBackground()
    ->response('invoice.pdf');
```

Cloud template renders send `source={"type":"template","templateId":"..."}` and a multipart `context.json` file. Header and footer HTML overrides are not supported for cloud templates because they are part of the stored template configuration.

## Supported multipart form fields

All fields are optional and are sent as `multipart/form-data` to `POST https://api.bladepdf.com/render`:

- `source` (JSON encoded)
- `metadata` (JSON encoded)
- `store_pdf`
- `wait_until`
- `wait_function`
- `emulate_media`
- `html`
- `header_html`
- `footer_html`
- `context`
- `pdf_options` (JSON encoded)

HTML renders send `source={"type":"html"}` and upload `html.html`. Cloud template renders send `source={"type":"template","templateId":"..."}` and upload `context.json`.

## Metadata and stored PDFs

Use a reference to correlate a render with your own model:

```php
BladePDF::fromTemplate('invoice.standard', $context)
    ->reference($invoice->uuid)
    ->storePdf()
    ->pdf();
```

For raw HTML or local Blade view renders, you may also provide a dashboard display name:

```php
BladePDF::fromView('pdf.invoice', ['invoice' => $invoice])
    ->templateName('Tenant invoice')
    ->reference($invoice->uuid)
    ->pdf();
```

`templateName()` is only accepted for HTML renders. Cloud template renders already identify the stored template through `templateId`.

## PDF options

Common PDF options are available as fluent methods:

```php
BladePDF::fromHtml('<h1>Hello</h1>')
    ->format('A4')
    ->landscape()
    ->margins(10, 10, 15, 10)
    ->showBackground()
    ->scale(0.9)
    ->pages('1-3')
    ->taggedPdf()
    ->preferCssPageSize()
    ->waitForFonts()
    ->outline()
    ->pdf();
```

You can still pass raw options when you need lower-level control:

```php
BladePDF::fromHtml('<h1>Hello</h1>')
    ->withOptions(['printBackground' => true])
    ->pdf();
```

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

## Manual assets

If you want to upload an asset yourself and reference it manually inside custom HTML, you can attach it explicitly:

```php
$html = '<html><body><img src="asset:///brand-logo.png"></body></html>';

return BladePDF::fromHtml($html)
    ->withAsset(public_path('images/logo.png'), 'brand-logo.png')
    ->response();
```

For cloud templates, use `overrideAsset()` to replace a stored `asset:///...` reference for a single render request:

```php
return BladePDF::fromTemplate('invoice.standard', $context)
    ->overrideAsset('brand-logo.png', public_path('images/tenant-logo.png'))
    ->response();
```

Request asset override targets must be simple file names containing only letters, numbers, dots, underscores, and hyphens.

## Raw HTML usage

```php
return BladePDF::fromHtml('<h1>Hello world</h1>')
    ->format('A4')
    ->response();
```

## Saving to disk

```php
BladePDF::fromView('pdf.report')->save(storage_path('app/report.pdf'));
```

## Base64 output

```php
$base64 = BladePDF::fromTemplate('invoice.standard', $context)->base64Pdf();
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
