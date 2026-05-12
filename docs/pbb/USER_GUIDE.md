# Modul Rekonsiliasi PBB — Panduan Pengguna

Dokumen ini ditujukan untuk **staf Bapenda & DPUTR Kab. Bandung** yang menggunakan halaman `/dashboards/reconciliation` sehari-hari.

> Untuk panduan modul Sibedas lain (PBG, retribusi, satellite monitoring), lihat `../USER_GUIDE.md`.

---

## 1. Apa itu Rekonsiliasi PBB?

Sistem ini membandingkan **3 sumber data** untuk menemukan ketidakcocokan:

| Sumber | Yang dilihat | Volume |
|---|---|---|
| 🟦 **PBB** (Pajak Bumi & Bangunan) | NOP terdaftar dengan "Terbangun = Ya" | ~1.15 juta |
| 🛰️ **Satelit** | Bangunan yang terdeteksi dari citra satelit | ~1.18 juta |
| 📋 **PBG** (Persetujuan Bangunan Gedung) | Izin PBG yang sudah terbit | 76 |

**Tujuan utamanya** menjawab 2 pertanyaan:

1. **Bangunan tanpa NOP** — ada bangunan di lapangan, tapi tidak terdaftar di sistem PBB. Potensi kebocoran pajak.
2. **NOP tanpa bangunan** — terdaftar di PBB tapi tidak ada bangunannya di citra satelit. Mungkin sudah dirobohkan, atau data outdated.

---

## 2. Akses Halaman

Login → klik menu sidebar **Dashboard → Rekonsiliasi PBB**.

### Tingkat Akses (Clearance)

Apa yang Anda lihat tergantung peran (role):

| Role | Anda bisa lihat |
|---|---|
| `user` / `operator` (level 1) | KPI kab + per kecamatan + export PDF |
| `admin` (level 2) | + drill-down kelurahan + audit list (nama wajib pajak DI-MASKING `BU***I`) + export Excel/CSV |
| `superadmin` (level 3) | + tombol Recompute + nama wajib pajak ASLI di audit list |

> **Tidak melihat tombol/tab tertentu?** Bukan bug — itu sudah benar. Hubungi admin sistem jika butuh akses lebih.

---

## 3. KPI Cards (atas halaman)

| Card | Arti |
|---|---|
| **PBB Terbangun** | Jumlah NOP yang ditandai "terbangun" di data PBB Bapenda |
| **Bangunan Satelit** | Jumlah polygon bangunan dari deteksi satelit |
| **Gap (Sat − Terbangun)** | Selisih. **Positif** = potensi bangunan ilegal; **Negatif** = mungkin data PBB outdated |
| **PBG Terbit** | Jumlah aplikasi PBG yang status SK-nya sudah "Terbit" |

**Contoh interpretasi:**
> "Gap +603.535 (51%)" → Ada 603 ribu bangunan yang terdeteksi di satelit melebihi yang terdaftar terbangun di PBB. Itu setara 51% dari total bangunan satelit. Ini **agregat kabupaten** — bukan bukti pidana, tapi sinyal untuk audit.

---

## 4. Bar Chart Per Kecamatan

Bar chart "Gap Per Kecamatan" disorting berdasarkan **gap absolut** (terbesar di kiri).

- 🔴 **Bar merah** = sat > terbangun (potensi kebocoran)
- ⚪ **Bar abu-abu** = sat < terbangun (data outdated)

Hover ke bar untuk lihat angka detail. Klik bar tidak melakukan apa-apa — drill-down dari **tabel di bawahnya**.

---

## 5. Tabel Per Kecamatan

Klik pada baris kecamatan untuk **drill-down ke kelurahan** (modal popup).

> Klik baris **tidak bekerja** untuk role level 1. Itu sudah benar — drill-down butuh akses admin.

### Modal Detail Kelurahan

Setiap baris kelurahan punya **badge coverage**:

- 🟢 **Covered** — Sat count akurat dari point-in-polygon (bisa dipercaya untuk decision-making)
- 🟡 **Pending Polygon** — Sat count = 0 karena belum ada peta batas kelurahan akurat. **JANGAN TARIK KESIMPULAN** dari kelurahan dengan badge ini sampai data spasial Bapenda/BPS lengkap

Saat ini ~62% kelurahan sudah Covered, sisanya Pending. Coverage akan naik di Phase 7+.

---

## 6. Tab Audit List

> **Hanya level 2+ (admin)**. Level 1 tidak melihat tab ini.

Tab ini punya 2 daftar:

### A. PBB Terbangun Tanpa Match Satelit

Daftar NOP yang di PBB ditandai terbangun, tapi TIDAK ada bangunan satelit yang match. 100 baris pertama.

> ⚠️ Saat ini list-nya **placeholder** — semua NOP terbangun yang ditampilkan, belum di-filter ketat berdasarkan match per-NOP. Versi ketat butuh Phase 7+ (spatial linking lengkap).

### B. Bangunan Satelit Tanpa NOP

Daftar bangunan satelit yang TIDAK match ke NOP manapun. **Ini kandidat utama bangunan tanpa pajak**.

### Masking PII (level 2)

Untuk role `admin`, kolom **Nama WP** dan **Alamat** akan otomatis di-masking:

```
ADAH JUBAEDAH    →  AD**********H
KP CIPASIR RT:01 →  KP**************1
```

Nama asli hanya muncul untuk role `superadmin` — sesuai aturan UU Perlindungan Data Pribadi.

---

## 7. Export Laporan

Tombol **Export** di header punya 3 opsi:

| Format | Untuk | Yang termasuk |
|---|---|---|
| 📄 **PDF** — Laporan Eksekutif | Print rapat / lampiran memo dinas | KPI cards + top 10 kec + signoff block |
| 📊 **Excel** — Multi-sheet | Edit lanjutan / share ke konsultan | 4 sheet: Summary / Per Kec / Per Kelurahan / Audit |
| 📑 **CSV** — kab/kec/kelurahan | Data scientist / sistem lain | Plain UTF-8 dengan BOM |

Excel untuk role `admin` (level 2) akan **otomatis masking** kolom Nama WP & Alamat dengan asterisks di sheet "Audit". Sheet title-nya berisi tag "PII Masked" sebagai tanda visual.

---

## 8. Recompute Manual

> **Hanya superadmin**. Tombol tidak muncul untuk role lain.

Sistem otomatis recompute **setiap hari jam 02:00 WIB**. Tombol ini cuma dipakai jika:

- Habis import data PBB baru dan perlu lihat angka segera
- Habis ingest tambahan data satelit
- Debug — angka tidak update setelah migrasi data

Klik → tunggu ~5 detik → angka di KPI card akan refresh otomatis.

---

## 9. FAQ

### Kenapa angka berubah hari ini?

Auto-recompute terjadi tiap pukul 02:00 WIB. Kalau ada import data baru malam sebelumnya, angka pagi-nya beda dengan kemarin sore.

### Saya download Excel tapi nama orangnya semua bintang-bintang. Kenapa?

Anda login sebagai `admin`. Ini normal sesuai aturan PDP. Untuk lihat nama asli, login pakai akun `superadmin` atau minta superadmin export untuk Anda.

### "Pending Polygon" itu artinya kelurahannya tidak ada bangunan?

**Bukan.** Artinya: kami belum punya peta batas kelurahan tersebut, jadi tidak bisa hitung berapa bangunan satelit di dalamnya. Bangunan-nya ada — kami cuma belum bisa menghitung secara akurat. Lihat Phase 7+ roadmap.

### Tombol Export PDF diklik, tapi file kosong/error?

1. Cek koneksi internet (PDF butuh data dari server)
2. Coba refresh halaman dulu (token mungkin expired)
3. Kalau masih error, screenshot error message dan kirim ke admin sistem

### Saya butuh data per RW. Bisa?

Belum. Granularity terendah saat ini = kelurahan. RW akan dipertimbangkan di roadmap berikutnya kalau Bapenda sudah expose data RW.

### Kenapa angka per-kelurahan kadang `gap_pct` -3000% atau lebih ekstrim?

Itu karena polygon kelurahannya kecil (cuma menutupi area inti, bukan boundary admin penuh). Pakai badge "Covered" sebagai indikator keandalan: kalau Coverage Status masih partial, angka kelurahan diabaikan dulu.

---

## Hubungi siapa kalau ada masalah?

| Jenis masalah | Hubungi |
|---|---|
| Login / akses | Admin sistem Sibedas |
| Angka aneh di dashboard | Bapenda IT (cek kemarin recompute jalan apa engga) |
| Bug UI / error message | Tim DPUTR IT |
| Kebijakan masking / siapa boleh akses apa | Bapenda kebijakan + sosialisasi UU PDP |
