# SIBEDAS — Future Plans

Catatan ide yang belum dibangun. Bukan komitmen, baru rencana.

---

## 1. Auto-poligon bangunan dari citra + editable user

**Ide:** komputer otomatis menggambar batas (poligon) atap tiap bangunan dari foto satelit/udara, lalu petugas bisa mengoreksi langsung di peta (geser sudut, tambah/hapus titik, gabung/pisah bangunan).

**Status fondasi:** sebagian sudah ada. Data bangunan saat ini (Microsoft Footprints + OSM) berasal dari deteksi otomatis citra; `detected_buildings.estimated_area_m2` sudah luas poligon atap asli (bukan bbox). Lihat memory `sibedas-area-polygon-based`.

**Yang belum ada:**
- Editor poligon interaktif di peta untuk user (koreksi manual + simpan).
- Re-deteksi area yang terlewat / bangunan baru dari citra terbaru.
- Riwayat perubahan + alur approval (siapa mengubah apa) — penting karena menyangkut nilai retribusi.

**Catatan:**
- Hasil deteksi otomatis tidak pernah 100% (atap ketutup pohon, bangunan menempel, sawah salah terbaca). Fitur edit user wajib, bukan opsional.
- Hanya mengubah geometri/batas; rumus retribusi (luas × tarif) tetap.
- JANGAN ubah skema vector tile Martin/PostGIS (`public.buildings`, `public.building_tile`) tanpa rencana migrasi.

---

## 2. Hitung jumlah lantai via Google Street View

**Ide:** ambil foto tiap bangunan dari Street View berdasarkan koordinat, lalu baca jumlah lantai (manual atau AI baca-gambar).

**Keterbatasan jujur:**
- Tidak semua bangunan ada fotonya (Street View hanya jalan yang dilewati mobil; gang sempit / tengah sawah kosong).
- Foto sering lama (2–5 tahun), bangunan baru / tambah lantai tidak terupdate.
- Google hanya kasih foto, bukan angka lantai — perlu dibaca manual atau AI (akurasi tidak 100%).
- Kuota & biaya terpisah dari enrichment fungsi bangunan; mahal untuk ~70.000 bangunan.

**Prasyarat keputusan:** cek dulu apakah rumus retribusi butuh jumlah lantai. Saat ini tarif hunian Rp 9.181,50/m² berbasis luas — jumlah lantai mungkin tidak mengubah tagihan. Kalau tidak ngaruh, fitur ini prioritas rendah.

**Rekomendasi:** kalau jadi, batasi ke sampel / bangunan bernilai retribusi tinggi, bukan semua.
