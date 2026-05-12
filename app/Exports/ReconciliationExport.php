<?php

namespace App\Exports;

use App\Services\PbbReconciliationService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReconciliationExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private PbbReconciliationService $service,
        private bool $includePii = false
    ) {}

    public function sheets(): array
    {
        return [
            new ReconciliationSummarySheet($this->service),
            new ReconciliationKecSheet($this->service),
            new ReconciliationKelurahanSheet(),
            new ReconciliationAuditSheet($this->service, $this->includePii),
        ];
    }
}

class ReconciliationSummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private PbbReconciliationService $service) {}

    public function array(): array
    {
        $d = $this->service->getKabSummary();
        return [
            ['PBB Total NOP', $d['pbb_total']],
            ['PBB Terbangun', $d['pbb_terbangun']],
            ['PBB Lahan Kosong', $d['pbb_lahan_kosong']],
            ['Bangunan Satelit (count)', $d['sat_count']],
            ['Luas Bangunan PBB (m²)', $d['pbb_lb_m2']],
            ['Luas Bangunan Satelit (m²)', $d['sat_area_m2']],
            ['PBG SK Terbit', $d['pbg_terbit_count']],
            ['Gap (Sat − Terbangun)', $d['gap_sat_minus_terbangun']],
            ['Gap %', $d['gap_pct'] !== null ? $d['gap_pct'] . ' %' : 'n/a'],
            ['', ''],
            ['Generated at', now()->toDateTimeString()],
        ];
    }

    public function headings(): array { return ['Metric', 'Value']; }
    public function title(): string { return 'Summary Kab'; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D6E9C6');
            },
        ];
    }
}

class ReconciliationKecSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private PbbReconciliationService $service) {}

    public function array(): array
    {
        $rows = $this->service->getPerKec();
        usort($rows, fn ($a, $b) => abs($b['gap']) <=> abs($a['gap']));
        return array_map(fn ($r) => [
            $r['kecamatan'],
            $r['djp_code'],
            $r['pbb_total'],
            $r['pbb_terbangun'],
            $r['pbb_lahan_kosong'],
            $r['sat_count'],
            $r['gap'],
            $r['gap_pct'] !== null ? $r['gap_pct'] . ' %' : 'n/a',
            $r['pbb_lb_m2'],
            $r['sat_area_m2'],
        ], $rows);
    }

    public function headings(): array
    {
        return ['Kecamatan', 'DJP Code', 'PBB Total', 'PBB Terbangun', 'PBB Lahan Kosong',
                'Sat Count', 'Gap', 'Gap %', 'PBB LB (m²)', 'Sat Area (m²)'];
    }
    public function title(): string { return 'Per Kecamatan'; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
                $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D6E9C6');
                $sheet->freezePane('A2');
            },
        ];
    }
}

class ReconciliationKelurahanSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function array(): array
    {
        $rows = DB::table('reconciliation_summary')
            ->where('scope', 'kelurahan')
            ->orderBy('kecamatan_name')->orderBy('kelurahan_name')
            ->get();

        return $rows->map(fn ($r) => [
            $r->kecamatan_name,
            $r->kelurahan_name,
            $r->pbb_total,
            $r->pbb_terbangun,
            $r->sat_count,
            $r->gap_sat_minus_terbangun,
            $r->gap_pct !== null ? $r->gap_pct . ' %' : 'n/a',
            $r->sat_count > 0 ? 'Covered' : 'Pending Polygon',
        ])->all();
    }

    public function headings(): array
    {
        return ['Kecamatan', 'Kelurahan', 'PBB Total', 'PBB Terbangun', 'Sat Count',
                'Gap', 'Gap %', 'Coverage Status'];
    }
    public function title(): string { return 'Per Kelurahan'; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
                $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D6E9C6');
                $sheet->freezePane('A2');
            },
        ];
    }
}

class ReconciliationAuditSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private PbbReconciliationService $service, private bool $includePii) {}

    public function array(): array
    {
        $noSat = $this->service->getNopWithoutSatellite(500, 0);
        $rows = [];
        foreach ($noSat['data'] as $r) {
            $rows[] = [
                $r['nop'] ?? '',
                $this->includePii ? ($r['nama_wp'] ?? '') : $this->mask($r['nama_wp'] ?? ''),
                $this->includePii ? ($r['alamat'] ?? '') : $this->mask($r['alamat'] ?? ''),
                $r['kecamatan_name'] ?? '',
                $r['kelurahan_name'] ?? '',
                $r['luas_bangunan'] ?? 0,
            ];
        }
        if (empty($rows)) {
            $rows[] = ['(no data)', '', '', '', '', 0];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['NOP', 'Nama WP', 'Alamat', 'Kecamatan', 'Kelurahan', 'Luas Bangunan (m²)'];
    }
    public function title(): string
    {
        return 'Audit (NOP Tanpa Sat)' . ($this->includePii ? '' : ' - PII Masked');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCD5B4');
                $sheet->freezePane('A2');
            },
        ];
    }

    private function mask(string $s): string
    {
        $s = trim($s);
        $len = mb_strlen($s);
        if ($len <= 4) return str_repeat('*', $len);
        return mb_substr($s, 0, 2) . str_repeat('*', max(3, $len - 3)) . mb_substr($s, -1);
    }
}
