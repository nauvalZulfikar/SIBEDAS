# Implementation Plan — Poligon Bangunan yang Bisa Diedit User

Status: RENCANA (belum dibangun). Bahasa non-teknis.

## Konteks sistem saat ini (hasil cek kode)

- Peta pakai **Leaflet** (tampilan halaman `dashboards/satellite-monitoring`).
- Bangunan digambar dari **vector tiles** (server tile "Martin" baca dari database PostGIS).
- **Dua database**:
  - MySQL `detected_buildings` = sumber kebenaran. Kebanyakan baris cuma TITIK + LUAS (`estimated_area_m2`). Hanya OSM (~84rb) yang punya gambar asli (`geometry_geojson`). Poligon Microsoft TIDAK tersimpan di sini — cuma luasnya yang diambil.
  - PostGIS `buildings` = salinan turunan (punya poligon), di-sync tiap malam 03:00 lewat `buildings:sync-postgis`.
- Tile di-cache di Redis (24 jam). Edit gak langsung keliatan kalau cache belum di-refresh.
- Login pakai Sanctum. Ada level akses: level_1 (lihat titik saja), level_2 (lihat poligon + data sensitif), level_3 (super admin).
- Sudah ada log akses (`pbb_access_log`) dan Observer buat invalidasi cache tile.
- Retribusi dihitung dari LUAS (hunian Rp 9.181,50/m²). Ngedit batas = ngubah luas = ngubah tagihan.

## Aturan yang TIDAK boleh dilanggar

- JANGAN ubah skema `pbg_task` / `pbg_task_retributions` / `pbg_task_details`.
- JANGAN drop kolom `estimated_area_m2`.
- JANGAN ubah skema vector tile (`public.buildings`, `public.building_tile`) tanpa rencana migrasi sadar.
- Edit poligon = nyangkut uang → wajib ada riwayat + persetujuan.

---

## FASE 0 — Sediakan "gambar awal" buat tiap bangunan (FONDASI, paling berat)

Masalah: kebanyakan bangunan belum punya poligon yang bisa diedit, cuma titik + luas.

Pilihan sumber gambar awal (urut dari paling bagus):
1. **Impor ulang poligon Microsoft asli.** Microsoft punya file poligon atap; dulu cuma luasnya yang diambil. Kalau file mentahnya masih ada / bisa di-download ulang, ini paling akurat. Simpan gambarnya ke kolom/tabel baru (jangan timpa yang lama).
2. **Pakai yang sudah ada** buat OSM (84rb) — udah ada gambar, langsung bisa diedit.
3. **Gambar kotak sementara dari titik + luas** buat sisanya, sebagai placeholder yang HARUS dirapikan user. (Paling jelek, cuma buat bangunan yang gak ada sumber poligonnya.)

Output Fase 0: tiap bangunan punya satu "gambar awal". Disimpan di **tempat baru** (kolom geometry baru di MySQL atau tabel pendamping `building_geometry_edits`), bukan menimpa data sumber.

## FASE 1 — Tempat simpan hasil editan

- Tambah tabel baru, mis. `building_polygon_edits`: id bangunan, gambar baru (poligon), luas hasil hitung ulang, status (draft / nunggu approval / disetujui / ditolak), siapa ngedit, kapan.
- Data asli TIDAK ditimpa — editan disimpan terpisah. Yang dipakai di peta = versi terbaru yang sudah disetujui; kalau belum ada, pakai gambar awal.

## FASE 2 — Tombol & alat edit di peta (frontend)

- Tambah plugin edit poligon ke Leaflet (mis. **Leaflet-Geoman** atau **Leaflet.Editable**).
- Alur petugas: klik bangunan → tombol "Edit batas" → titik-titik sudut jadi bisa ditarik → tambah/hapus sudut → bisa pisah/gabung bangunan → klik "Simpan".
- Hanya muncul di zoom ≥14 (zoom di mana poligon ditampilkan) dan hanya buat user level_2+.
- Saat ngedit, tampilkan luas baru + estimasi retribusi baru secara langsung biar petugas tahu dampaknya.

## FASE 3 — Simpan editan (endpoint tulis baru)

- Endpoint baru, mis. `PUT /api/detected-buildings/{id}/geometry`.
- Server: validasi bentuk poligon (gak boleh garis nyilang, gak boleh kosong), hitung ulang luas dari gambar, hitung ulang potensi retribusi, simpan sebagai status "nunggu approval".
- Catat ke log siapa-ngubah-apa.

## FASE 4 — Riwayat & persetujuan (karena nyangkut uang)

- Daftar "editan nunggu disetujui" buat atasan (level_3).
- Atasan bisa lihat sebelum/sesudah (gambar lama vs baru, luas lama vs baru, tagihan lama vs baru), lalu Setujui / Tolak + alasan.
- Setiap perubahan kecatet permanen (riwayat). Manfaatin pola yang udah ada (`pbb_access_log` / Observer).

## FASE 5 — Refresh tampilan (sync + cache)

- Setelah disetujui: dorong gambar baru ke PostGIS (jangan nunggu sync malam — bikin sync cepat khusus 1 bangunan), lalu hapus cache tile di area itu biar peta langsung update.
- Sudah ada mekanismenya (`InvalidateBuildingTiles`) — tinggal dipicu saat approval.

## FASE 6 — Deteksi bangunan baru / yang kelewat (opsional, belakangan)

- Jalanin deteksi otomatis lagi dari citra terbaru buat nangkep bangunan baru.
- Sediakan tombol "Gambar bangunan baru" di peta buat petugas nambah manual yang gak kedetek.

---

## Urutan kerja yang disarankan

1. Fase 0 (sediakan gambar awal) — paling lama, harus beres dulu. Mulai dari OSM (udah ada) + impor ulang Microsoft.
2. Fase 1 + 3 (tempat simpan + endpoint) — backend.
3. Fase 2 (alat edit di peta) — frontend.
4. Fase 4 (approval) — sebelum dipakai produksi, WAJIB karena uang.
5. Fase 5 (refresh cepat).
6. Fase 6 (bangunan baru) — paling akhir.

## Risiko jujur

- Fase 0 bisa mentok kalau file poligon Microsoft mentah gak ketemu lagi → sebagian bangunan terpaksa pakai kotak placeholder yang harus dirapikan manual (lama).
- Edit poligon ngubah tagihan → kalau approval-nya gak ketat, bisa jadi celah bocor PAD atau protes warga. Fase 4 gak boleh diskip.
- Dua database + cache = ada jeda. Tanpa Fase 5, editan baru keliatan besok pagi.
