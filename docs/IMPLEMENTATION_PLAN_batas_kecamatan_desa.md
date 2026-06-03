# Implementation Plan — Integrasi Batas Kecamatan/Desa ke Page Satelit

Status: RENCANA. Sumber: `BATAS_KECAMATAN_DESA.rar` (sudah diekstrak).

## Aset (terverifikasi)

- File: ESRI Shapefile `BATAS_KECAMATAN_DESA_AR.shp` (5,8 MB)
  - Lokasi ekstrak: `tmp/batas/BATAS_KECAMATAN_DESA/`
- **280 poligon** (POLYGONZ — ada koordinat Z, harus di-drop ke 2D)
- Field atribut: `KABUPATEN`, `KECAMATAN`, `DESA`
- **31 kecamatan**, **253 desa**, semua **Kabupaten Bandung**
- CRS: **WGS84 / EPSG:4326** (lat/lon) → **cocok** dengan `detected_buildings.latitude/longitude`, tanpa reproyeksi
- bbox: 107,251–107,938 BT · -7,310–-6,813 LS

## Tujuan

1. **Perbaiki atribusi wilayah** tiap bangunan (kecamatan + desa) lewat point-in-polygon resmi → buang leakage (mis. 1.708 row "Soreang" yang sebenarnya KBB/Cianjur).
2. **Tampilkan batas kecamatan/desa** di page satelit sebagai overlay + dukung filter desa.

## Batasan (WAJIB dipatuhi)

- JANGAN overwrite kolom `kecamatan` yang ada secara destruktif → tulis ke **kolom baru** (`kecamatan_verified`, `desa_verified`).
- JANGAN ubah skema `pbg_task*`, JANGAN drop `estimated_area_m2`, JANGAN re-call Google.
- PostGIS lagi down → spatial join di **MySQL 8** (`ST_Contains`, SRID 0 planar, titik `POINT(lon lat)`).
- JANGAN ubah skema vector tile Martin (`public.buildings`).

---

## STAGE 0 — Konversi SHP → GeoJSON + load ke MySQL

**0a. SHP → GeoJSON (tanpa GDAL, pakai pyshp)**
- Script Python (`tmp/batas/shp2geojson.py`): baca shapefile, drop Z, output `FeatureCollection` (EPSG:4326) dengan properti `kabupaten/kecamatan/desa`.
- Dua output:
  - `desa_full.geojson` — presisi penuh (buat spatial join di DB).
  - `desa_web.geojson` — disederhanakan (douglas-peucker ~0,0001°) buat overlay peta (ringan).

**0b. Tabel batas di MySQL**
```sql
CREATE TABLE admin_boundaries_desa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kabupaten VARCHAR(100),
  kecamatan VARCHAR(100),   -- tanpa prefiks "Kecamatan "
  desa      VARCHAR(100),   -- tanpa prefiks "Desa "
  geom GEOMETRY NOT NULL SRID 0,
  SPATIAL INDEX(geom)
);
```
- Insert tiap poligon via `ST_GeomFromGeoJSON(... , 1, 0)` (SRID 0, axis lon-lat) — konsisten dengan pendekatan point-in-polygon yang sudah dipakai.
- Normalisasi nama: strip "Kecamatan "/"Desa " biar match dengan label existing.

---

## STAGE 1 — Re-atribusi bangunan (point-in-polygon)

**1a. Tambah kolom hasil (non-destruktif)**
```sql
ALTER TABLE detected_buildings
  ADD COLUMN kecamatan_verified VARCHAR(100) NULL,
  ADD COLUMN desa_verified      VARCHAR(100) NULL,
  ADD COLUMN admin_match_status ENUM('matched','outside_regency') NULL;
```

**1b. Spatial join, batch**
- Untuk tiap bangunan: cari poligon desa yang `ST_Contains(geom, POINT(lon lat))`.
- Prefilter pakai spatial index (MBR) → baru `ST_Contains` presisi.
- 2,4 juta baris → batch per kecamatan-bbox atau per 50rb id; commit bertahap.
- Yang **tidak kena poligon mana pun** → `admin_match_status='outside_regency'` (kandidat leakage / di luar Kab. Bandung).

**1c. Verifikasi**
- Bandingkan `kecamatan` (lama) vs `kecamatan_verified`. Mismatch = leakage/edge yang dulu dicurigai.
- Hitung ulang ringkasan Soreang **dari `kecamatan_verified`**, bukan kolom lama.

---

## STAGE 1.5 — Review tiap koordinat bangunan vs label kecamatan

Tujuan: audit menyeluruh — apakah label kecamatan tiap bangunan benar.

- Untuk SETIAP bangunan, bandingkan `kecamatan` (label lama) vs `kecamatan_verified` (hasil point-in-polygon).
- Klasifikasi tiap baris:
  - `ok` — label lama = verified.
  - `relabel` — beda kecamatan (label lama salah, dibenerin).
  - `was_null` — label lama kosong, sekarang terisi.
  - `outside_regency` — gak masuk poligon Kab. Bandung mana pun (leakage / luar wilayah).
  - `unmatched` — lat/lng null / di luar bbox.
- Output laporan: jumlah per klasifikasi, breakdown per kecamatan (berapa yang masuk/keluar), dan daftar contoh relabel terbesar.
- Simpan ke `admin_match_status` + tabel ringkasan `kecamatan_review_report`.
- Tes: total = jumlah bangunan; tidak ada baris matched yang `kecamatan_verified` NULL.

---

## STAGE 2 — Rekonsiliasi & dampak ke angka

- Update `kecamatan_stats` snapshot dari kolom verified.
- Buang `outside_regency` dari hitungan potensi retribusi per kecamatan → angka Soreang (90/75/55 M) jadi lebih bersih & tahan audit.
- Log jumlah yang pindah kecamatan + jumlah outside_regency (transparansi).

---

## STAGE 3 — Overlay batas di page satelit (frontend)

File: `resources/views/dashboards/satellite-monitoring.blade.php` (Leaflet).

- Tambah layer **batas desa & kecamatan** dari `desa_web.geojson` (disederhanakan):
  - Garis kecamatan tebal, desa tipis.
  - Toggle "Batas Wilayah" (on/off), default off di zoom jauh.
  - Hover → tooltip "Kec. X — Desa Y".
- Serve GeoJSON: simpan di `public/geo/desa_web.geojson` atau endpoint Laravel ber-cache (jangan inline ke blade — 5,8 MB).
- Saat zoom dekat, batas desa membantu petugas paham konteks tiap bangunan.

---

## STAGE 4 — Sambungkan ke filter

- Dropdown **Kecamatan** (`filter-district`) & **Desa/Kelurahan** (saat ini "Pilih kecamatan") diisi dari data resmi: 31 kecamatan → 253 desa.
- Pilih kecamatan → desa ter-filter dinamis (cascading) dari `admin_boundaries_desa`.
- Filter peta + statistik ikut pakai `kecamatan_verified`/`desa_verified`.

---

## Urutan eksekusi

1. Stage 0 (konversi + load) — cepat, fondasi.
2. Stage 1 (spatial join) — paling berat (2,4 jt baris); jalankan batch, monitor.
3. Stage 2 (rekonsiliasi angka) — setelah join beres.
4. Stage 3 (overlay peta) — frontend, independen, bisa paralel.
5. Stage 4 (filter cascading) — terakhir.

## Risiko

- **Performa**: 2,4 jt × point-in-polygon di MySQL berat → wajib spatial index + batch; estimasi puluhan menit–jam.
- **Nama tak konsisten**: prefiks "Kecamatan/Desa" + ejaan (mis. "Soreang") harus dinormalisasi biar nyambung ke data lama.
- **POLYGONZ**: koordinat Z harus dibuang saat konversi, kalau tidak `ST_Contains` bisa error.
- **Geometri berat**: jangan inline GeoJSON penuh ke peta — sederhanakan + cache.
