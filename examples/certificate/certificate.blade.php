<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate for {{ $certificate['recipient'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ebf2; color: #18202d; font-family: Georgia, 'Times New Roman', serif; }
        .page { position: relative; width: 297mm; height: 210mm; padding: 13mm; background: #fff; }
        .frame { position: relative; display: flex; height: 100%; flex-direction: column; align-items: center; justify-content: center; padding: 17mm 28mm; border: 1px solid #c9c0ee; text-align: center; }
        .frame::before { content: ''; position: absolute; inset: 5mm; border: 3px double #6d4aff; pointer-events: none; }
        .corners span { position: absolute; width: 24mm; height: 24mm; border-color: #6d4aff; }
        .corners span:nth-child(1) { top: 8mm; left: 8mm; border-top: 2px solid #6d4aff; border-left: 2px solid #6d4aff; }
        .corners span:nth-child(2) { top: 8mm; right: 8mm; border-top: 2px solid #6d4aff; border-right: 2px solid #6d4aff; }
        .corners span:nth-child(3) { bottom: 8mm; left: 8mm; border-bottom: 2px solid #6d4aff; border-left: 2px solid #6d4aff; }
        .corners span:nth-child(4) { right: 8mm; bottom: 8mm; border-right: 2px solid #6d4aff; border-bottom: 2px solid #6d4aff; }
        .seal { display: grid; width: 18mm; height: 18mm; margin-bottom: 6mm; place-items: center; border: 1px solid #6d4aff; border-radius: 50%; color: #6d4aff; font: 800 12px/1 Inter, sans-serif; letter-spacing: -1px; }
        .eyebrow { color: #6d4aff; font: 700 11px Inter, sans-serif; letter-spacing: 3px; text-transform: uppercase; }
        h1 { margin: 5mm 0 0; font-size: 38px; font-weight: 400; letter-spacing: 1px; }
        .presented { margin: 5mm 0 2mm; color: #7b8290; font: 11px Inter, sans-serif; letter-spacing: 1.4px; text-transform: uppercase; }
        h2 { margin: 0; padding: 0 10mm 3mm; border-bottom: 1px solid #d8d2f2; color: #111827; font-size: 32px; font-weight: 400; font-style: italic; }
        .achievement { margin: 5mm 0 1mm; font-size: 19px; font-weight: 700; }
        .description { max-width: 150mm; margin: 0 auto; color: #687386; font: 13px/1.6 Inter, sans-serif; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 28mm; width: 138mm; margin-top: 11mm; }
        .signature { padding-top: 5mm; border-top: 1px solid #a9afba; font: 12px Inter, sans-serif; }
        .signature strong { display: block; color: #252c38; }
        .signature span { color: #858c99; font-size: 10px; }
        .credential { position: absolute; right: 14mm; bottom: 11mm; color: #8d94a1; font: 9px ui-monospace, monospace; }
        .issued { position: absolute; bottom: 11mm; left: 14mm; color: #8d94a1; font: 9px Inter, sans-serif; }
    </style>
</head>
<body>
<main class="page">
    <section class="frame">
        <div class="corners"><span></span><span></span><span></span><span></span></div>
        <div class="seal">BP</div>
        <div class="eyebrow">{{ $certificate['issuer'] }}</div>
        <h1>Certificate of Completion</h1>
        <div class="presented">Proudly presented to</div>
        <h2>{{ $certificate['recipient'] }}</h2>
        <div class="achievement">{{ $certificate['achievement'] }}</div>
        <p class="description">{{ $certificate['description'] }}</p>
        <div class="signatures">
            @foreach ($certificate['signatures'] as $signature)
                <div class="signature"><strong>{{ $signature['name'] }}</strong><span>{{ $signature['role'] }}</span></div>
            @endforeach
        </div>
        <div class="issued">Issued {{ $certificate['issued_at'] }}</div>
        <div class="credential">Credential {{ $certificate['credential'] }}</div>
    </section>
</main>
</body>
</html>
