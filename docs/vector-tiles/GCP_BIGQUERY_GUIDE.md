# Guide: Re-import Polygon Asli dari Google Open Buildings

Tujuan: ganti 1.09 juta polygon kotak (Microsoft fallback) jadi polygon **outline asli** dari Google Open Buildings v3. Ini sekali setup, hasilnya 92%+ polygon jadi real footprint.

**Estimasi waktu:** ~40 menit total, sebagian besar nungguin download/import.

---

## Kenapa BigQuery?

Google Open Buildings v3 di-host sebagai public dataset BigQuery. Pengen download polygon-nya (1.1 juta bangunan untuk Kab. Bandung) = query BigQuery 1× → CSV ~500 MB.

**Biaya:** Query ini scan ~2 GB. Free tier BigQuery = 1 TB/bulan. **Gratis** untuk first query. Tapi GCP tetep minta billing account "just in case" — kartu kredit jangan kepotong selama free quota cukup.

---

## Step 1 — Buat Akun & Project GCP (~10 menit)

### 1.1. Daftar Google Cloud (skip kalau sudah)
1. Buka https://console.cloud.google.com
2. Login pake Google account
3. Klik **Get Started for Free** (kalau pertama kali)
4. Setup billing — masukin kartu kredit/debit. Google ngasih **$300 free credit 90 hari**, dan setelah expired pun BigQuery free tier 1 TB/bulan tetep berlaku.

### 1.2. Buat Project Baru
1. Di console, klik dropdown project di atas → **New Project**
2. Project name: `sibedas-polygon` (atau apapun)
3. Project ID note-in (auto-generate, mis. `sibedas-polygon-468291`)
4. **Create**

### 1.3. Enable BigQuery API
1. Search bar atas → ketik **"BigQuery API"**
2. Klik **Enable**
3. Tunggu ~30 detik

---

## Step 2 — Install gcloud CLI (~5 menit)

Pilih sesuai OS:

### Windows (yang lagi lu pake)
```powershell
# Download installer dari https://cloud.google.com/sdk/docs/install
# Double-click .exe, ikutin wizard, default semua
# Habis install, restart terminal / PowerShell
```

Atau via Chocolatey:
```powershell
choco install gcloudsdk
```

### Verify
```bash
gcloud --version
# Should show: Google Cloud SDK 4xx.x.x
```

---

## Step 3 — Authenticate (~5 menit)

### 3.1. Login
```bash
gcloud auth login
```
Browser kebuka → pilih akun Google yang sama yang dipake bikin GCP project di Step 1.

### 3.2. Set default project
```bash
gcloud config set project <PROJECT_ID_LU>
# Contoh:
gcloud config set project sibedas-polygon-468291
```

### 3.3. Application default credentials (buat bq CLI)
```bash
gcloud auth application-default login
```
Browser kebuka lagi, login lagi pake akun yang sama.

### Verify
```bash
bq ls
# Should list datasets in your project (kosong kalau baru). 
# Kalau error "permission denied" — billing belum aktif, fix di Step 1.1.
```

---

## Step 4 — Download Polygon (~15 menit)

Script-nya udah gw siapkan di `scripts/download_open_buildings.sh`:

```bash
cd "D:\Downloads\coding project\_sibedas\Sibedas"
bash scripts/download_open_buildings.sh
```

Yang script lakuin:
1. Query BigQuery `bigquery-public-data.open_buildings_v3.buildings` untuk bbox Kab. Bandung
2. Tarik kolom: latitude, longitude, area_in_meters, confidence, **geometry (WKT polygon)**
3. Stream hasilnya jadi CSV ke `storage/app/open-buildings/bandung_buildings.csv`
4. Ukuran final ~400-600 MB, ~1.1 juta baris

**Progress check:** Pas script jalan, di console BigQuery akan kelihatan job aktif. Bisa di-monitor di https://console.cloud.google.com/bigquery → Job History.

---

## Step 5 — Re-Import ke MySQL (~10 menit)

```bash
php artisan buildings:import-open-buildings --source=google
```

Yang artisan command lakuin:
1. Prompt: "Delete N existing google_open_buildings records?" → **Yes**  
   (Termasuk 5 test row Phase 5 dummy data — bakal ke-replace)
2. Parse CSV row by row
3. Untuk tiap row, parse kolom `geometry` (WKT) → GeoJSON via `WktParser` (udah ada di Phase 5)
4. Bulk insert ke `detected_buildings` table dengan `geometry_geojson` filled

Akhir output:
```
Import complete: 1,097,xxx rows
  with polygon: 1,097,xxx (100.0%)
  centroid only: 0
```

Note: Microsoft data **TIDAK** dihapus — tetap di-keep di MySQL. Tapi Google data akan jadi "primary source" karena banyak + akurat.

---

## Step 6 — Sync ke PostGIS (~6 menit)

```bash
php artisan buildings:sync-postgis --via=stdout 2>/dev/null | docker exec -i sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial -q
```

(Atau kalau di production Docker dengan pdo_pgsql installed: just `php artisan buildings:sync-postgis`.)

Yang sync lakuin:
1. Walk semua `detected_buildings` rows (1.18jt + 1.1jt baru = 2.3jt)
2. Convert `geometry_geojson` → PostGIS `geom` via `ST_GeomFromGeoJSON`
3. UPSERT ke `buildings` table
4. Polygon Google **menggantikan** kotak Microsoft (kalau Google punya building yang lokasinya sama)

---

## Step 7 — Verify (~1 menit)

```bash
docker exec sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial -c "
SELECT source, COUNT(*) AS total,
       COUNT(*) FILTER (WHERE ST_NPoints(geom) > 5) AS real_polygon
FROM buildings GROUP BY source ORDER BY total DESC;"
```

Sebelum:
```
microsoft_footprints  | 1097530 |       0
osm_buildings         |   84691 |   17219
google_open_buildings |       5 |       0
```

Sesudah (target):
```
google_open_buildings | 1097xxx | 1090xxx   ← 99% real
microsoft_footprints  | 1097530 |       0   ← tetap ada tapi ke-override
osm_buildings         |   84691 |   17219
```

Kalo angka `real_polygon` Google jadi >90% dari total Google → **berhasil**.

Lalu di peta, ganti filter "Sumber polygon" → **"Outline asli (OSM + Google)"** — sekarang isinya **>1.1 juta polygon real**, bukan cuma 85K.

---

## Troubleshooting

### `bq query` lambat / ga ngerespon
BigQuery kadang antri job. Cek console → Job History → kalau status "Pending" tunggu ~1 menit. Kalau "Failed" → click job untuk lihat error (biasanya billing belum aktif).

### Free credit habis sebelum query selesai
1 query Open Buildings = ~2 GB. Free tier = 1 TB/bulan. Kalau lu udah pernah running banyak query bulan ini, mungkin habis. Solusi: skip ke bulan depan, atau pakai akun GCP lain.

### CSV download ke-cut tengah jalan
File `bandung_buildings.csv` partial. Jalanin ulang `bash scripts/download_open_buildings.sh` — akan overwrite.

### `php artisan buildings:import-open-buildings` out of memory
PHP CLI default 128 MB. Bump:
```bash
php -d memory_limit=1G artisan buildings:import-open-buildings --source=google
```

### Sync ke PostGIS sangat lambat
2.3 juta baris × ~5 KB SQL each = ~12 GB SQL pipe. Bisa lama. Jalanin di malam hari sebelum tidur, paginya udah selesai.

---

## Setelah selesai

Update memory + commit:
- Foto sebelum (kotak ngarang) + sesudah (outline real) sebagai bukti
- Update `docs/vector-tiles/BASELINE.md` Phase 5 section dengan angka real
- Microsoft data bisa di-delete dari PostGIS kalau mau hemat ~400 MB:
  ```sql
  DELETE FROM buildings WHERE source = 'microsoft_footprints';
  ```
  (atau biarin sebagai backup)

Mau gw bantu eksekusi setiap step di atas pas lu siap?
