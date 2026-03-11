# Daftar Improvement & Perbaikan Sibedas

| # | Kategori | Item | Penjelasan |
|---|----------|------|------------|
| 1 | **Bug Kritis** | Fix LEFT JOIN inflation di BigdataResume | Query stats pakai LEFT JOIN ke tabel retribusi — kalau 1 permit punya 2 retribusi, nilainya ikut dihitung 2x. Bikin angka total biaya di dashboard jadi lebih besar dari kenyataan |
| 2 | **Bug Kritis** | Fix hitungan dokumen bisnis | Permit non-bisnis dengan `unit > 1` salah masuk ke hitungan dokumen bisnis. Angka kategori permit di dashboard jadi tidak akurat |
| 3 | **Bug Kritis** | Google credentials hilang saat container restart | File `teak-banner-450003-s8-ea05661d9db0.json` disimpan di dalam container, bukan di volume. Setiap rebuild/restart container, file hilang dan scraping Google Sheets gagal |
| 4 | **Bug Medium** | Filter tahun payment realization terlalu ketat | `whereYear('payment_date_raw', date('Y'))` hanya ambil pembayaran tahun ini. Pembayaran yang diinput telat atau backdate bisa terlewat |
| 5 | **Bug Medium** | Data pembayaran hilang kalau Google Sheets crash | Step sync Google Sheets hapus semua data lama dulu baru insert baru. Kalau crash di tengah, data pembayaran kosong semua sampai scraping berikutnya |
| 6 | **Reliability** | Incremental scraping | Sekarang scraper fetch ulang semua ~7800+ permit dari awal setiap jalan. Seharusnya hanya scrape permit yang statusnya berubah sejak terakhir sync, hemat waktu dan resource |
| 7 | **Reliability** | Retry otomatis kalau Google Sheets API gagal | Kalau API Google Sheets timeout atau rate limit, scraping langsung gagal. Perlu mekanisme retry dengan exponential backoff |
| 8 | **Reliability** | Volume mount untuk Google credentials | Mount file credentials ke Docker volume biar tidak hilang saat container restart atau rebuild image |
| 9 | **Reliability** | Container .env tidak ikut rebuild | Perubahan `.env` di host tidak otomatis masuk ke container karena `.env` di-bake ke image. Perlu mekanisme yang lebih proper (Docker secrets atau env file mount) |
| 10 | **Feature** | Progress scraping real-time (persentase) | UI sekarang hanya tampilkan status (processing/paused/dll). Perlu progress bar persentase supaya operator tahu sudah sampai mana dan estimasi selesai |
| 11 | **Feature** | Notifikasi WhatsApp/email selesai scraping | Operator tidak tahu kapan scraping selesai atau gagal kecuali buka halaman. Kirim notif otomatis saat job selesai, gagal, atau butuh perhatian |
| 12 | **Feature** | Audit log scraping | Tidak ada catatan siapa yang trigger scraping, kapan, dan hasilnya apa. Penting untuk akuntabilitas dan debugging |
| 13 | **Feature** | Export laporan Excel/PDF | Pejabat biasanya butuh laporan dalam format Excel atau PDF untuk rapat/arsip. Sekarang data hanya bisa dilihat di dashboard |
| 14 | **Feature** | Role-based access control | Tidak jelas siapa saja yang bisa akses dashboard atau trigger scraping. Perlu pembagian role (admin, viewer, operator) |
| 15 | **UX** | Info step scraping yang sedang berjalan | Kalau scraping gagal, operator tidak tahu di step mana (apakah di fetch SIMBG, sync Google Sheets, atau generate resume). Perlu log step yang visible di UI |
| 16 | **UX** | Tampilkan waktu terakhir scraping berhasil | Dashboard tidak menampilkan kapan data terakhir diupdate. Operator tidak tahu apakah data yang dilihat masih fresh atau sudah lama |
| 17 | **Performance** | Cache dashboard per request | `BigdataResume` sudah pre-computed, tapi endpoint masih query tambahan (spatial planning, payments) setiap request. Bisa di-cache dengan TTL pendek |
| 18 | **DevOps** | Automated deploy pipeline | Sekarang deploy manual (rsync + scp + rebuild). Perlu simple CI/CD supaya deploy lebih cepat dan tidak error-prone |
