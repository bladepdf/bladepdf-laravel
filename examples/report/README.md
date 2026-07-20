# Business report example

An A4 executive report with KPI cards, a CSS-only chart, customer segments, and written insights. It uses no JavaScript or external chart dependency.

```php
use BladePDF\Laravel\Facades\BladePDF;

$data = require resource_path('views/pdf/report/data.php');

return BladePDF::fromView('pdf.report.report', $data)
    ->format('A4')
    ->showBackground()
    ->render()
    ->download('quarterly-report.pdf');
```

![Rendered report](preview.png)
