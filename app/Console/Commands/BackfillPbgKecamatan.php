<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills pbg_task.kecamatan using a hybrid strategy:
 *   1. point-in-polygon — when the application has valid coordinates
 *      (pbg_task_details.latitude/longitude), match them against the official
 *      desa/kecamatan polygons (admin_boundaries_desa.geom). This is the
 *      authoritative source, consistent with detected_buildings.kecamatan_verified.
 *   2. address fallback — for rows without (valid) coordinates, take the first
 *      comma-token of the SIMBG address, but ONLY when it matches one of the 31
 *      official Kabupaten Bandung kecamatan (junk first-tokens become NULL).
 *
 * Idempotent: resets the two columns then recomputes. Safe to re-run after each
 * SIMBG sync. Never writes any other column.
 */
class BackfillPbgKecamatan extends Command
{
    protected $signature = 'pbg:backfill-kecamatan
        {--dry : Show the would-be counts without writing}
        {--address-only : Skip point-in-polygon, populate from address parsing only}';

    protected $description = 'Populate pbg_task.kecamatan via point-in-polygon (coords) with address fallback';

    /** The 31 official kecamatan of Kabupaten Bandung. */
    private const KECAMATAN = [
        'Arjasari', 'Baleendah', 'Banjaran', 'Bojongsoang', 'Cangkuang',
        'Cicalengka', 'Cikancung', 'Cilengkrang', 'Cileunyi', 'Cimaung',
        'Cimenyan', 'Ciparay', 'Ciwidey', 'Dayeuhkolot', 'Ibun',
        'Katapang', 'Kertasari', 'Kutawaringin', 'Majalaya', 'Margaasih',
        'Margahayu', 'Nagreg', 'Pacet', 'Pameungpeuk', 'Pangalengan',
        'Paseh', 'Pasirjambu', 'Rancabali', 'Rancaekek', 'Solokanjeruk',
        'Soreang',
    ];

    public function handle(): int
    {
        $inList = "'" . implode("','", self::KECAMATAN) . "'";

        if ($this->option('dry')) {
            return $this->report($inList);
        }

        // Guard: the point-in-polygon source only exists where the satellite
        // boundary data is loaded (e.g. local). On environments without it
        // (prod), a normal run would reset every row to NULL and then crash on
        // the missing table — wiping any verified values that were replicated
        // in. Refuse unless the caller explicitly opts into address-only.
        $addressOnly = (bool) $this->option('address-only');
        if (! Schema::hasTable('admin_boundaries_desa') && ! $addressOnly) {
            $this->error('admin_boundaries_desa is missing — point-in-polygon unavailable here.');
            $this->warn('Refusing to run: a reset would discard existing/replicated kecamatan values.');
            $this->line('Re-run with --address-only to force address-based population, or run where the polygon table exists.');

            return self::FAILURE;
        }

        // 0) reset so the run reflects the current address/coords state.
        DB::statement('UPDATE pbg_task SET kecamatan = NULL, kecamatan_source = NULL');

        if ($addressOnly) {
            $this->warn('--address-only: skipping point-in-polygon.');
            $pip = 0;
        } else {
        // 1) point-in-polygon (authoritative). POINT(lng lat), polygons are SRID 0.
        $pip = DB::update("
            UPDATE pbg_task pt
            JOIN pbg_task_details d
                ON d.pbg_task_uid = pt.uuid
               AND d.latitude  IS NOT NULL AND d.latitude  <> 0
               AND d.longitude IS NOT NULL AND d.longitude <> 0
            JOIN admin_boundaries_desa b
                ON ST_Contains(
                       b.geom,
                       ST_GeomFromText(CONCAT('POINT(', d.longitude, ' ', d.latitude, ')'), 0)
                   )
            SET pt.kecamatan = b.kecamatan,
                pt.kecamatan_source = 'pip'
        ");
        $this->info("point-in-polygon: {$pip} rows");
        }

        // 2) address fallback for everything PIP could not place.
        $addr = DB::update("
            UPDATE pbg_task pt
            SET pt.kecamatan = TRIM(SUBSTRING_INDEX(pt.address, ',', 1)),
                pt.kecamatan_source = 'address'
            WHERE pt.kecamatan IS NULL
              AND pt.address IS NOT NULL
              AND TRIM(SUBSTRING_INDEX(pt.address, ',', 1)) IN ({$inList})
        ");
        $this->info("address fallback : {$addr} rows");

        $null = DB::table('pbg_task')->whereNull('kecamatan')->count();
        $this->info("unresolved (NULL): {$null} rows");
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function report(string $inList): int
    {
        $pip = DB::selectOne("
            SELECT COUNT(*) c FROM pbg_task pt
            JOIN pbg_task_details d ON d.pbg_task_uid = pt.uuid
               AND d.latitude IS NOT NULL AND d.latitude<>0 AND d.longitude IS NOT NULL AND d.longitude<>0
            JOIN admin_boundaries_desa b
                ON ST_Contains(b.geom, ST_GeomFromText(CONCAT('POINT(', d.longitude, ' ', d.latitude, ')'), 0))
        ")->c;
        $addrCov = DB::selectOne("
            SELECT COUNT(*) c FROM pbg_task
            WHERE TRIM(SUBSTRING_INDEX(address, ',', 1)) IN ({$inList})
        ")->c;
        $this->info("[dry] PIP-resolvable        : {$pip}");
        $this->info("[dry] address-canonical cov : {$addrCov}");
        $this->info('[dry] no write performed.');

        return self::SUCCESS;
    }
}
