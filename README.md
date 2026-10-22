# Absensi Seminar (Laravel)

Sistem absensi seminar multi-sesi: peserta punya QR di ID card, panitia scan lewat web, sesi bisa dibuka/ditutup (manual atau otomatis berdasarkan jam).

## Fitur

- Peserta: input manual, import CSV (`nama, nip, kab/kota`), cari, edit, hapus.
- Sesi: tambah/hapus sesi, atur jam buka–tutup, mode Auto / Buka / Tutup.
- Scan QR (kamera atau scanner USB/input manual), anti-duplikat per sesi.
- Template ID card 80×110 mm dari gambar dengan anchor `<<CODE>> <<QRCODE>> <<NAMA>> <<NIP>> <<KAB/KOTA>>`, hasil berupa **PDF**.
- Rekap kehadiran + export CSV.

## Kebutuhan

- PHP **8.3+** dengan ekstensi: `gd`, `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `zip`, `xml`
- Composer 2
- MySQL / MariaDB (mis. Laragon)

## Instalasi di komputer baru

1. **Salin proyek** ke komputer baru (folder `absensi-jelai`). Folder `vendor/` tidak perlu disalin.
2. **Pasang dependensi**
   ```bash
   composer install
   ```
3. **Buat file `.env`**: salin `.env` dari komputer lama, atau jika tidak ada:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```
   Lalu pastikan isi `.env` seperti ini:
   ```env
   APP_TIMEZONE=Asia/Jakarta
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=absensi_jelai
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   > Kalau `.env` disalin dari komputer lama, `APP_KEY` jangan diganti. Kalau diganti, sesi login lama tidak berlaku (data tetap aman).
4. **Buat database** `absensi_jelai` (Laragon: Menu → MySQL → atau lewat HeidiSQL/phpMyAdmin), collation `utf8mb4_unicode_ci`:
   ```sql
   CREATE DATABASE absensi_jelai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. **Pilih salah satu: data baru atau pindahkan data lama**
   - **Data baru (kosong):**
     ```bash
     php artisan migrate --seed
     ```
     Membuat tabel, akun panitia, dan 7 sesi awal.
   - **Pindahkan data lama (peserta, absensi, sesi):** di komputer lama export database, di komputer baru import ke `absensi_jelai`, lalu jalankan `php artisan migrate` (aman, hanya menambah yang belum ada):
     ```bash
     # komputer lama
     mysqldump -uroot absensi_jelai > absensi_jelai.sql
     # komputer baru
     mysql -uroot absensi_jelai < absensi_jelai.sql
     ```
     (di Laragon `mysqldump.exe` / `mysql.exe` ada di `C:\laragon\bin\mysql\...\bin\`)
6. **Salin file template ID card** dari komputer lama (tidak ikut di database):

   | File | Fungsi |
   |------|--------|
   | `public/card-template.png` | gambar template kartu (anchor sudah dihapus) |
   | `storage/app/card-layout.json` | posisi & ukuran font tiap anchor |
   | `public/fonts/Gotham-Bold.ttf` | font Gotham (opsional, lihat bagian Font) |

   Atau cukup upload ulang desain di menu **Template Kartu** — posisi akan terdeteksi lagi.
7. **Jalankan**
   ```bash
   php artisan serve
   ```
   Buka http://localhost:8000

   Di Laragon bisa juga: taruh folder di `C:\laragon\www\`, lalu buka `http://absensi-jelai.test` (Menu → Apache → Reload / Laragon auto virtual host).

## Login

Akun awal dari seeder: **panitia@example.com** / **panitia123**. Segera ganti password:

```bash
php artisan tinker
>>> App\Models\User::first()->update(['password' => 'passwordbaru'])
```

Tambah akun panitia lain:

```bash
php artisan tinker
>>> App\Models\User::create(['name' => 'Nama', 'email' => 'a@b.c', 'password' => 'rahasia123'])
```

## ID Card & Template

1. Menu **Template Kartu** → upload desain (PNG/JPG rasio 8:11, ukuran cetak 80×110 mm).
2. Desain harus berisi teks **hitam pekat** `<<CODE>>`, `<<QRCODE>>`, `<<NAMA>>`, `<<NIP>>`, `<<KAB/KOTA>>`, berurutan dari atas ke bawah. Sistem mendeteksi posisi dan ukuran font, lalu menghapus teks anchor dari gambar.
3. Atur manual di tabel jika perlu (dalam % dari ukuran kartu), klik **Simpan Posisi**.
4. Menu **Peserta** → **Download PDF semua ID Card**, atau link **PDF** di tiap baris.

### Font (Gotham)

Gotham berlisensi, jadi file font tidak disertakan. Letakkan di `public/fonts/` dengan nama persis **`Gotham-Bold.ttf`** (format TrueType `.ttf`; `.otf`/`.woff2` tidak didukung generator PDF). Tanpa file itu dipakai `Montserrat-Bold.ttf` (sudah disertakan). Setelah mengganti font, hapus cache font PDF bila tampilan belum berubah:

```bash
del storage\app\dompdf-fonts\*
```

## Import peserta

Menu **Peserta** → Import CSV. Kolom: `nama, nip, kab/kota` (header opsional, pemisah `,` atau `;`). NIP yang sudah ada diperbarui, bukan diduplikasi, dan kode QR-nya tetap. Dari Excel: simpan sebagai CSV dan format kolom NIP sebagai Text (agar tidak menjadi `1.98E+17`).

## Scan di HP / perangkat lain

Kamera browser hanya jalan di `localhost` atau **HTTPS**. Untuk scan dari HP lewat jaringan yang sama:

- Gunakan HTTPS (Laragon: Menu → Apache → SSL, atau tunnel seperti ngrok / Cloudflare Tunnel), atau
- Pakai input manual / scanner QR USB (tidak butuh HTTPS).

Server agar bisa diakses perangkat lain: `php artisan serve --host=0.0.0.0 --port=8000` dan izinkan port di Windows Firewall.

Halaman memuat Tailwind dan html5-qrcode dari CDN, jadi perangkat panitia perlu internet.

## Ringkasan pindah komputer (cek cepat)

- [ ] `composer install`
- [ ] `.env` (DB, `APP_KEY`, `APP_TIMEZONE=Asia/Jakarta`)
- [ ] Database `absensi_jelai` dibuat + `php artisan migrate` (atau import dump `.sql`)
- [ ] `public/card-template.png`, `storage/app/card-layout.json`, `public/fonts/Gotham-Bold.ttf`
- [ ] `php artisan serve`

Jika muncul error aneh setelah pindah:

```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
