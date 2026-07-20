<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['number'] }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18212f; background: #f6f7fb; font: 14px/1.5 Inter, ui-sans-serif, system-ui, sans-serif; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 18mm; background: #fff; position: relative; }
        .topbar { height: 7px; background: linear-gradient(90deg, #6d4aff, #3b82f6); position: absolute; inset: 0 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 8mm; }
        .brand { font-weight: 800; font-size: 22px; letter-spacing: -.5px; }
        .brand-mark { display: inline-block; width: 13px; height: 20px; margin-right: 8px; border-radius: 3px; background: #6d4aff; vertical-align: -4px; }
        h1 { margin: 0; font-size: 38px; letter-spacing: -1.5px; color: #111827; }
        .invoice-meta { margin-top: 6px; text-align: right; color: #687386; }
        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 18mm; margin: 19mm 0 12mm; }
        .eyebrow { margin-bottom: 7px; color: #7b8493; font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        .address strong { display: block; margin-bottom: 3px; color: #111827; font-size: 16px; }
        .address p { margin: 0; color: #687386; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 10px 9px; border-bottom: 2px solid #111827; color: #687386; font-size: 10px; letter-spacing: 1px; text-align: left; text-transform: uppercase; }
        th:nth-child(n+2), td:nth-child(n+2) { text-align: right; }
        td { padding: 14px 9px; border-bottom: 1px solid #e6e9ef; vertical-align: top; }
        td strong { display: block; color: #111827; }
        td small { color: #7b8493; }
        .summary { width: 76mm; margin: 10mm 0 0 auto; }
        .summary-row { display: flex; justify-content: space-between; padding: 6px 0; color: #687386; }
        .summary-total { margin-top: 6px; padding: 12px 14px; border-radius: 8px; background: #111827; color: #fff; font-size: 17px; font-weight: 800; }
        .note { margin-top: 18mm; padding: 14px 16px; border: 1px solid #e3e6ef; border-left: 4px solid #6d4aff; border-radius: 7px; background: #fafaff; color: #596579; }
        .footer { position: absolute; right: 18mm; bottom: 12mm; left: 18mm; display: flex; justify-content: space-between; color: #9299a6; font-size: 10px; }
    </style>
</head>
<body>
<main class="page">
    <div class="topbar"></div>
    <header class="header">
        <div class="brand"><span class="brand-mark"></span>{{ $invoice['company']['name'] }}</div>
        <div>
            <h1>Invoice</h1>
            <div class="invoice-meta">
                <strong>{{ $invoice['number'] }}</strong><br>
                Issued {{ $invoice['issued_at'] }} · Due {{ $invoice['due_at'] }}
            </div>
        </div>
    </header>

    <section class="addresses">
        <div class="address">
            <div class="eyebrow">From</div>
            <strong>{{ $invoice['company']['name'] }}</strong>
            <p>{{ $invoice['company']['email'] }}<br>@foreach ($invoice['company']['address'] as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach</p>
        </div>
        <div class="address">
            <div class="eyebrow">Bill to</div>
            <strong>{{ $invoice['customer']['name'] }}</strong>
            <p>{{ $invoice['customer']['contact'] }} · {{ $invoice['customer']['email'] }}<br>@foreach ($invoice['customer']['address'] as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach</p>
        </div>
    </section>

    <table aria-label="Invoice line items">
        <thead><tr><th>Service</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
        @foreach ($invoice['items'] as $item)
            <tr>
                <td><strong>{{ $item['description'] }}</strong><small>{{ $item['detail'] }}</small></td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ $item['rate'] }}</td>
                <td><strong>{{ $item['total'] }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <section class="summary">
        <div class="summary-row"><span>Subtotal</span><strong>{{ $invoice['subtotal'] }}</strong></div>
        <div class="summary-row"><span>Tax</span><strong>{{ $invoice['tax'] }}</strong></div>
        <div class="summary-row summary-total"><span>Total</span><span>{{ $invoice['total'] }} {{ $invoice['currency'] }}</span></div>
    </section>

    <aside class="note"><strong>Payment terms</strong><br>{{ $invoice['payment_terms'] }}</aside>
    <footer class="footer"><span>Thank you for your business.</span><span>{{ $invoice['number'] }} · Page 1 of 1</span></footer>
</main>
</body>
</html>
