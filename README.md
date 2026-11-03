# AIKu Studio — Laravel

Panel operasional dan payroll hasil migrasi aplikasi Node.js ke Laravel 13/PHP 8.3+. Tujuh halaman lama, data payroll, rekaman, akun studio, bukti gambar, dan arsip PDF telah dipindahkan.

## Menjalankan lokal
Pastikan MySQL Laragon berjalan. Konfigurasi lokal memakai database terpisah aiku_studio_laravel; database asal aiku_payroll tidak diubah.

~~~powershell
php artisan serve
~~~

Buka http://localhost:8000/login. Aset produksi sudah dibangun, jadi PHP server saja cukup untuk mencoba aplikasi.
Kredensial awal tersimpan privat di storage/app/private/initial-admin.txt.
Untuk mengganti login/password:

~~~powershell
php artisan panel:admin admin@aiku.local
~~~

Untuk mengembangkan CSS/JavaScript gunakan composer dev. Sinkronisasi Atlas lokal membutuhkan php artisan schedule:work dari terminal terpisah, sesi Atlas valid, dan koneksi DNS yang dapat menjangkau Atlas.

## Fitur
- Dashboard operasional, kontributor, dan filter/assignment massal rekaman.
- Payroll cut-off 22–21 WITA, bonus, status pembayaran, slip historis dan live.
- PDF dibuat langsung oleh PHP; bukti/PDF dilindungi login.
- Pengaturan shift per nama: beberapa sesi per anggota, pilihan alat, penambahan kontributor, dan deteksi bentrok. Jadwal harian serta simulasi bulanan mengikuti susunan tim yang disimpan.
- Profit dengan konfigurasi tersimpan di database.
- Akun studio dan sinkronisasi Atlas melalui HTTP/cron bertahap.
- Pengiriman slip melalui SMTP yang dikonfigurasi pada .env.
- Login session, CSRF, validasi input dan upload.

Tidak ada fitur menghidupkan/mematikan CMD. Hosting menjalankan PHP biasa. Node.js hanya untuk build aset sebelum upload.

## Dokumentasi
- [Arsitektur, pemetaan variabel, database, kontrak API, dan hasil verifikasi](docs/MIGRATION.md)
- [Deployment shared hosting Hostinger](docs/HOSTINGER.md)
- Contoh konfigurasi produksi: .env.hostinger.example
- SQL siap impor ke database hosting kosong: storage/app/private/migration/aiku-studio-hostinger.sql

Dump SQL dan kredensial bersifat privat. Jangan menaruhnya di public_html.

## Verifikasi
~~~powershell
composer validate --strict
composer check-platform-reqs
composer test
php vendor/bin/pint --test
npm run build
~~~

Test suite memakai SQLite in-memory dan tidak mengubah database operasional.
Audit migrasi membandingkan semua kolom serta checksum file terhadap snapshot privat.
Sinkronisasi Atlas nyata belum terverifikasi karena probe DNS Atlas gagal pada lingkungan migrasi. Email nyata belum dikirim; konfigurasi SMTP dibutuhkan.

## Setup baru
~~~powershell
composer install
Copy-Item .env.example .env
~~~

Atur kredensial MySQL pada .env lalu jalankan:

~~~powershell
php scripts/ensure_app_key.php
php artisan migrate
php artisan panel:admin admin@example.com
npm ci
npm run build
~~~

Gunakan impor snapshot/dump sesuai panduan Hostinger untuk membawa data lama. Salin .env hanya pada instalasi baru; APP_KEY yang sudah digunakan harus dipertahankan.

Simpan composer.lock dan package-lock.json di Git. .env, database lokal, data privat, vendor, node_modules, dan hasil build diabaikan Git.

## Lokasi proyek Windows
Karakter # pada folder induk memunculkan peringatan Vite dan dapat mengganggu dependency scan development. Production build dan penyajian aset berhasil diuji. Path hosting gunakan nama biasa seperti aiku-studio.

Dokumentasi Laravel: https://laravel.com/docs/13.x
