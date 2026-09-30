# Izzan Digital Printing

Proyek skripsi aplikasi berbasis web untuk mengelola pemesanan layanan percetakan dan menganalisis pola pemesanan menggunakan K-Means. Informasi yang dihasilkan membantu pengelola memahami layanan yang sering dipesan, volume permintaan, periode pemesanan, dan prioritas kebutuhan bahan.

Repositori private: [ibnuagam/izzan-digital-printing](https://github.com/ibnuagam/izzan-digital-printing). Branch utama: `main`.

## Status proyek

Per 30 September 2026, proyek berada pada tahap diskusi kebutuhan dan pemeriksaan lingkungan pengembangan. Kode aplikasi Laravel belum dibuat. Repositori awal berisi catatan kebutuhan dan arahan pengembangan; panduan menjalankan aplikasi akan ditambahkan setelah aplikasi tersedia.

## Teknologi yang dipilih

- Bahasa backend: PHP.
- Framework: Laravel.
- Analisis K-Means dan evaluasi: PHP di dalam proyek Laravel; pustaka analisis belum dipilih.
- Editor pengembang utama: VS Code di MacBook M1.
- Database lokal pengembang utama: lingkungan XAMPP, dikelola melalui phpMyAdmin. Jenis dan versi server aktif perlu diverifikasi.

Versi framework dan komponen final mengikuti pemeriksaan kompatibilitas lingkungan. PHP Homebrew dan PHP XAMPP pada perangkat pengembang utama memiliki versi berbeda; detail pemeriksaan dicatat dalam konteks proyek.

## Fokus aplikasi

- Admin mengelola layanan, bahan, pemesanan, pembayaran, dan proses analisis.
- Manajer melihat ringkasan transaksi, tren periode, layanan terlaris, serta karakteristik kelompok pemesanan.
- Fitur pelanggan yang dibahas mencakup katalog, pemesanan, unggah desain, pembayaran, dan status pesanan; cakupan final masih perlu ditetapkan.
- Rekap penjualan dan grafik waktu dibedakan dari hasil K-Means. Clustering tidak otomatis menjadi prediksi stok atau penjelasan sebab pelanggan membeli.
- Data dummy digunakan untuk pengembangan dan simulasi, bukan sebagai data penelitian lapangan.

## Catatan bersama

- [Konteks proyek](KONTEKS_PROYEK.md): kebutuhan, keputusan teknologi, batasan, dan pertanyaan terbuka.
- [Panduan agen](AGENTS.md): arahan untuk Codex ketika mengerjakan proyek ini.

Keputusan penting harus dicatat kembali agar diskusi aplikasi dan penulisan skripsi menggunakan acuan yang sama.

## Cara berkolaborasi

1. Pemilik membagikan tautan repositori. Jika repositori private, pemilik mengundang akun GitHub teman sebagai collaborator.
2. Setiap pengembang melakukan clone repositori ke perangkatnya dan membaca catatan bersama.
3. Sebelum mulai bekerja, ambil pembaruan terbaru dengan Git pull. Gunakan branch terpisah untuk perubahan fitur, misalnya `codex/dashboard-manajer`.
4. Simpan perubahan melalui commit, lalu push branch ke GitHub dan buat pull request agar perubahan dapat dibahas serta diperiksa.
5. Setelah perubahan digabungkan, pengembang lain melakukan pull untuk memperbarui salinan lokalnya.

GitHub tidak menyinkronkan perubahan lokal secara otomatis. Perubahan di VS Code maupun catatan hasil diskusi baru tersedia bagi teman setelah di-commit dan di-push. GitHub Issues dapat digunakan untuk membahas kebutuhan, bug, dan pembagian pekerjaan.

## Konfigurasi dan data lokal

Ketika aplikasi tersedia, setiap pengembang menggunakan file `.env` dan database lokal masing-masing. Contoh konfigurasi tanpa kredensial dapat disimpan dalam `.env.example`. Struktur database dibagikan melalui migration Laravel dan data simulasi melalui seeder.

File `.gitignore` mengecualikan konfigurasi lingkungan, dependensi yang dihasilkan, log, database lokal, serta lokasi data nyata dan unggahan pelanggan. Jangan memasukkan data transaksi nyata atau bukti pembayaran ke repositori tanpa peninjauan khusus. Lokasi penyimpanan aktual akan disesuaikan saat implementasi.
