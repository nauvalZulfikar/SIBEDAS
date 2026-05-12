# Sibedas — Panduan Pengguna

Dokumen ini menjelaskan setiap modul utama Sibedas dari sudut pandang **staf dinas** dan **pimpinan DPUTR Kab. Bandung**. Untuk panduan teknis (cara setup, deploy, struktur kode), lihat `architecture_overview.md` dan `scripts/README.md`.

---

## Login & akses

- URL produksi: `https://sibedaspbg.aureonforge.com`
- URL lokal (dev): `http://localhost:8002`
- Login menggunakan akun yang dibuatkan admin. Hubungi admin sistem untuk pembuatan akun baru.
- Setiap akun terikat **role** (peran). Role menentukan menu yang muncul dan operasi yang diizinkan (lihat / buat / edit / hapus).

---

## 1. Dashboard

Setelah login, halaman default adalah salah satu dari beberapa dashboard tergantung role:

- **BigData** — ringkasan menyeluruh: PBG aktif, retribusi terealisasi, sebaran kecamatan, grafik tren.
- **Pimpinan** — versi ringkas untuk eksekutif. Menampilkan KPI utama tanpa detail teknis.
- **PBG** — fokus pada pipeline aplikasi PBG (status, antrean, pembayaran).
- **Lack of Potential** — wilayah dengan potensi PBG belum tergarap.
- **Inside / Outside System** — perbandingan bangunan terdaftar vs hasil deteksi satelit di luar sistem.

Filter umum: rentang waktu, kecamatan, jenis berkas. Klik kartu/grafik untuk drill-down ke daftar detail.

---

## 2. PBG (Persetujuan Bangunan Gedung)

Modul inti sistem.

### Daftar PBG (`/pbg-task`)

- Tabel berisi semua aplikasi PBG: nama pemohon, jenis bangunan, status, retribusi, tanggal masuk.
- **Filter cepat**: berkas lengkap / belum lengkap, status (RAB, KRK, DLH, SK Terbit, dll.), kecamatan, periode.
- Search: NIK pemohon, nomor PBG, alamat objek bangunan.
- Export: tombol Excel/PDF di kanan atas.

### Detail PBG (`/pbg-task/{id}`)

Tab-tab di halaman detail:

1. **Informasi Umum** — data pemohon, alamat, jenis pengajuan.
2. **Bangunan** — dimensi, fungsi, jumlah lantai, indeks tata ruang.
3. **Retribusi** — rincian perhitungan biaya. Lihat tombol "Hitung Ulang Retribusi" jika nilai usulan berubah.
4. **Pembayaran** — riwayat tagihan & realisasi pembayaran (terhubung Virtual Account jika diaktifkan).
5. **Lampiran** — dokumen (KTP, sertifikat tanah, gambar bangunan, RAB, KRK, DLH, SK Terbit). Bisa upload/download.
6. **Riwayat Status** — timeline perubahan status.

### Approval

Menu **Approval** menampilkan PBG yang menunggu persetujuan dari user dengan role pemegang otoritas. Tombol "Setujui" / "Tolak" + kolom catatan.

### Sinkronisasi SIMBG

Data PBG sebagian besar masuk otomatis dari SIMBG nasional via job scraping. Jika data tidak muncul:

- Cek menu **Settings → Sync Status**.
- Hubungi admin teknis untuk restart job scraping bila perlu.

### Sinkronisasi Google Sheets

PBG terpilih disinkron 2 arah ke Google Sheet eksternal. Trigger manual: tombol "Sync ke Sheet" pada detail PBG.

---

## 3. Customers (Pemohon)

- CRUD data pemohon PBG.
- Bulk import via Excel (template: `Settings → Download Template → Customers`).
- Field utama: NIK, nama, alamat, kontak, status verifikasi.

---

## 4. Reklame (Advertisements)

- Pendataan izin reklame: pemilik, lokasi, dimensi, masa berlaku, nilai pajak.
- Upload foto reklame.
- Filter: status aktif / kedaluwarsa, kecamatan, jenis reklame.
- Bulk upload via Excel.

---

## 5. UMKM

- Master data usaha mikro/kecil/menengah di wilayah.
- Field: nama usaha, jenis, alamat, omset, jumlah karyawan.
- Bulk upload + edit massal.

---

## 6. Pariwisata (Tourisms)

- Pendataan destinasi wisata.
- Field: nama, kategori, koordinat, fasilitas, kontak pengelola.
- Terhubung ke Report → Pariwisata.

---

## 7. Tata Ruang (Spatial Plannings)

- Data zonasi & rencana tata ruang per parcel.
- Field: jenis zona, KDB (Koefisien Dasar Bangunan), KLB (Koefisien Lantai Bangunan), tinggi maksimum, indeks retribusi.
- **Penting**: nilai indeks di sini dipakai engine retribusi otomatis. Hati-hati saat mengubah.
- Bulk upload via Excel.

---

## 8. Bangunan Terdeteksi Satelit (Detected Buildings)

Fitur baru (April 2026).

- Hasil deteksi otomatis bangunan dari citra satelit (Open Buildings + Google Earth Engine).
- Setiap entri di-cocokkan dengan PBG existing → bangunan tanpa izin terdeteksi otomatis.
- Tampilan peta interaktif di **Dashboard BigData → Map View**.
- Statistik per kecamatan di **Dashboard → Kecamatan Stats**.
- Untuk staf lapangan: filter "tanpa izin" → cetak daftar untuk verifikasi langsung.

---

## 9. Quick Search

`/quick-search`

- Pencarian cepat lintas data: ketik NIK, alamat, atau koordinat → sistem cari di PBG, customers, parcel, dan deteksi satelit sekaligus.
- Halaman hasil menampilkan ringkasan + tombol jump ke detail.

---

## 10. Public Search

Endpoint publik untuk warga (tidak perlu login).

- Pencarian status PBG berdasarkan nomor permohonan / NIK pemohon.
- Hanya menampilkan info status, tanpa data sensitif.

---

## 11. Reports

Menu **Reports** berisi:

- **PBG PTSP** — laporan PBG yang masuk via PTSP.
- **Payment Recap** — rekap pembayaran retribusi per periode.
- **Growth Report** — pertumbuhan jumlah PBG per kecamatan / periode.
- **Tourism Report** — kunjungan wisata per destinasi.
- **BigData Resume** — eksekutif summary (untuk pimpinan).

Setiap report bisa diexport ke Excel / PDF.

---

## 12. Chatbot

### User Chatbot (`/chatbot`)

- AI assistant berbasis OpenAI gpt-4o-mini.
- Bisa menjawab pertanyaan terkait PBG: "Berapa PBG di Soreang bulan ini?", "Status PBG nomor X?".
- Punya konteks data live dari sistem.

### Chatbot Pimpinan (`/main-chatbot`)

- Versi khusus pimpinan dengan akses ringkasan eksekutif.
- Bisa request laporan ad-hoc: "Bandingkan capaian retribusi Q1 2026 vs Q4 2025".

---

## 13. Rekonsiliasi PBB

Modul terbaru (Phase 1-12 — selesai 2026-05-05). Membandingkan data PBB Bapenda dengan deteksi satelit untuk menemukan bangunan tanpa NOP / NOP tanpa bangunan.

Akses: **Dashboard → Rekonsiliasi PBB** (`/dashboards/reconciliation`).

Panduan lengkap dengan FAQ ada di **`docs/pbb/USER_GUIDE.md`**. Untuk admin / IT: **`docs/pbb/RUNBOOK.md`**. Untuk developer: **`docs/pbb/DEVELOPER.md`**.

---

## 14. Settings & RBAC

Akses hanya untuk role **Administrator**.

### Users

- Tambah / edit / nonaktifkan akun.
- Set role per user.

### Roles

- Buat role baru, definisikan izin per menu (lihat / create / update / delete).

### Menus

- Atur menu apa yang muncul di sidebar.
- Susun urutan & ikon.

### Data Settings

- Konfigurasi rumus retribusi, indeks, parameter sistem.
- **Hati-hati**: perubahan di sini langsung memengaruhi perhitungan PBG live.

### Sinkronisasi

- Status & log job background (scraping SIMBG, sync Google Sheets).
- Tombol restart manual.

---

## FAQ Operasional

**Q: Saya upload Excel UMKM tapi sebagian baris error.**
A: Lihat kolom "Status Import" di hasil upload. Klik "Lihat Error" untuk detail per baris. Perbaiki di Excel, upload ulang hanya baris yang gagal.

**Q: Retribusi PBG keluar Rp 0.**
A: Pastikan data Tata Ruang untuk parcel tersebut sudah ada (zona + KDB + KLB). Jika belum, retribusi tidak bisa dihitung. Tambahkan dulu di menu Tata Ruang.

**Q: Status PBG di Sibedas beda dengan SIMBG.**
A: Sinkronisasi berjalan periodik (default tiap 30 menit). Jika urgent, trigger manual via Settings → Sync.

**Q: Login berhasil tapi sidebar kosong.**
A: Role belum dipetakan ke menu. Hubungi admin untuk assign menu ke role Anda.

**Q: Muncul error "berkas belum lengkap" padahal sudah upload semua.**
A: Cek tab Lampiran — pastikan tipe dokumen sudah dipilih dengan benar (RAB, KRK, DLH wajib di-tag eksplisit). Sistem mengecek berdasarkan tipe, bukan jumlah file.

---

## Kontak

- Admin sistem & dukungan teknis: hubungi tim IT DPUTR
- Bug / saran fitur: kanal internal dinas
