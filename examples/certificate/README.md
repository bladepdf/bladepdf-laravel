# Certificate example

An A4 landscape completion certificate with a formal border, signatures, issue date, and verifiable credential id.

```php
use BladePDF\Laravel\Facades\BladePDF;

$data = require resource_path('views/pdf/certificate/data.php');

return BladePDF::fromView('pdf.certificate.certificate', $data)
    ->format('A4')
    ->landscape()
    ->showBackground()
    ->render()
    ->download('certificate.pdf');
```

![Rendered certificate](preview.png)
