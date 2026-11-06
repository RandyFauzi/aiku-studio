# Deployment shared hosting Hostinger

Aplikasi berjalan sebagai PHP biasa. Build frontend dilakukan di komputer lokal; tidak perlu menjalankan `composer dev`, Vite server, Node.js, Python, Chromium, atau queue worker permanen pada hosting.

## Persiapan

1. Pilih PHP 8.3+ untuk web dan CLI, dengan PDO MySQL, mbstring, intl, XML/DOM, fileinfo, curl, zip, GD, dan OpenSSL. Periksa `php -v` serta `composer check-platform-reqs` di hosting.
2. Buat database dan user MySQL melalui hPanel. Gunakan database baru, bukan database lain yang sudah berisi data.
3. Lokal: `npm ci`, `npm run build`. Dependency development Node tidak perlu diunggah.
4. Pasang dependensi PHP produksi dengan `composer install --no-dev --optimize-autoloader` pada salinan deployment atau server yang memiliki SSH. Jangan menghapus dependensi development pada workspace lokal utama hanya untuk membuat paket.
5. Gunakan `.env.hostinger.example` sebagai acuan `.env` server. Isi URL HTTPS, kredensial database, SMTP, dan konfigurasi Atlas. Pertahankan rahasia di server. Gunakan APP_DEBUG=false dan secure cookie.

## Struktur aman

### Pembaruan laporan beberapa sesi per hari

Setelah menarik kode fitur multi-shift, jalankan `php artisan migrate --force` dari root aplikasi sebelum worker menambah sesi kedua. Migrasi `2026_11_06_000004` menghapus batas unik `(user_id, work_date)` dan mempertahankan indeks pencarian; migrasi `2026_11_06_000005` memperbaiki instalasi parsial secara aman tanpa menghapus laporan. Keduanya aman diulang untuk langkah pembaruan ini. Pastikan `php artisan migrate:status` menampilkan keduanya sudah dijalankan.

Jika kode sudah baru tetapi indeks unik lama masih aktif, sesi kedua ditolak dengan pesan database belum diperbarui; laporan pertama tidak ditimpa. Jangan menghapus laporan atau menjalankan `migrate:fresh` di produksi untuk mengatasi masalah ini.

Pilihan utama: document root domain diarahkan ke folder `public` Laravel jika pengaturan hosting memungkinkan.

Jika root harus `public_html`, gunakan struktur sibling:

```text
/home/u123456789/domains/domain.tld/
  aiku-studio/       # aplikasi lengkap, .env, vendor, storage, public/build
  public_html/      # hanya isi folder public: build/, assets/, favicon, .htaccess
```

Salin isi `public/` ke `public_html/`, lalu gunakan `deploy/hostinger/public_html/index.php` sebagai index PHP publik. File tersebut memuat aplikasi dari `../aiku-studio` dan mengatur public path untuk request web. Simpan juga `aiku-studio/public/build` untuk operasi CLI/cache view. Jangan menaruh `.env`, snapshot, SQL, vendor, atau storage privat di `public_html`.

Storage bukti/PDF diakses melalui route login; symlink `storage:link` tidak dibutuhkan. Pastikan `storage` dan `bootstrap/cache` bisa ditulis PHP tanpa memberikan izin 777.

## Database dan file

Ada dua pilihan, gunakan salah satu:

- **Dump MySQL lokal:** impor `storage/app/private/migration/aiku-studio-hostinger.sql` ke database kosong melalui phpMyAdmin. Dump memiliki struktur tabel, data payroll, UUID akun studio, serta user administrator, tanpa isi session/cache lokal. Jangan menjalankan migrasi fresh setelah impor. Ubah password administrator setelah import.
- **Migration + snapshot:** jalankan `php artisan migrate --force`, kemudian `php artisan payroll:import-legacy /path/private/legacy-snapshot.json --participants=/path/private/studio_participants.json`. Import menolak target payroll yang sudah berisi data. UUID alternatif bisa dibawa melalui dump `studio_accounts` atau snapshot deployment yang disiapkan.

Unggah isi `storage/app/private/payroll/` (bukti, PDF, file sesi Atlas bila digunakan) ke folder privat yang sama di aplikasi server. Jangan unggah file `initial-admin.txt` lokal ke hosting. Jangan mengunggah screenshot/laporan migrasi bila tidak diperlukan.

Untuk instalasi baru tanpa dump user, buat login dengan `php artisan panel:admin email-admin@example.com`; password diminta secara tersembunyi. Untuk dump yang sudah membawa user, jalankan perintah yang sama untuk mengganti password sebelum publikasi.

Untuk APP_KEY server baru, jalankan `php artisan key:generate --force` sekali sebelum menyimpan data terenkripsi. Jangan mengganti key aplikasi yang sudah digunakan. Kebutuhan payroll lama tidak menyimpan encrypted Laravel fields, tetapi sesi login terenkripsi membutuhkan key server tetap.

## Cron

Di hPanel, buat cron custom setiap menit:

```sh
/usr/bin/php /home/u123456789/domains/domain.tld/aiku-studio/artisan schedule:run
```

Sesuaikan binary PHP dengan versi 8.3+ pada akun. Scheduler memakai callback internal `Artisan::call`, sehingga sync tidak memanggil shell/subprocess. Alternatif khusus sync: cron langsung `php /path/aiku-studio/artisan atlas:tick`, tanpa scheduler lain. Jangan memasang kedua cron sekaligus.

`sync-now` memasukkan pekerjaan ke state database, lalu cron memprosesnya bertahap. Tombol auto-sync menyimpan interval di database. Di lokal, jalankan `php artisan schedule:work` dari terminal terpisah bila ingin mencoba sinkronisasi dengan koneksi Atlas valid. Web panel tidak mematikan/menghidupkan proses CMD.

## Atlas dan email

Atlas memerlukan sesi yang masih valid di `ATLAS_COOKIE` atau `storage/app/private/payroll/atlas-session.json`. File session berformat storage-state Playwright, tetapi runtime Playwright tidak diperlukan. Bila Atlas menolak cookie dari server/IP baru atau memerlukan login tambahan, perbarui sesi. DNS Atlas belum berhasil dijangkau saat migrasi lokal, sehingga lakukan probe baca `php scripts/check_atlas.php` di lingkungan yang dapat mengakses Atlas sebelum mengaktifkan auto-sync.

Masukkan kredensial SMTP yang benar; contoh port 465 menggunakan MAIL_SCHEME=smtps. Gunakan pengaturan resmi penyedia email untuk port lain. Tombol kirim tidak menganggap MAIL_MAILER=log/array sebagai email terkirim. Tidak ada email nyata yang dikirim selama migrasi.

## Aktivasi dan pemeriksaan

```sh
php artisan migrate --force
php artisan optimize
php artisan schedule:list
```

Perintah migrate setelah dump hanya menjalankan migration baru karena tabel migrations ikut diimpor. Periksa login/logout, tujuh halaman, filter rekaman, satu perubahan kontributor uji, slip live, unduh PDF/gambar, serta state cron. Uji email hanya pada alamat yang disetujui. Jangan menjalankan test suite terhadap database produksi; tes otomatis memakai SQLite :memory:.

Sediakan backup MySQL dan `storage/app/private/payroll` sebelum perubahan data berikutnya. Hosting belum diunggah atau dipublikasikan oleh migrasi lokal ini.

Referensi: [Laravel di Hostinger](https://support.hostinger.com/en/articles/1583301-which-laravel-versions-are-supported-at-hostinger), [Composer Hostinger](https://www.hostinger.com/support/5792078-how-to-use-composer-at-hostinger/), [Laravel Scheduler](https://laravel.com/framework/docs/13.x/scheduling).
