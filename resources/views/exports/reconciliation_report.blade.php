<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekonsiliasi PBB</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        h2 { margin: 14px 0 6px; font-size: 13px; border-bottom: 2px solid #146c43; padding-bottom: 2px; }
        .muted { color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { padding: 4px 6px; border: 1px solid #ccc; text-align: left; }
        th { background: #f0f4f0; font-weight: bold; }
        td.num { text-align: right; }
        .kpi { display: table; width: 100%; margin: 8px 0 4px; }
        .kpi-cell { display: table-cell; width: 25%; padding: 6px 8px; border: 1px solid #ddd; }
        .kpi-cell .label { color: #666; font-size: 9px; text-transform: uppercase; }
        .kpi-cell .value { font-size: 16px; font-weight: bold; color: #146c43; margin-top: 2px; }
        .kpi-cell.danger .value { color: #d63384; }
        .kpi-cell.muted-cell .value { color: #6c757d; }
        .gap-pos { color: #d63384; font-weight: bold; }
        .gap-neg { color: #6c757d; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
        .signoff { margin-top: 30px; }
        .signoff td { border: none; padding: 4px 0; }
    </style>
</head>
<body>

<table style="border:none; margin-bottom: 6px"><tr style="border:none">
    <td style="border:none; width:60%">
        <h1>Laporan Rekonsiliasi PBB ↔ Satelit</h1>
        <div class="muted">Kabupaten Bandung — DPUTR &amp; Bapenda<br>
            Generated: {{ now()->format('d M Y H:i') }} WIB
            @if($generatedBy)| oleh: {{ $generatedBy }}@endif</div>
    </td>
    <td style="border:none; width:40%; text-align:right; vertical-align: top">
        <strong>SIBEDAS</strong><br>
        <span class="muted">Sistem Informasi Bangunan Pemkab Bandung Selatan</span>
    </td>
</tr></table>

<h2>Ringkasan Kabupaten</h2>
<div class="kpi">
    <div class="kpi-cell">
        <div class="label">PBB Terbangun</div>
        <div class="value">{{ number_format($kab['pbb_terbangun'], 0, ',', '.') }}</div>
        <div class="muted">dari {{ number_format($kab['pbb_total'], 0, ',', '.') }} NOP</div>
    </div>
    <div class="kpi-cell">
        <div class="label">Bangunan Satelit</div>
        <div class="value">{{ number_format($kab['sat_count'], 0, ',', '.') }}</div>
    </div>
    <div class="kpi-cell {{ ($kab['gap_sat_minus_terbangun'] ?? 0) > 0 ? 'danger' : 'muted-cell' }}">
        <div class="label">Gap (Sat − Terbangun)</div>
        <div class="value">
            {{ ($kab['gap_sat_minus_terbangun'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($kab['gap_sat_minus_terbangun'], 0, ',', '.') }}
        </div>
        <div class="muted">{{ $kab['gap_pct'] !== null ? number_format($kab['gap_pct'], 2) . '%' : 'n/a' }}</div>
    </div>
    <div class="kpi-cell">
        <div class="label">PBG Terbit</div>
        <div class="value">{{ number_format($kab['pbg_terbit_count'], 0, ',', '.') }}</div>
    </div>
</div>

<h2>Top 10 Kecamatan dengan Gap Tertinggi (Absolute)</h2>
<table>
    <thead><tr>
        <th>Kecamatan</th>
        <th class="num">PBB Terbangun</th>
        <th class="num">Sat Count</th>
        <th class="num">Gap</th>
        <th class="num">Gap %</th>
    </tr></thead>
    <tbody>
        @foreach ($topKec as $r)
        <tr>
            <td>{{ $r['kecamatan'] }}</td>
            <td class="num">{{ number_format($r['pbb_terbangun'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($r['sat_count'], 0, ',', '.') }}</td>
            <td class="num {{ $r['gap'] >= 0 ? 'gap-pos' : 'gap-neg' }}">
                {{ $r['gap'] >= 0 ? '+' : '' }}{{ number_format($r['gap'], 0, ',', '.') }}
            </td>
            <td class="num">{{ $r['gap_pct'] !== null ? number_format($r['gap_pct'], 2) . '%' : '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2>Catatan</h2>
<ul>
    <li><strong>Gap positif</strong> (sat &gt; terbangun): potensi bangunan tidak terdaftar PBB.</li>
    <li><strong>Gap negatif</strong>: potensi NOP terbangun yang sudah dirobohkan / data outdated.</li>
    <li>Data per-kelurahan tersedia di Excel export (sheet "Per Kelurahan") — sebagian masih
        flagged "Pending Polygon" (Phase 7+ data gap).</li>
    <li>Data sensitif (nama WP, alamat) hanya tersedia di Excel export untuk caller dengan
        clearance level_3.</li>
</ul>

<div class="footer">
    Laporan ini di-generate otomatis dari snapshot table <code>reconciliation_summary</code>
    yang di-recompute terakhir pada {{ \Carbon\Carbon::parse($lastComputed)->format('d M Y H:i') }} WIB.
    Untuk verifikasi manual, akses dashboard di <code>/dashboards/reconciliation</code>.
</div>

<table class="signoff">
    <tr>
        <td style="width:50%">
            Mengetahui,<br><br><br>
            ____________________<br>
            Kepala Bapenda
        </td>
        <td style="width:50%">
            Bandung, {{ now()->format('d M Y') }}<br><br><br>
            ____________________<br>
            Operator Sistem
        </td>
    </tr>
</table>

</body>
</html>
