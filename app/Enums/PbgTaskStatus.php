<?php

namespace App\Enums;

enum PbgTaskStatus: int
{
    case VERIFIKASI_KELENGKAPAN = 1;
    case PERBAIKAN_DOKUMEN = 2;
    case PERMOHONAN_DIBATALKAN = 3;
    case MENUNGGU_PENUGASAN_TPT_TPA = 4;
    case MENUNGGU_JADWAL_KONSULTASI = 5;
    case PELAKSANAAN_KONSULTASI = 6;
    case PERBAIKAN_DOKUMEN_KONSULTASI = 8;
    case PERMOHONAN_DITOLAK = 9;
    case PERHITUNGAN_RETRIBUSI = 10;
    case MENUNGGU_PEMBAYARAN_RETRIBUSI = 14;
    case VERIFIKASI_PEMBAYARAN_RETRIBUSI = 15;
    case RETRIBUSI_TIDAK_SESUAI = 16;
    case VERIFIKASI_SK_PBG = 18;
    case PENERBITAN_SK_PBG = 19;
    case SK_PBG_TERBIT = 20;
    case PENERBITAN_SPPST = 24;
    case PROSES_PENERBITAN_SKRD = 25;
    case MENUNGGU_PENUGASAN_TPT = 26;
    case VERIFIKASI_DATA_TPT = 27;
    case SERTIFIKAT_SLF_TERBIT = 28;

    public static function getStatuses(): array
    {
        return [
            null => "Pilih Status",
            self::VERIFIKASI_KELENGKAPAN->value => "Verifikasi Kelengkapan Dokumen",
            self::PERBAIKAN_DOKUMEN->value => "Perbaikan Dokumen",
            self::PERMOHONAN_DIBATALKAN->value => "Permohonan Dibatalkan",
            self::MENUNGGU_PENUGASAN_TPT_TPA->value => "Menunggu Penugasan TPT/TPA",
            self::MENUNGGU_JADWAL_KONSULTASI->value => "Menunggu Jadwal Konsultasi",
            self::PELAKSANAAN_KONSULTASI->value => "Pelaksanaan Konsultasi",
            self::PERBAIKAN_DOKUMEN_KONSULTASI->value => "Perbaikan Dokumen Konsultasi",
            self::PERMOHONAN_DITOLAK->value => "Permohonan Ditolak",
            self::PERHITUNGAN_RETRIBUSI->value => "Perhitungan Retribusi",
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value => "Menunggu Pembayaran Retribusi",
            self::VERIFIKASI_PEMBAYARAN_RETRIBUSI->value => "Verifikasi Pembayaran Retribusi",
            self::RETRIBUSI_TIDAK_SESUAI->value => "Retribusi Tidak Sesuai",
            self::VERIFIKASI_SK_PBG->value => "Verifikasi SK PBG",
            self::PENERBITAN_SK_PBG->value => "Penerbitan SK PBG",
            self::SK_PBG_TERBIT->value => "SK PBG Terbit",
            self::PENERBITAN_SPPST->value => "Penerbitan SPPST",
            self::PROSES_PENERBITAN_SKRD->value => "Proses Penerbitan SKRD",
            self::MENUNGGU_PENUGASAN_TPT->value => "Menunggu Penugasan TPT",
            self::VERIFIKASI_DATA_TPT->value => "Verifikasi Data TPT",
            self::SERTIFIKAT_SLF_TERBIT->value => "Sertifikat SLF Terbit",
        ];
    }

    public static function getLabel(?int $status): ?string
    {
        return self::getStatuses()[$status] ?? null;
    }

    public static function getWaitingClickDpmptsp(): array
    {
        return [
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value, 
            self::PROSES_PENERBITAN_SKRD->value, 
            self::VERIFIKASI_PEMBAYARAN_RETRIBUSI->value
        ];
    }

    public static function getIssuanceRealizationPbg(): array
    {
        return [
            self::VERIFIKASI_SK_PBG->value,
            self::PENERBITAN_SK_PBG->value,
            self::SK_PBG_TERBIT->value,
        ];
    }

    public static function getIssuanceRealizationPbgPrev(): array
    {
        return [
            self::MENUNGGU_PENUGASAN_TPT_TPA->value,
            self::MENUNGGU_JADWAL_KONSULTASI->value,
            self::PELAKSANAAN_KONSULTASI->value,
            self::PERBAIKAN_DOKUMEN_KONSULTASI->value,
            self::PERHITUNGAN_RETRIBUSI->value,
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value,
        ];
    }

    public static function getProcessInTechnicalOffice(): array
    {
        return [
            self::PENERBITAN_SPPST->value, 
            self::PERHITUNGAN_RETRIBUSI->value, 
            self::RETRIBUSI_TIDAK_SESUAI->value, 
            self::MENUNGGU_JADWAL_KONSULTASI->value, 
            self::MENUNGGU_PENUGASAN_TPT_TPA->value, 
            self::PELAKSANAAN_KONSULTASI->value
        ];
    }

    public static function getVerified(): array
    {
        return [
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value, 
            self::PROSES_PENERBITAN_SKRD->value, 
            self::VERIFIKASI_PEMBAYARAN_RETRIBUSI->value,
            self::PENERBITAN_SK_PBG->value, 
            self::SK_PBG_TERBIT->value, 
            self::VERIFIKASI_SK_PBG->value,
            self::PENERBITAN_SPPST->value, 
            self::PERHITUNGAN_RETRIBUSI->value, 
            self::RETRIBUSI_TIDAK_SESUAI->value, 
            self::MENUNGGU_JADWAL_KONSULTASI->value, 
            self::MENUNGGU_PENUGASAN_TPT_TPA->value, 
            self::PELAKSANAAN_KONSULTASI->value
        ];
    }

    public static function getNonVerified(): array
    {
        return [
            self::VERIFIKASI_KELENGKAPAN->value,
            self::PERBAIKAN_DOKUMEN->value,
        ];
    }

    public static function getPotention(): array
    {
        return [
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value,
            self::PROSES_PENERBITAN_SKRD->value,
            self::VERIFIKASI_PEMBAYARAN_RETRIBUSI->value,
            self::PENERBITAN_SK_PBG->value,
            self::SK_PBG_TERBIT->value,
            self::VERIFIKASI_SK_PBG->value,
            self::PENERBITAN_SPPST->value,
            self::PERHITUNGAN_RETRIBUSI->value,
            self::RETRIBUSI_TIDAK_SESUAI->value,
            self::MENUNGGU_JADWAL_KONSULTASI->value,
            self::MENUNGGU_PENUGASAN_TPT_TPA->value,
            self::PELAKSANAAN_KONSULTASI->value,
            self::VERIFIKASI_KELENGKAPAN->value,
            self::PERBAIKAN_DOKUMEN->value,
            self::PERBAIKAN_DOKUMEN_KONSULTASI->value,
        ];
    }

    /**
     * Sisa potensi tahun sebelumnya - status terbatas
     */
    public static function getPotentionPreviousYear(): array
    {
        return [
            self::MENUNGGU_PENUGASAN_TPT_TPA->value,
            self::MENUNGGU_JADWAL_KONSULTASI->value,
            self::PELAKSANAAN_KONSULTASI->value,
            self::PERHITUNGAN_RETRIBUSI->value,
            self::PENERBITAN_SPPST->value,
            self::PROSES_PENERBITAN_SKRD->value,
            self::MENUNGGU_PEMBAYARAN_RETRIBUSI->value,
        ];
    }

    public static function getRejected(): array
    {
        return [
            self::PERMOHONAN_DITOLAK->value, 
            self::PERMOHONAN_DIBATALKAN->value
        ];
    }
}