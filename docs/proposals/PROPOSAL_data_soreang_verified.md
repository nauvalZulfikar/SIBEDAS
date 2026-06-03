# PROPOSAL PENAWARAN (REVISI — DATA TERVERIFIKASI)
## Penyediaan Data Terverifikasi Bangunan Belum Ber-PBG
### Kecamatan Soreang — Kabupaten Bandung

---

<div style="text-align:right">Bandung, 2 Juni 2026</div>

**Kepada Yth.**
Kepala Dinas Pekerjaan Umum dan Tata Ruang (PUTR)
Kabupaten Bandung

**Dari:** Nauval Zulfikar

---

## 1. Latar Belakang

Sebagian besar bangunan di Kecamatan Soreang berdiri tanpa Persetujuan Bangunan Gedung (PBG). Kondisi ini menyebabkan potensi penerimaan retribusi daerah (PAD) belum tergali optimal, sekaligus menyulitkan pengendalian tata ruang.

Pendataan manual (survei lapangan) butuh biaya besar — **Rp 1–5 juta per bidang** — dan waktu panjang. Diperlukan pendekatan lebih cepat dan terjangkau melalui **pemetaan digital + verifikasi fungsi bangunan + verifikasi batas wilayah**, sehingga Dinas dapat langsung fokus menagih objek bernilai tinggi.

## 2. Maksud dan Tujuan

- Menyediakan **daftar bangunan belum berizin** di Kecamatan Soreang yang sudah **terverifikasi batas wilayahnya** (titik berada di dalam polygon resmi Soreang), terverifikasi fungsinya, dan terprioritaskan berdasarkan nilai potensi retribusi.
- Membantu Dinas PUTR mengarahkan penagihan ke objek **bernilai tinggi dan aktif beroperasi**.
- Menjadi basis data awal optimalisasi PAD dari sektor retribusi PBG.

## 3. Aset Data yang Ditawarkan (Angka Terverifikasi)

| Komponen | Jumlah |
|---|---|
| Bangunan Soreang terverifikasi (point-in-polygon batas resmi) | **20.649 bangunan** |
| Bangunan **belum berizin** (Tidak Berizin) | **20.460 bangunan** |
| Di antaranya **usaha aktif terverifikasi** (beroperasi, non-fasum) | **± 6.350 bangunan** |
| Potensi retribusi belum berizin — **kotor** | **± Rp 31,2 miliar** |
| Potensi retribusi **bersih** (setelah keluarkan rumah ibadah/sekolah/fasum) | **± Rp 28 miliar** |
| Potensi dari **usaha aktif terverifikasi saja** (angka paling kuat) | **± Rp 19,9 miliar** |

> **Catatan transparansi:** angka ini **lebih kecil** dari penawaran versi awal (yang menyebut ~24.690 bangunan & Rp 75 miliar). Penyebabnya: data awal masih memuat bangunan KBB/Cianjur yang **salah label kecamatan**. Setelah verifikasi batas administrasi (titik-dalam-polygon resmi), angka Soreang yang **benar** adalah seperti tabel di atas. Kami memilih menyajikan angka yang **bisa dipertanggungjawabkan**, bukan angka besar yang tidak akurat.

## 4. Rincian per Kategori (Verified)

| Status | Jumlah | Potensi (kotor) |
|---|---:|---:|
| Tidak Berizin — usaha aktif terverifikasi | 7.138 | Rp 23,0 miliar |
| Tidak Berizin — lainnya (prediksi hunian) | 13.322 | Rp 8,2 miliar |
| Proses | 47 | Rp 87 juta |
| Ditolak/Batal | 16 | Rp 10 juta |
| Sudah ber-PBG (SK terbit) | 126 | Rp 0 |
| **TOTAL** | **20.649** | **± Rp 31,3 miliar** |

Dari 7.138 usaha terverifikasi, **788 bangunan dikecualikan** (rumah ibadah, sekolah, fasilitas sosial/pemerintah senilai ± Rp 3,1 miliar) sehingga lead usaha **billable** ± 6.350 bangunan / Rp 19,9 miliar.

<div style="page-break-before: always"></div>

## 5. Spesifikasi Setiap Data (Record)

Setiap bangunan memuat:

- Koordinat presisi (lintang/bujur)
- Luas bangunan (m²) — dari polygon citra
- Fungsi / jenis bangunan
- Nama tempat & alamat (untuk lead usaha)
- Status operasional (beroperasi / tutup)
- Rating publik (bila tersedia)
- Status izin
- **Kecamatan & desa terverifikasi** (point-in-polygon batas resmi)
- **Estimasi potensi retribusi per bangunan**

**Format penyajian:** data tayang langsung pada **halaman peta satelit website SIBEDAS PBG** (sibedaspbg.cloud) — petugas dapat menelusuri, memfilter, dan menindaklanjuti tiap bangunan secara interaktif.

## 6. Metodologi (Ringkas)

1. **Pemetaan** seluruh bangunan dari citra satelit/udara.
2. **Penghitungan luas** tiap bangunan dari polygon.
3. **Verifikasi batas wilayah** — setiap titik diuji terhadap polygon batas resmi kecamatan/desa (BATAS_KECAMATAN_DESA). *(Tahap inilah yang mengoreksi angka.)*
4. **Verifikasi fungsi** bangunan secara digital (rumah, toko, kantor, dll).
5. **Pemeringkatan** berdasarkan potensi retribusi tertinggi.

## 7. Manfaat bagi Dinas (Nilai Investasi)

- **Penghematan besar** vs survei manual: 20.460 bangunan × Rp 1 juta ≈ **Rp 20,5 miliar** → dipangkas menjadi sebagian kecil.
- **Pengembalian biaya sangat cepat:** investasi kembali bila hanya **±3 bangunan usaha** berhasil ditagih.
- **Fokus penagihan:** ± 6.350 lead usaha aktif siap ditindaklanjuti, dengan nilai retribusi Rp 19,9 miliar.
- **Angka kredibel & teraudit** — siap dibawa ke rapat anggaran karena setiap bangunan dapat ditelusuri di peta.

## 8. Harga Penawaran

> **[ASUMSI HARGA — mohon konfirmasi sebelum final]**

Nilai wajar dihitung berdasarkan **harga per titik data terverifikasi**, dengan bobot lebih tinggi untuk lead usaha:

| Komponen | Jumlah | Harga satuan | Subtotal |
|---|---:|---:|---:|
| Lead bangunan usaha (terverifikasi & aktif) | 6.350 | Rp 12.000 | Rp 76.200.000 |
| Data bangunan umum (terverifikasi) | 14.110 | Rp 3.500 | Rp 49.385.000 |
| **Nilai wajar (harga normal)** | **20.460** | | **± Rp 125.000.000** |

### Penawaran Khusus Kecamatan Soreang

> ~~Rp 125.000.000~~
> ## Rp 60.000.000
> *(enam puluh juta rupiah)*

Sebagai paket perdana, harga khusus **Rp 60.000.000** — hemat ± Rp 65 juta dari nilai wajar, atau ± **Rp 2.930 per bangunan terverifikasi**.

> *Harga belum termasuk PPN (bila berlaku).*

**Opsi pembayaran:**
- Sekali bayar di muka, atau
- Dua termin: 50% saat kontrak, 50% saat serah terima data.

## 9. Mekanisme Penyerahan

1. Penandatanganan kontrak / SPK.
2. Data tayang pada **halaman peta satelit website SIBEDAS PBG** maks **7 hari kerja** setelah kontrak, dengan akses login untuk petugas Dinas.
3. Satu sesi **briefing/pendampingan** penggunaan halaman peta.
4. Garansi pendampingan & perbaikan tampilan data **14 hari** setelah serah terima.

## 10. Keterbatasan Data (Transparansi)

- Angka potensi retribusi adalah **estimasi** berbasis luas dari citra, **bukan** nilai ketetapan retribusi final.
- Fungsi bangunan hasil verifikasi digital **perlu konfirmasi petugas** sebelum penetapan resmi.
- Lead usaha aktif (± 6.350) telah lolos verifikasi fungsi via sumber publik; sisanya (prediksi hunian) bertarif lebih rendah dan **perlu sampling lapangan**.
- Batas wilayah sudah diverifikasi point-in-polygon terhadap batas resmi; selisih kecil di garis batas masih mungkin.

## 11. Penutup

Data siap diserahkan segera setelah kesepakatan. Kami juga menyediakan **contoh 50 data gratis** dan demonstrasi peta sebagai bahan pertimbangan.

Hormat kami,


**Nauval Zulfikar**
Telp/WA: +62 821-1704-3831
Email: zulfikar.nauval1998@gmail.com

---

*Lampiran: contoh struktur data (50 baris), tangkapan layar dashboard peta, daftar 20 bangunan usaha bernilai tertinggi (verified).*
