# Invoice example

A print-safe A4 invoice with company and customer details, itemized services, totals, and payment terms.

```php
use BladePDF\Laravel\Facades\BladePDF;

$data = require resource_path('views/pdf/invoice/data.php');

return BladePDF::fromView('pdf.invoice.invoice', $data)
    ->format('A4')
    ->showBackground()
    ->render()
    ->download('invoice.pdf');
```

![Rendered invoice](preview.png)
