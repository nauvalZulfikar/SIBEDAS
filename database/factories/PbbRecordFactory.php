<?php

namespace Database\Factories;

use App\Models\PbbRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class PbbRecordFactory extends Factory
{
    protected $model = PbbRecord::class;

    public function definition(): array
    {
        $kec = $this->faker->randomElement([
            ['010', 'CIWIDEY'], ['100', 'RANCAEKEK'], ['260', 'CILEUNYI'],
            ['750', 'BALEENDAH'], ['280', 'CIMENYAN'], ['160', 'SOREANG'],
        ]);
        $desa = str_pad((string) $this->faker->numberBetween(1, 12), 3, '0', STR_PAD_LEFT);
        $blok = str_pad((string) $this->faker->numberBetween(1, 30), 3, '0', STR_PAD_LEFT);
        $obj = str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
        $luasBumi = $this->faker->numberBetween(60, 5000);
        $luasBangunan = $this->faker->boolean(60) ? $this->faker->numberBetween(20, $luasBumi) : 0;

        return [
            'nop' => "32.06.{$kec[0]}.{$desa}.{$blok}.{$obj}.0",
            'nama_wp' => $this->faker->name(),
            'alamat' => $this->faker->streetAddress() . ' RT: ' . str_pad((string) $this->faker->numberBetween(1, 30), 3, '0', STR_PAD_LEFT) . ' RW:' . str_pad((string) $this->faker->numberBetween(1, 20), 2, '0', STR_PAD_LEFT),
            'terbangun_flag' => null,
            'nama_bangunan' => $luasBangunan > 0 ? $this->faker->randomElement(['PERUMAHAN', 'TOKO', 'GUDANG', null]) : null,
            'luas_bumi' => $luasBumi,
            'luas_bangunan' => $luasBangunan,
            'kecamatan_djp_code' => $kec[0],
            'desa_djp_code' => $desa,
            'kecamatan_name' => $kec[1],
            'kelurahan_name' => strtoupper($this->faker->word()),
            'source_sheet' => $this->faker->randomElement(['Sheet1', 'Sheet2']),
            'imported_at' => now(),
        ];
    }

    public function terbangun(): self
    {
        return $this->state(fn () => [
            'luas_bangunan' => $this->faker->numberBetween(40, 500),
            'nama_bangunan' => 'PERUMAHAN',
        ]);
    }

    public function lahanKosong(): self
    {
        return $this->state(fn () => [
            'luas_bangunan' => 0,
            'nama_bangunan' => null,
        ]);
    }
}
