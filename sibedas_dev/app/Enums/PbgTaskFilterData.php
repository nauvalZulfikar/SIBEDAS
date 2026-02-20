<?php

namespace App\Enums;

enum PbgTaskFilterData : string
{
    case non_business = 'non-business';
    case business = 'business';
    case verified = 'verified';
    case non_verified = 'non-verified';
    case all = 'all';
    case potention = 'potention';
    case issuance_realization_pbg = 'issuance-realization-pbg';
    case process_in_technical_office = 'process-in-technical-office';
    case waiting_click_dpmptsp = 'waiting-click-dpmptsp';
    case non_business_rab = 'non-business-rab';
    case non_business_krk = 'non-business-krk';
    case business_rab = 'business-rab';
    case business_krk = 'business-krk';
    case business_dlh = 'business-dlh';

    public static function getAllOptions() : array  {
        return [
            self::all->value => 'Semua Berkas',
            self::business->value => 'Usaha',
            self::non_business->value => 'Bukan Usaha',
            self::verified->value => 'Terverifikasi',
            self::non_verified->value => 'Belum Terverifikasi',
            self::potention->value => 'Potensi',
            self::issuance_realization_pbg->value => 'Realisasi PBG',
            self::process_in_technical_office->value => 'Proses Di Dinas Teknis',
            self::waiting_click_dpmptsp->value => 'Menunggu Klik DPMPTSP',
            self::non_business_rab->value => 'Non Usaha - RAB',
            self::non_business_krk->value => 'Non Usaha - KRK',
            self::business_rab->value => 'Usaha - RAB',
            self::business_krk->value => 'Usaha - KRK',
            self::business_dlh->value => 'Usaha - DLH',
        ];
    }
}
