<?php

namespace App\Enums;

enum PbgTaskApplicationTypes: string
{
    case PERSETUJUAN_BG = '1';
    case PERUBAHAN_BG = '2';
    case SLF_BB = '4';
    case SLF = '5';
    public static function labels(): array
    {
        return [
            null => "Pilih Application Type",
            self::PERSETUJUAN_BG->value => 'Persetujuan Bangunan Gedung',
            self::PERUBAHAN_BG->value => 'Perubahan Bangunan Gedung',
            self::SLF_BB->value => 'Sertifikat Laik Fungsi - Bangunan Baru',
            self::SLF->value => 'Sertifikat Laik Fungsi',
        ];
    }
    public static function getLabel(?string $status): ?string
    {
        return self::labels()[$status] ?? null;
    }
}