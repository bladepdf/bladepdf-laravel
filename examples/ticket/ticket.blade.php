<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $ticket['event'] }} ticket</title>
    <style>
        @page { size: 200mm 90mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eceef5; color: #131722; font: 14px/1.4 Inter, ui-sans-serif, system-ui, sans-serif; }
        .ticket { display: grid; grid-template-columns: 1fr 58mm; width: 200mm; height: 90mm; overflow: hidden; background: #fff; }
        .main { position: relative; padding: 13mm 14mm; }
        .main::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 6px; background: linear-gradient(#714bff, #316cff); }
        .brand { color: #6d4aff; font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { max-width: 95mm; margin: 5mm 0 1mm; font-size: 28px; line-height: 1.05; letter-spacing: -1px; }
        .tagline { margin: 0; color: #667085; }
        .details { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 9mm; margin-top: 11mm; }
        .label { margin-bottom: 3px; color: #9299a7; font-size: 9px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .value { color: #1c2431; font-weight: 700; }
        .side { position: relative; padding: 11mm 9mm; border-left: 1px dashed #b9bfcb; background: #11131b; color: #fff; text-align: center; }
        .side::before, .side::after { content: ''; position: absolute; left: -5mm; width: 10mm; height: 10mm; border-radius: 50%; background: #eceef5; }
        .side::before { top: -5mm; }
        .side::after { bottom: -5mm; }
        .pass { color: #a999ff; font-size: 10px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        .qr { display: grid; grid-template-columns: repeat(9, 1fr); gap: 1px; width: 29mm; height: 29mm; margin: 6mm auto 4mm; padding: 2mm; background: #fff; }
        .qr i { background: #11131b; }
        .qr i:nth-child(3n), .qr i:nth-child(7n+1), .qr i:nth-child(11n+4) { background: #fff; }
        .code { font: 700 11px ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .6px; }
        .attendee { margin-top: 6mm; font-weight: 800; }
        .seat { color: #a6adba; font-size: 10px; }
        .footer { position: absolute; bottom: 6mm; left: 14mm; color: #8a92a2; font-size: 10px; }
    </style>
</head>
<body>
<article class="ticket">
    <section class="main">
        <div class="brand">BladePDF Events</div>
        <h1>{{ $ticket['event'] }}</h1>
        <p class="tagline">{{ $ticket['tagline'] }}</p>
        <div class="details">
            <div><div class="label">Date & time</div><div class="value">{{ $ticket['date'] }}<br>{{ $ticket['time'] }}</div></div>
            <div><div class="label">Venue</div><div class="value">{{ $ticket['venue'] }}<br>{{ $ticket['address'] }}</div></div>
            <div><div class="label">Entry</div><div class="value">{{ $ticket['doors'] }}</div></div>
        </div>
        <div class="footer">Bring this ticket on your phone or as a printed copy.</div>
    </section>
    <aside class="side">
        <div class="pass">{{ $ticket['type'] }}</div>
        <div class="qr" aria-label="Ticket QR code">
            @for ($i = 0; $i < 81; $i++)<i></i>@endfor
        </div>
        <div class="code">{{ $ticket['code'] }}</div>
        <div class="attendee">{{ $ticket['attendee'] }}</div>
        <div class="seat">{{ $ticket['seat'] }}</div>
    </aside>
</article>
</body>
</html>
