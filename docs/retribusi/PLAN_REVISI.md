# Revisi Pipeline Retribusi — Log Eksekusi

Branch: `revisi-retribusi-2026-05-30` · DB: MySQL `sibedas` (2.403.038 detected_buildings) · PostGIS `sibedas_postgis_local` (db `sibedas_spatial`)

## Baseline (sebelum revisi, `kecamatan_stats` bucket 0, refreshed 2026-05-30 06:45)
| Metrik | Nilai |
|---|---|
| without_permit_total | 1.089.736 bangunan |
| without_permit_area_m2 | 209.372.187 m² |
| without_permit_retribution | **Rp 1.922.465.035.459** (≈1,922 T) |
| without_permit_enriched_retribution | Rp 326.956.546 (≈327 jt) |

---

## RECON — temuan yang mengubah rencana

### 🔴 Fase 1 premis asli GAGAL: polygon asli tidak ada
Brief mengasumsikan footprint Microsoft punya polygon di `geometry_geojson`, tinggal recompute via `ST_Area`. **Tidak benar:**

| Cek | Hasil |
|---|---|
| `detected_buildings.geometry_geojson` (Microsoft) | **NULL untuk seluruh 1.097.530 baris** (hanya OSM yang terisi) |
| PostGIS `public.buildings.geom` (Microsoft) | **5-titik = bounding box itu sendiri**, semua 1.097.530 |
| Rasio `ST_Area(UTM48S)/estimated` (Microsoft) | **1,0014** rata-rata → recompute = no-op |
| id 1588 "Koperasi Bangkit Jaya" recompute | 52.024 m² (vs 51.950) — kriteria `<2000` **gagal** |
| Bangunan dgn polygon >5-titik | **0** Microsoft, 231.047 OSM |

Bounding box adalah satu-satunya geometri yang pernah di-ingest untuk Microsoft. `ST_Area` atas kotak = luas kotak. **Recompute-from-polygon mustahil.**

### Keputusan user: empirical fill-factor
Ganti recompute dgn faktor isian bbox→footprint, **diturunkan dari data** (bukan tebakan):
faktor = `ST_Area(polygon)/ST_Area(ST_Envelope(polygon))` atas 231.047 OSM polygon nyata (>5 titik, UTM48S):

| n | mean | median | p25 | p75 |
|---|---|---|---|---|
| 231.047 | 0,6381 | **0,6158** | 0,5324 | 0,7352 |

→ pakai **`FILL_FACTOR = 0.62`** (median, robust). Microsoft box × 0,62 ≈ estimasi footprint.

### 🟡 Drift kolom (brief vs aktual)
- `pbg_task_retributions`: **tak ada** kolom `amount`/`status`. Uang = `nilai_retribusi_bangunan` (+`nilai_prasarana`,`skrd_amount`,`underpayment`); status harus via `pbg_task_uid`→`pbg_task_details.status`.
- `property_enrichment`: ✓ `detected_building_id`, `place_type` (5.389 baris). Fase 2 sesuai rencana.

---

## Fase 1 — actual_area_m2 (fill-factor) ✅
**Yang berubah**
- Migration `2026_05_30_150000_add_actual_area_to_detected_buildings`: kolom `actual_area_m2 DECIMAL(10,2) NULL` + `area_suspect BOOLEAN` (+ index). `estimated_area_m2` **tidak** disentuh (legacy fallback).
- Command `buildings:recompute-area` (`--factor=0.62 --suspect-threshold=10000 --chunk=2000`):
  - Microsoft (1.097.530) → `estimated × 0.62`, satu bulk UPDATE.
  - OSM polygon nyata >5 titik (231.047) → `ST_Area(ST_Transform(geom,32748))` dari PostGIS, bulk CASE per 2000 id.
  - OSM kotak (1.074.461) → dibiarkan NULL (fallback COALESCE ke estimated).
  - **Runtime 22,7 s** (bukan 30-60 mnt — karena Microsoft murni aritmetika, tak perlu PostGIS per-baris).
- `RefreshKecamatanStats`: semua `db.estimated_area_m2` → `COALESCE(db.actual_area_m2, db.estimated_area_m2)` (filter bucket, SUM tanpa-izin, enriched).
- `DetectedBuildingController`: filter `min_area`, order, & `area_m2` yang di-expose pakai COALESCE; tambah `area_m2_bbox` (legacy) di properties.

**Validasi**
| Cek | Hasil |
|---|---|
| 12 sample Microsoft | semua ratio 0,62 ✓ |
| Rata-rata actual/estimated (n=1.328.577 non-null) | **0,685** (dalam band 40-70%) ✓ |
| id 1588 "Koperasi Bangkit Jaya" | 51.949 → **32.209 m²**, `area_suspect=1` (masih >2000 → flag review; faktor seragam tak bisa benerin bbox blob) ⚠️ |
| OSM box NULL (fallback) | 1.074.461 |
| area_suspect total (>10.000 m²) | **648** bangunan masuk antrean review |

**Dampak angka (bucket 0, Kab Bandung):**
| | Baseline | After Fase 1 | Δ |
|---|---|---|---|
| without_permit_area_m2 | 209.372.187 | 129.810.863 | −38,0% |
| without_permit_retribution | Rp 1.922.465 jt | **Rp 1.191.929 jt** | **−38,0%** |
| enriched_retribution | Rp 326,96 jt | Rp 202,71 jt | −38,0% |

⚠️ Catatan: validasi brief "id 1588 < 2000 m²" **tak tercapai** by design — itu mengandaikan polygon asli yang tak pernah ada. Diganti: flag `area_suspect` utk review manual.

## Fase 2 — place_type → fungsi_bg ✅
**Yang berubah**
- Migration `2026_05_30_151000_create_place_type_function_mapping_table` (place_type PK · fungsi_bg · confidence enum · notes). ⚠️ Collation `place_type` dipaksa `utf8mb4_general_ci` agar match `property_enrichment` (DB default = unicode_ci → kalau tidak, JOIN error "illegal mix of collations").
- Seeder `PlaceTypeFunctionMappingSeeder` — **145 mapping** (diperluas dari daftar brief utk menutup place_type frekuensi tinggi nyata: hotel, coffee_shop, manufacturer, grocery_store, dll). Target fungsi_bg valid: Hunian / Usaha / Usaha (UMKM) / Sosial Budaya / **Keagamaan (rate 0 — exempt)** / Campuran Besar/Kecil.
- `RefreshKecamatanStats` — Source B: JOIN `property_enrichment` + `place_type_function_mapping` utk bangunan tanpa-izin **tanpa PBG** (unmatched/orphan). Prioritas PBG-ditolak (Source A) menang (0 overlap di data ini). `manual_review`/`low` → dipaksa Hunian. **`area_suspect` di-exclude** dari upgrade tarif (bbox blob tak boleh dinaikkan ke Usaha).

**Validasi**
| Cek | Hasil |
|---|---|
| Coverage place_type (5.389 baris) | auto 2.673 · empty 2.000 · manual_review 366 · unmapped 350 |
| Enriched building coverage | **338 → 3.146** (+2.808 Google non-suspect) |
| Toko Besi Saribaja | building_materials_store → Fungsi Usaha ✓ |
| Rumah Helmi | housing_complex → Fungsi Hunian ✓ |
| Masjid Al Jihad | mosque → Fungsi Keagamaan (Rp 0, exempt) ✓ |
| Suspect blob inflasi (ditemukan & dibuang) | 231 suspect = 2,7 jt m² (mis. "kost" 86.164 m²) di-exclude |

**Dampak (bucket 0):**
| | After P1 | After P2 | Δ |
|---|---|---|---|
| without_permit_retribution | Rp 1.191.929 jt | **Rp 1.343.571 jt** | +12,7% |
| enriched_retribution | Rp 202,7 jt | **Rp 216.073 jt (≈216 M)** | coverage ↑ |
| enriched_area_m2 | 23.162 | 7.008.565 | — |

Catatan: enriched naik karena bangunan komersil/sosial tanpa-izin kini pakai tarif fungsi asli (bukan Hunian). Total naik krn reklasifikasi Hunian→Usaha pd 7 jt m². Bangunan enriched cenderung besar (bias seleksi Google Places + sisa merge bbox); `area_suspect` sudah dibuang dr enrichment.

## Fase 3 — aktual vs potensi
_(diisi saat eksekusi)_
