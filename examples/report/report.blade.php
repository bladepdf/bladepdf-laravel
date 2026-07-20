<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }} - {{ $report['period'] }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf0f5; color: #1d2431; font: 12px/1.5 Inter, ui-sans-serif, system-ui, sans-serif; }
        .page { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm; background: #fff; }
        header { display: flex; align-items: flex-start; justify-content: space-between; padding-bottom: 9mm; border-bottom: 1px solid #e5e8ee; }
        .brand { color: #6d4aff; font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { margin: 2mm 0 0; color: #111827; font-size: 28px; letter-spacing: -.7px; }
        .meta { color: #7b8493; text-align: right; }
        .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; margin: 8mm 0; }
        .metric { padding: 5mm; border: 1px solid #e4e7ee; border-radius: 6px; background: #fafbfc; }
        .metric-label { color: #7d8695; font-size: 9px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; }
        .metric-value { margin: 3px 0; color: #111827; font-size: 20px; font-weight: 800; }
        .metric-change { color: #218a56; font-size: 10px; font-weight: 700; }
        .grid { display: grid; grid-template-columns: 1.55fr 1fr; gap: 6mm; }
        .panel { padding: 6mm; border: 1px solid #e4e7ee; border-radius: 7px; }
        .panel h2 { margin: 0; color: #202734; font-size: 14px; }
        .panel-subtitle { margin: 1mm 0 5mm; color: #8a92a0; font-size: 10px; }
        .chart { display: flex; height: 56mm; align-items: flex-end; gap: 5mm; padding: 4mm 2mm 0; border-bottom: 1px solid #dfe3ea; background: repeating-linear-gradient(to top, transparent 0, transparent 13mm, #eef0f4 13.2mm); }
        .bar-column { display: flex; flex: 1; height: 100%; flex-direction: column; justify-content: flex-end; text-align: center; }
        .bar { min-height: 8mm; border-radius: 4px 4px 0 0; background: linear-gradient(#7653ff, #3b82f6); }
        .month { margin-top: 2mm; color: #89919e; font-size: 9px; }
        .insights { margin: 0; padding: 0; list-style: none; }
        .insights li { position: relative; padding: 4mm 0 4mm 6mm; border-bottom: 1px solid #edf0f4; color: #586476; }
        .insights li:last-child { border: 0; }
        .insights li::before { content: ''; position: absolute; top: 6mm; left: 0; width: 2.5mm; height: 2.5mm; border-radius: 50%; background: #6d4aff; }
        .table-panel { margin-top: 6mm; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 3mm; border-bottom: 2px solid #242b38; color: #7a8392; font-size: 9px; letter-spacing: .8px; text-align: left; text-transform: uppercase; }
        td { padding: 4mm 3mm; border-bottom: 1px solid #e8ebf0; }
        th:not(:first-child), td:not(:first-child) { text-align: right; }
        .segment { font-weight: 800; }
        .retention { color: #218a56; font-weight: 800; }
        .callout { display: flex; align-items: center; justify-content: space-between; margin-top: 8mm; padding: 5mm 6mm; border-radius: 7px; background: #11131b; color: #fff; }
        .callout strong { font-size: 14px; }
        .callout span { color: #aab1bd; }
        footer { position: absolute; right: 15mm; bottom: 10mm; left: 15mm; display: flex; justify-content: space-between; color: #949ba7; font-size: 9px; }
    </style>
</head>
<body>
<main class="page">
    <header>
        <div><div class="brand">{{ $report['workspace'] }}</div><h1>{{ $report['title'] }}</h1></div>
        <div class="meta"><strong>{{ $report['period'] }}</strong><br>Generated {{ $report['generated_at'] }}</div>
    </header>

    <section class="metrics">
        @foreach ($report['metrics'] as $metric)
            <div class="metric">
                <div class="metric-label">{{ $metric['label'] }}</div>
                <div class="metric-value">{{ $metric['value'] }}</div>
                <div class="metric-change">{{ $metric['change'] }} vs prior quarter</div>
            </div>
        @endforeach
    </section>

    <section class="grid">
        <article class="panel">
            <h2>Monthly recurring revenue</h2>
            <p class="panel-subtitle">Indexed growth across the first half of 2026</p>
            <div class="chart">
                @foreach ($report['monthly'] as $point)
                    <div class="bar-column"><div class="bar" style="height: {{ $point['value'] }}%"></div><div class="month">{{ $point['month'] }}</div></div>
                @endforeach
            </div>
        </article>
        <article class="panel">
            <h2>What changed</h2>
            <p class="panel-subtitle">Highlights selected for the leadership team</p>
            <ul class="insights">
                @foreach ($report['insights'] as $insight)<li>{{ $insight }}</li>@endforeach
            </ul>
        </article>
    </section>

    <section class="panel table-panel">
        <h2>Customer segments</h2>
        <p class="panel-subtitle">Revenue and retention by commercial segment</p>
        <table aria-label="Customer segment performance">
            <thead><tr><th>Segment</th><th>Customers</th><th>Net revenue</th><th>Retention</th></tr></thead>
            <tbody>
            @foreach ($report['segments'] as $segment)
                <tr><td class="segment">{{ $segment['name'] }}</td><td>{{ $segment['customers'] }}</td><td>{{ $segment['revenue'] }}</td><td class="retention">{{ $segment['retention'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <aside class="callout"><strong>Outlook: on track for the 2026 operating plan</strong><span>Next review · October 2026</span></aside>
    <footer><span>Confidential · {{ $report['workspace'] }}</span><span>{{ $report['title'] }} · Page 1 of 1</span></footer>
</main>
</body>
</html>
