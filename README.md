# Aplikasi Penyewaan Garasi Kendaraan

Aplikasi web untuk uji kompetensi Pemrogram (LSP). Penyewa dapat menyewa garasi sepeda, mobil, atau mobil listrik secara harian maupun bulanan, dan Admin mengelola garasi, verifikasi penyewa, perpanjangan sewa, serta laporan.

## Fitur

| Role | Fitur |
| --- | --- |
| Tamu | Mendaftar sebagai penyewa (NIK, nama, telepon, email, password) |
| Admin, Penyewa | Login / logout (penyewa harus disetujui Admin terlebih dahulu) |
| Penyewa | Sewa garasi baru: isi kendaraan dan tipe sewa, pilih garasi tersedia beserta total biayanya, unggah bukti pelunasan |
| Admin | Buat garasi secara berkelompok dengan kode otomatis (S001, C010, E005) dan lihat daftar garasi beserta statusnya |
| Admin | Verifikasi pendaftar baru (terima / tolak) |
| Admin | Perpanjang sewa: pilih garasi yang sedang disewa, ubah tanggal selesai, lihat sisa biaya, unggah bukti bayar |
| Admin | Laporan sewa per bulan, per hari, per tipe garasi, dan per penyewa |

## Aturan tanggal dan biaya

- Periode sewa adalah `[tanggal mulai, tanggal selesai)`: garasi kembali tersedia **pada** tanggal selesai.
- Tanggal mulai selalu hari ini.
- Sewa harian: tanggal selesai = tanggal mulai + jumlah hari. Maksimal jumlah hari = jumlah hari dalam bulan mulai − 1.
- Sewa bulanan: tanggal selesai = tanggal mulai + jumlah hari dalam bulan mulai (contoh: 31 Jan 2026 → 3 Mar 2026). Harga = 25 × harga harian.
- Mobil listrik di garasi mobil listrik diisi listrik hingga penuh: biaya = kapasitas baterai (kWh) × harga per kWh.
- Perpanjangan: sisa biaya = selisih hari (tanggal selesai baru − tanggal selesai lama) × harga harian.

Seluruh perhitungan ada di `app/Services/RentalCalculator.php` dan diuji di `tests/Unit/RentalCalculatorTest.php`.

## Teknologi

Laravel 13 (PHP 8.3+), SQLite, Blade, Tailwind CSS (Vite), PHPUnit.

## Cara menjalankan

```bash
git clone https://github.com/AxelValerioErtamto/lsp_axel.git
cd lsp_axel
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Buka http://127.0.0.1:8000. Jika diminta membuat file database SQLite saat migrate, pilih **yes**.

**Akun Admin:** `admin@garasi.test` / `password`

## Menjalankan pengujian

```bash
php artisan test
```

- `tests/Unit`: unit test (white-box) untuk `RentalCalculator` dan enum.
- `tests/Feature`: feature test (black-box) untuk setiap fitur, termasuk boundary value analysis.