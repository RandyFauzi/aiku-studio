# Panduan Penyambungan Privat AIKU STUDIO ↔ KameraKita

Integrasi privat ini memungkinkan Owner AIKU Studio membuat dan mengelola akun Mitra KameraKita langsung dari antarmuka **Pengaturan HP & Akun** di AIKU Studio, per perangkat HP. Fitur ini dirancang khusus untuk penggunaan pribadi owner (bukan pendaftaran publik).

---

## 1. Arsitektur & Keamanan

1. **Server-to-Server Private API**:
   - Komunikasi berlangsung langsung antara backend AIKU Studio dan backend KameraKita via endpoint `/api/private/studio/*`.
   - Menggunakan Bearer Token yang hanya tersimpan di `.env` backend masing-masing server.
   - **Kredensial tidak pernah dikirim ke browser**, tidak pernah dicatat ke file log, dan tidak boleh di-commit ke Git.
2. **Otorisasi Administrator (Role Admin)**:
   - Di AIKU Studio, antarmuka dan endpoint integrasi KameraKita dapat diakses oleh seluruh user dengan role `admin` (administrator studio).
   - Pengguna non-admin (seperti kontributor/pekerja) diblokir dari endpoint dan tidak dapat melihat opsi maupun data integrasi KameraKita.
3. **Idempotensi & Transaksi Atomik**:
   - Pembuatan akun mitra di KameraKita (`User` dan `Partner`) dieksekusi dalam satu transaksi database (`DB::transaction`).
   - Jika terjadi network retry atau timeout, API KameraKita memeriksa keberadaan email terlebih dahulu dan mengembalikan data mitra yang sudah ada secara idempoten (status 200 OK) tanpa membuat duplikasi akun.
   - Jika pembuatan mitra gagal setelah akun HP tersimpan, sistem melaporkan **kegagalan parsial** (HTTP 502) dan memungkinkan owner mencoba lagi atau menautkan ulang dari UI.
4. **Perlindungan Riwayat (No Permanent Deletion)**:
   - Akun HP yang telah tertaut dengan mitra KameraKita tidak dapat dihapus permanen atau diganti emailnya dari daftar HP demi menjaga integritas laporan kerja, payroll, dan riwayat bisnis.
   - Penonaktifan dilakukan melalui pengubahan status menjadi `suspended`, yang secara otomatis membekukan role login di KameraKita tanpa menghapus data historis.

---

## 2. Langkah-Langkah Konfigurasi Server

### Langkah 1: Buat Token Rahasia Acak
Jalankan perintah berikut di terminal server lokal Anda untuk membuat token acak sepanjang 64 karakter hex:
```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```
> **PENTING**: Simpan token ini dengan aman di password manager Anda. Jangan pernah membagikan atau menempelkan token ini ke chat atau repository publik.

### Langkah 2: Konfigurasi di Server KameraKita
Buka file `.env` di server KameraKita dan tambahkan token:
```env
# Private server-to-server integration with AIKU Studio. Keep this token secret.
AIKU_STUDIO_API_TOKEN=masukkan_token_64_karakter_hex_disini
```

### Langkah 3: Konfigurasi di Server AIKU Studio
Buka file `.env` di server AIKU Studio dan tambahkan variabel berikut:
```env
# Private, owner-only KameraKita partner integration. Keep this token secret.
KAMERAKITA_API_URL=https://kamerakitaid.site
KAMERAKITA_API_TOKEN=masukkan_token_64_karakter_hex_yang_sama_disini
AIKU_STUDIO_OWNER_EMAIL=email_owner_anda@example.com
# Daftar kode mitra yang boleh dipilih sebagai induk akun studio (diprioritaskan sesuai urutan)
KAMERAKITA_ALLOWED_PARENT_CODES=KMK-847,KMK-210
```
*Catatan: `KAMERAKITA_API_URL` harus menggunakan domain HTTPS publik tanpa akhiran `/api`. Secara default, hanya akun `KMK-847` (AI KU STUDIO 2) dan `KMK-210` (AI KU Studio) yang ditampilkan di menu dropdown studio agar terisolasi dari mitra lain.*

### Langkah 4: Jalankan Migrasi Database di AIKU Studio
Jalankan migrasi di server AIKU Studio untuk menambahkan kolom tautan mitra ke tabel `device_accounts`:
```bash
php artisan migrate --force
```
*(Di server KameraKita tidak diperlukan migrasi DDL baru karena skema tabel `partners` dan `users` yang ada sudah mendukung seluruh atribut mitra).*

### Langkah 5: Bersihkan dan Segarkan Cache Konfigurasi
Jalankan perintah berikut di **KEDUA server** setelah memperbarui `.env`:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
Jika server produksi Anda memanfaatkan cache konfigurasi untuk performa, bangun kembali cache:
```bash
php artisan config:cache
php artisan route:cache
```

---

## 3. Cara Menguji Koneksi dari UI

1. Buka browser dan login ke **AIKU Studio** menggunakan email yang terdaftar pada `AIKU_STUDIO_OWNER_EMAIL`.
2. Masuk ke menu **Pengaturan HP & Akun** (`/devices`).
3. Pilih salah satu HP (misal: HP Putih).
4. Perhatikan bagian form **Ganti ke Akun / Email Baru**:
   - Jika koneksi berhasil, akan muncul kotak opsi **Buat akun Mitra KameraKita juga**.
   - Centang kotak tersebut, lalu periksa dropdown **Akun utama KameraKita**. Dropdown harus terisi otomatis dengan daftar akun utama / koordinator yang aktif di KameraKita (contoh: `Nama Akun (KMK-001)`).
5. Masukkan email, nama lengkap, WhatsApp mitra, pilih akun utama, lalu klik **Daftarkan & Jadikan Aktif**.
6. Sistem akan:
   - Menyimpan akun di HP tersebut.
   - Membuat akun User & Partner di KameraKita.
   - Menautkan ID mitra (`KMK-xxx`) dan menampilkan badge `Mitra KK: active` di tabel daftar akun.
7. Di baris tabel, owner dapat:
   - Menekan tombol **Nonaktifkan Mitra** / **Aktifkan Mitra** untuk mengubah status login secara instan.
   - Menekan tombol **Edit** untuk memperbarui nama lengkap, WhatsApp, atau memindahkan akun utama.
   - Untuk akun HP lama yang belum tertaut mitra, modal **Edit** menyediakan opsi **Tautkan ke Akun Mitra KameraKita** untuk membuatkan mitra tanpa perlu menghapus akun HP.

---

## 4. Prosedur Rotasi Token (Token Rotation)

Lakukan rotasi token secara berkala atau jika ada indikasi kebocoran:
1. Generate string acak hex baru (64 karakter) via `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
2. Perbarui nilai `AIKU_STUDIO_API_TOKEN` di `.env` KameraKita.
3. Perbarui nilai `KAMERAKITA_API_TOKEN` di `.env` AIKU Studio.
4. Jalankan `php artisan config:clear` (dan `php artisan config:cache` jika relevan) di kedua server.
5. Uji kembali dengan merefresh halaman `/devices` di AIKU Studio.

---

## 5. Rekomendasi Pembatasan Jaringan / IP Whitelist

Untuk keamanan berlapis di level infrastruktur jaringan hosting:
1. **Nginx Web Server** (pada server KameraKita):
   Batasi akses ke blok path `/api/private/studio/` hanya untuk IP publik server AIKU Studio:
   ```nginx
   location /api/private/studio/ {
       allow 103.xxx.xxx.xxx; # IP Server AIKU Studio
       deny all;
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```
2. **Cloudflare WAF**:
   Buat Custom WAF Rule: jika URI path berisi `/api/private/studio/` dan IP source bukan IP server AIKU Studio, lakukan tindakan *Block*.

---

## 6. Troubleshooting

| Gejala Masalah | Penyebab Utama | Solusi |
|---|---|---|
| Dropdown Akun Utama kosong / peringatan "Koneksi server belum disiapkan" | `KAMERAKITA_API_TOKEN` atau `KAMERAKITA_API_URL` belum diisi, atau `php artisan config:clear` belum dijalankan. | Periksa `.env` di AIKU Studio, pastikan URL menggunakan `https://` dan akhiri dengan `php artisan config:clear`. |
| Error `401 Unauthorized` | Token di KameraKita (`AIKU_STUDIO_API_TOKEN`) berbeda dengan token di AIKU (`KAMERAKITA_API_TOKEN`). | Samakan nilai token di kedua server, pastikan tidak ada spasi di awal/akhir nilai, lalu clear cache konfigurasi. |
| Opsi integrasi KameraKita tidak muncul di UI | Pengguna login bukan akun owner yang ditentukan di `AIKU_STUDIO_OWNER_EMAIL`. | Periksa email login admin di pojok atas, pastikan sama persis dengan `AIKU_STUDIO_OWNER_EMAIL`. |
| Error `502 Tersimpan Sebagian` | Server KameraKita tidak merespons atau timeout saat AIKU mencoba membuat mitra. | Akun HP tetap tersimpan di AIKU. Setelah server KameraKita online kembali, buka modal Edit pada akun tersebut dan centang "Tautkan ke Akun Mitra KameraKita" untuk retry tanpa duplikasi. |
| Error `422 Email sudah terdaftar pada peran partner lain` | Email yang diinput sudah digunakan oleh akun admin atau kontributor non-mitra di KameraKita. | Gunakan alamat email khusus untuk mitra, atau gunakan email yang memang ditujukan sebagai mitra. |
