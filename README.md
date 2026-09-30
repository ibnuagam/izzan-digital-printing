# Izzan Digital Printing

Aplikasi Laravel untuk proyek skripsi analisis pola pemesanan layanan percetakan. Backend menggunakan PHP; K-Means direncanakan dalam PHP.

## Fitur saat ini

- Login admin, manajer dan pelanggan; registrasi pelanggan.
- Pengelolaan layanan/bahan dan gambar layanan; katalog pelanggan.
- Pesanan dengan jumlah, ukuran, volume, unggah desain privat, penawaran harga, persetujuan pelanggan dan status pengerjaan.

Pembayaran, analisis K-Means, stok, nota dan notifikasi WhatsApp belum diimplementasikan. Data seeder adalah dummy, bukan hasil penelitian toko.

## Instalasi Windows (PowerShell)

1. Siapkan Git, Composer, PHP **8.3 atau lebih baru yang kompatibel dengan composer.lock**, dan MySQL/MariaDB (dapat menggunakan XAMPP). PHP 8.2 tidak dapat menjalankan proyek ini. Periksa:

```powershell
php -v
composer -V
where.exe php
php -m
```

Pastikan Composer memakai PHP yang sama. Aktifkan ekstensi BCMath (perhitungan volume/harga), PDO MySQL, mbstring, OpenSSL, Fileinfo, DOM/XML, cURL dan ekstensi yang diminta Composer. GD dibutuhkan untuk menjalankan tes unggah gambar; PDO SQLite dibutuhkan untuk tes otomatis. Bila PHP XAMPP masih 8.2, gunakan PHP CLI 8.3+ untuk Composer dan Artisan; MySQL XAMPP tetap dapat dipakai.

2. Clone lalu pasang dependensi:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/ibnuagam/izzan-digital-printing.git
cd izzan-digital-printing
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Jika sudah memiliki folder dari ZIP lama, clone ke folder baru. Folder yang benar memiliki `artisan`, `composer.json`, `app`, `routes`, dan `resources`. Tidak perlu membuat proyek Laravel baru atau memasang installer Laravel global.

3. Nyalakan MySQL pada XAMPP. Buat database kosong `izzan_printing` melalui phpMyAdmin. Sesuaikan `.env` dengan koneksi laptop masing-masing:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=izzan_printing
DB_USERNAME=root
DB_PASSWORD=
```

Isi password bila server lokal memilikinya. Kemudian:

```powershell
php artisan config:clear
php artisan migrate
php artisan db:seed --class=DemoUserSeeder
php artisan db:seed --class=MasterDemoSeeder
php artisan storage:link
php artisan serve
```

Buka http://127.0.0.1:8000. Tampilan aktif menggunakan CSS/JavaScript di `public`; npm/build tidak diperlukan untuk menjalankan tampilan saat ini. Apache tidak diperlukan bila menggunakan `artisan serve`. Bila memakai Apache, DocumentRoot harus menunjuk folder `public`.

## Akun simulasi lokal

| Peran | Email | Password |
| --- | --- | --- |
| Admin | admin@izzan.test | IzzanDemo2026! |
| Manajer | manajer@izzan.test | IzzanDemo2026! |
| Pelanggan | pelanggan@izzan.test | IzzanDemo2026! |

Seeder tersebut hanya dapat berjalan ketika `APP_ENV=local`. Jangan gunakan akun demo untuk produksi. Gambar/desain dan transaksi laptop pemilik tidak disertakan; teman dapat membuat pesanan dummy sendiri.

## Pembaruan dan pengujian

Untuk clone yang sudah ada dan tidak memiliki perubahan lokal:

```powershell
git pull origin main
composer install
php artisan migrate
php artisan optimize:clear
```

Jika ada perubahan lokal, simpan dalam commit/branch terlebih dahulu; jangan menghapusnya untuk memaksa pull. Untuk mengusulkan perubahan, buat branch lalu pull request. Clone publik dapat dilakukan tanpa akun; push membutuhkan akses collaborator.

Tes menggunakan SQLite terpisah di memori, bukan database operasional:

```powershell
php artisan test
```

`.env`, database lokal, file unggahan, `vendor` dan cache tidak dibagikan. Setiap laptop memiliki database dan konfigurasi sendiri.
