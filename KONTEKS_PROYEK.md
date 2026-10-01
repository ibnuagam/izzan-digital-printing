# Konteks proyek Izzan Digital Printing

Terakhir diperbarui: 30 September 2026.

## Tujuan dan sumber awal

Pengguna ingin memisahkan chat diskusi, chat pengembangan aplikasi, dan chat penulisan skripsi, dengan folder proyek yang sama sebagai acuan bersama. Diskusi awal sudah dilakukan; aplikasi belum dibuat dan proposal belum diubah.

Judul pada proposal: **Implementasi Algoritma K-Means Clustering untuk Analisis Pola Pemesanan Layanan Percetakan pada Toko Percetakan Izzan Digital Printing**.

Dokumen sumber: `/Users/ibnuagam/Desktop/Skripsi ICA/Sempro/Seminar Proposal.docx`.

Arahan dosen yang disampaikan pengguna:

- Aplikasi berbasis web mengelola data pemesanan, melakukan analisis, dan menampilkan hasil pengelompokan pola pemesanan.
- Karakteristik meliputi jenis layanan, frekuensi, jumlah atau volume, dan periode waktu pemesanan.
- Data transaksi diolah menjadi informasi untuk mendukung operasional toko.
- Masalah praktisnya adalah toko kadang kekurangan stok karena belum mengetahui pola pemesanan.

Proposal awal menyebut Python untuk pengembangan web, K-Means sebagai metode utama, Elbow Method untuk membantu memilih jumlah cluster, dan Silhouette Score untuk evaluasi. Pengguna kini telah memilih PHP dan Laravel; dokumen proposal belum direvisi untuk mencerminkan keputusan tersebut. Batasan awal berfokus pada analisis dan visualisasi tanpa membangun manajemen produksi secara menyeluruh.

## Perangkat dan alat yang dikonfirmasi pengguna

- Perangkat: **MacBook M1**, menggunakan macOS. Versi macOS belum diperiksa.
- Editor pengembangan: **Visual Studio Code (VS Code)**.
- Database lokal: pengguna menggunakan **XAMPP dan phpMyAdmin**. phpMyAdmin adalah antarmuka pengelolaan; jenis dan versi server database serta versi PHP harus diperiksa saat implementasi.
- Pengguna terbiasa dengan **Laravel**, memiliki kemampuan coding dasar, dan belum terbiasa dengan **Python**.
- Pengguna berencana membuat chat baru dalam folder yang sama untuk vibe coding. Perlu arahan bertahap dan penjelasan kode penting.
- Pemeriksaan awal pada 30 September 2026: PHP terminal/Homebrew 8.3.30, Composer 2.8.9 (menggunakan PHP Homebrew), Node.js 22.17.0, npm 10.9.2, dan PHP XAMPP 8.2.4. Klien database bawaan XAMPP melaporkan MariaDB 10.4.28 (x86_64); versi server aktif dan koneksinya belum diperiksa. Ada dua PHP berbeda, sehingga runtime saat menjalankan aplikasi perlu konsisten. Belum ada instalasi atau konfigurasi aplikasi yang dilakukan.

## Arah fitur hasil diskusi

Tiga peran yang diinginkan:

- **Admin:** mengelola layanan atau produk, bahan, harga, pesanan, pembayaran, serta melihat dan menjalankan analisis pola pemesanan.
- **Manajer atau pemilik:** dashboard berfokus pada layanan terlaris, tren periode, volume, penerimaan pembayaran, kelompok pola pemesanan, dan informasi yang berguna untuk kebutuhan bahan.
- **Pelanggan:** arah diskusi sebelumnya mencakup katalog, pemilihan spesifikasi, pemesanan, unggah desain, pembayaran, dan pemantauan status. Pengguna juga sempat mempertanyakan apakah pelanggan hanya melihat katalog; belum ada keputusan final untuk mengurangi cakupan tersebut.

Pemesanan dan pembayaran adalah fitur pendukung. Implementasi dan evaluasi K-Means tetap menjadi pusat penelitian.

Usulan pembayaran awal adalah transfer bank dengan unggah bukti dan verifikasi admin. Payment gateway belum diputuskan. Jika menerima DP, bedakan nilai pesanan, pembayaran diterima, dan sisa tagihan. Penerimaan pembayaran bukan laba.

Untuk percetakan, admin mungkin perlu memeriksa desain dan spesifikasi sebelum mengonfirmasi harga; aturan harga final belum ditentukan.

## Data dan analisis

- Pengguna ingin memulai dengan **data dummy**. Data historis nyata, periode, dan kelengkapan data toko belum tersedia untuk analisis.
- Usulan simulasi: 6–10 layanan, 12 bulan, dan sekitar 500–1.000 transaksi. Ini contoh cakupan pengembangan, bukan batas minimum ilmiah atau keputusan final.
- Dua jalur input yang disarankan: impor CSV transaksi historis dan input manual pesanan harian. Keduanya menuju database yang sama dengan pemeriksaan duplikasi.
- Jumlah produk dan volume harus berbeda. Contoh: 3 spanduk, masing-masing 2 m × 1 m, menghasilkan total luas 6 m². Brosur dapat memakai lembar. Satuan yang digunakan toko harus dikonfirmasi.
- Tanggal transaksi menentukan periode analisis. Frekuensi dihitung otomatis dari transaksi, bukan diinput sebagai angka manual.
- Usulan unit analisis: ringkasan satu kombinasi layanan–bahan dalam satu periode tetap, seperti bulan. Unit final bergantung pada data nyata dan kesepakatan metodologi.
- Usulan fitur awal K-Means adalah frekuensi dan volume, dengan fitur tambahan hanya jika beralasan. Jenis layanan, bahan, dan periode dapat menjadi identitas ringkasan; perlakuan setiap variabel harus dijelaskan dalam skripsi.
- Jangan membandingkan lembar, pcs, meter panjang, dan m² seolah memiliki makna yang sama. Normalisasi angka tidak menyelesaikan perbedaan satuan; strategi analisis atau konversi bahan harus ditentukan sebelum implementasi.
- Bedakan periode tanpa pesanan dari periode yang datanya tidak tersedia. Jangan menghapus pesanan besar secara otomatis sebagai outlier.
- Status pesanan diperlukan agar pesanan batal dan kebutuhan bahan yang terealisasi dapat dibedakan. Kebijakan pemilihan status untuk analisis belum final.
- Rekap layanan terlaris dan grafik waktu berasal dari perhitungan transaksi biasa. K-Means menambahkan pengelompokan berdasarkan kemiripan fitur numerik.
- Jangan memaksakan tiga cluster atau nama tinggi, sedang, rendah sebelum membaca hasil. Interpretasikan karakteristik cluster setelah evaluasi.
- K-Means tidak otomatis menentukan jumlah pembelian bahan masa depan, membuktikan penurunan kekurangan stok, atau menjelaskan alasan pelanggan membeli. Hubungkan hasil dengan prioritas bahan secara terukur dan batasi klaim sesuai bukti.
- Bukti pola musiman membutuhkan riwayat beberapa siklus yang sebanding.

## Keputusan teknologi dan rencana pengerjaan

**Keputusan final pengguna pada 30 September 2026: bahasa backend PHP dengan framework Laravel, termasuk pengolahan data dan implementasi K-Means.** Pengguna akan melanjutkan pengerjaan di chat vibe coding dalam folder yang sama. Kombinasi Laravel dan Python yang sempat disarankan sudah digantikan oleh keputusan ini.

- Laravel dan PHP digunakan untuk login, hak akses, katalog, transaksi, pembayaran, dashboard, pengolahan data, K-Means, SSE untuk Elbow, dan Silhouette Score.
- Blade, HTML, CSS, dan JavaScript tetap menjadi usulan untuk tampilan serta grafik; pilihan komponen frontend belum ditetapkan.
- Server database lokal menggunakan lingkungan XAMPP pengguna, setelah jenis dan versinya diperiksa. MySQL sempat disarankan, tetapi kompatibilitas lingkungan aktual belum diperiksa.
- Tidak ada modul atau layanan Python dalam rancangan yang dipilih. Jangan menambahkannya tanpa perubahan keputusan dari pengguna.
- Pilihan pustaka PHP untuk analisis belum ditetapkan. PHP-ML adalah salah satu kandidat dengan dokumentasi K-Means: `https://php-ml.readthedocs.io/en/latest/machine-learning/clustering/k-means/`. Periksa kompatibilitas dan kebutuhan evaluasi sebelum memilih.
- Jika sebagian perhitungan ditulis sendiri, verifikasi normalisasi, iterasi centroid, SSE, dan Silhouette menggunakan contoh yang dapat dihitung atau dibandingkan secara independen.
- Revisi keterangan Python dan pustaka terkait di proposal sesuai implementasi PHP dan Laravel, serta selaraskan dengan dosen saat membahas skripsi. Dokumen proposal belum diubah. Penambahan pemesanan pelanggan dan pembayaran juga perlu tercermin dalam batasan serta kebutuhan sistem.
- Pengguna memisahkan chat diskusi dari implementasi. Mulai implementasi saat chat coding meminta pengerjaan; keputusan teknologi ini sendiri bukan permintaan untuk mulai membangun di chat diskusi.

Urutan awal yang disarankan: fondasi dan hak akses → layanan, bahan, pesanan, dan dummy → dashboard rekap → modul K-Means → fitur pelanggan dan pembayaran → pengujian dan dokumentasi. Sesuaikan urutan dengan permintaan pada chat implementasi.

Flowchart dan ERD diminta untuk dibahas nanti, setelah kebutuhan lebih jelas. Jangan membuatnya hanya karena tercantum dalam catatan ini.

## Kolaborasi melalui GitHub

- Pengguna meminta proyek dibagikan melalui repositori GitHub agar dapat berdiskusi dan berkolaborasi dengan teman.
- Repositori pada akun `ibnuagam`: `https://github.com/ibnuagam/izzan-digital-printing`. Awalnya private; pada 30 September 2026 pengguna secara eksplisit meminta perubahan menjadi **public** agar teman dapat membuka dan clone tanpa undangan. Perubahan telah dilakukan melalui API GitHub menggunakan autentikasi Git yang sudah tersedia, dan akses tanpa login berhasil diverifikasi.
- Folder lokal terhubung melalui remote `origin` ke `https://github.com/ibnuagam/izzan-digital-printing.git`. Branch utama `main` sudah di-push dan melacak `origin/main`.
- `README.md` dan `.gitignore` telah disiapkan. README menjelaskan status, teknologi, catatan bersama, dan alur kolaborasi. Konfigurasi `.env`, dependensi, database lokal, serta lokasi unggahan pelanggan dan data nyata dikecualikan dari Git.
- GitHub CLI (`gh`) tidak tersedia saat diperiksa. Pembuatan repositori dilakukan melalui browser setelah pengguna login; pengunggahan commit menggunakan Git dari terminal dan berhasil.
- Membuka, mengunduh, dan clone sekarang tidak memerlukan undangan. Akses push langsung untuk teman belum diberikan; username teman belum diketahui. Gunakan undangan collaborator untuk akses push, atau fork dan pull request tanpa akses tersebut.
- Pembaruan tidak otomatis tersinkronkan: perubahan lokal dibagikan dengan commit dan push, lalu pengembang lain melakukan pull. Gunakan branch dan pull request untuk meninjau perubahan.

## Hal yang perlu dipastikan saat memulai implementasi

- Versi PHP pada XAMPP, Composer, Laravel yang cocok, server database, dan alat frontend yang tersedia; perhatikan lingkungan Apple Silicon. Python bukan kebutuhan proyek yang telah dipilih.
- Layanan, jenis bahan, satuan, serta aturan harga toko.
- Cakupan pelanggan, pemeriksaan pesanan, dan pembayaran versi awal.
- Unit pengelompokan, periode, fitur numerik, dan strategi menangani satuan volume berbeda.
- Ketersediaan data nyata untuk penelitian dan penyesuaian proposal dengan dosen.

## Pengunggahan aplikasi ke GitHub (30 September 2026)

- Kode aplikasi ditemukan di `/Applications/XAMPP/xamppfiles/htdocs/Project-Skripsi/izzan-printing`, terpisah dari checkout diskusi yang sebelumnya hanya berisi dokumentasi. Salinan kode dimasukkan ke root repositori agar dapat di-clone teman pengguna. Folder XAMPP tetap lokasi aplikasi yang sedang dijalankan; perubahan di sana belum otomatis tersinkron ke GitHub.
- Kondisi implementasi yang diperiksa: Laravel 13, PHP minimal 8.3; autentikasi tiga peran, registrasi pelanggan, data master layanan/bahan, gambar layanan, katalog, pesanan/unggah desain, penawaran harga dan perubahan status tersedia. Pembayaran, K-Means, stok, nota dan pemberitahuan WhatsApp belum tersedia.
- Repositori menyertakan migration, seeder dummy, composer.lock dan panduan Windows. .env, database lokal, desain pelanggan, gambar unggahan, vendor, cache dan log tidak diunggah. .env.example disiapkan untuk database MySQL/MariaDB lokal.

## Pembaruan GitHub (1 Oktober 2026)

- Salinan kode terbaru dari folder XAMPP disinkronkan: pembayaran simulasi DP/pelunasan dengan bukti privat dan verifikasi admin, kewajiban lunas sebelum penyerahan, nota cetak, chat privat per pesanan, input rupiah, template WhatsApp manual, kartu JPG dan catatan pengiriman manual. Pembayaran/WhatsApp tidak otomatis mengirim uang atau pesan.
- Metode bank/QRIS adalah dummy. K-Means, stok, laporan dan dashboard keuangan belum tersedia. .env.example menambahkan placeholder alamat/telepon toko; konfigurasi dan data lokal tetap tidak diunggah.
