<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Summary Excel export — 31 rows, baca dari snapshot kecamatan_stats.
 * Kolom dipisah: PBG-side (count langsung dari pbg_task) vs Satelit-side (count via match).
 * Detail (>100k rows) di-handle terpisah lewat CSV streaming di Console command.
 */
class SatelitPbgSummaryExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    use Exportable;

    private const KECS = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    public function __construct(private int $minArea = 0) {}

    public function title(): string { return 'Summary per Kecamatan'; }

    public function headings(): array
    {
        return [
            'Kecamatan',
            'Total Satelit',
            'Tanpa Izin Sah',
            'PBG Terbit (jml di pbg_task)',
            'PBG Proses (jml di pbg_task)',
            'PBG Ditolak (jml di pbg_task)',
            'Match Sat→PBG Terbit',
            'Match Sat→PBG Proses',
            'Unmatched (Sat tanpa PBG)',
            'Orphan FK',
            'Rejected (Sat→PBG ditolak)',
            'Rasio Match Berizin (%)',
        ];
    }

    public function array(): array
    {
        // Pakai snapshot kecamatan_stats — itulah yang dibaca UI dashboard.
        $rows = DB::table('kecamatan_stats')
            ->where('min_area_bucket', $this->snapBucket($this->minArea))
            ->whereIn('kecamatan', self::KECS)
            ->get()
            ->keyBy('kecamatan');

        $out = [];
        $sum = ['total'=>0,'without'=>0,'pTerbit'=>0,'pProses'=>0,'pDitolak'=>0,
                'mTerbit'=>0,'mProses'=>0,'unmatched'=>0,'orphan'=>0,'rejected'=>0];

        foreach (self::KECS as $kec) {
            $r = $rows[$kec] ?? null;
            $total    = $r ? (int) $r->total_detected : 0;
            $without  = $r ? (int) $r->without_permit_total : 0;
            $pTerbit  = $r ? (int) $r->pbg_terbit : 0;
            $pProses  = $r ? (int) $r->pbg_proses : 0;
            $pDitolak = $r ? (int) $r->pbg_ditolak : 0;
            $mTerbit  = $r ? (int) $r->permit_valid_count : 0;
            $mProses  = $r ? (int) $r->permit_in_process_count : 0;
            $orphan   = $r ? (int) $r->orphan_count : 0;
            $rejected = $r ? (int) $r->permit_rejected_count : 0;
            $unmatched= $r ? (int) $r->unmatched_count : 0;
            $rasio    = $total > 0 ? round($mTerbit / $total * 100, 2) : 0;

            $out[] = [$kec, $total, $without, $pTerbit, $pProses, $pDitolak,
                      $mTerbit, $mProses, $unmatched, $orphan, $rejected, $rasio];

            $sum['total']    += $total;    $sum['without']  += $without;
            $sum['pTerbit']  += $pTerbit;  $sum['pProses']  += $pProses;
            $sum['pDitolak'] += $pDitolak; $sum['mTerbit']  += $mTerbit;
            $sum['mProses']  += $mProses;  $sum['unmatched']+= $unmatched;
            $sum['orphan']   += $orphan;   $sum['rejected'] += $rejected;
        }
        $rasioTotal = $sum['total'] > 0 ? round($sum['mTerbit'] / $sum['total'] * 100, 2) : 0;
        $out[] = ['TOTAL', $sum['total'], $sum['without'], $sum['pTerbit'], $sum['pProses'],
                  $sum['pDitolak'], $sum['mTerbit'], $sum['mProses'], $sum['unmatched'],
                  $sum['orphan'], $sum['rejected'], $rasioTotal];

        return $out;
    }

    private function snapBucket(int $minArea): int
    {
        foreach ([1000, 500, 200, 100, 50] as $b) if ($minArea >= $b) return $b;
        return 0;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0EA5E9']],
                ]);
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$lastRow}:L{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
            },
        ];
    }
}
