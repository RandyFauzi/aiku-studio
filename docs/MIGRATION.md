# Migrasi AIKu Payroll Panel ke Laravel

Sumber: `C:/laragon/www/AIKu - Studio/aiku-payroll-panel`. Tujuan: proyek Laravel ini. Database sumber `aiku_payroll` hanya dibaca; database lokal tujuan adalah `aiku_studio_laravel` (MySQL). Snapshot privat dibuat pada 2 November 2026. Jangan mengimpor ulang dump SQL lama karena hanya berisi dua tabel dan tidak mewakili database aktif.

## Arsitektur

| Lapisan asal | Implementasi Laravel |
| --- | --- |
| Express dalam `backend/server.js` | Route session/CSRF di `routes/web.php`, controller sesuai domain |
| Sequelize + query MySQL mentah | Eloquent, Query Builder, migration dengan indeks |
| HTML, Alpine, Tailwind CDN | Tujuh view Blade; Alpine/SweetAlert dibundel Vite; CSS Tailwind 3 dibangun lokal untuk kompatibilitas tampilan asal |
| Tarif/perhitungan tersebar | `PayrollCalculator` dan `PayrollPeriod`; tarif default dalam `config/payroll.php` |
| JSON akun studio | Tabel `studio_accounts` beserta UUID peserta Atlas |
| JSON progress / timer dalam memori Node | State dan jadwal tersimpan di `shift_settings`; lock database; cron Laravel |
| Playwright/Python + path Windows | HTTP Laravel + parser Next Flight Atlas, maksimal tiga halaman per pemanggilan cron |
| Puppeteer/Chromium untuk PDF | Dompdf, template PHP/Blade, aset gambar lokal |
| PDF/gambar disajikan langsung dari folder publik | Storage privat, diakses melalui route yang mewajibkan login |
| Google Apps Script `no-cors` untuk email | Endpoint SMTP Laravel, lampiran PDF, error bila transport belum tersedia |
| Tombol Exit CMD / shutdown Node | Dihapus, endpoint tidak tersedia |

Frontend JavaScript tetap dipakai untuk interaksi Alpine dan simulasi. Node.js hanya alat build lokal/CI. Server hosting tidak menjalankan aplikasi Node.js, Python, Chromium, daemon queue, atau skrip start/stop CMD.

## Skema dan variabel data

Semua ID lama, nama field, nilai enum, data JSON, timestamp dan URL bukti dipertahankan agar kontrak frontend tidak berubah.

| Tabel | Field dan arti |
| --- | --- |
| `contributors` | `id`, `nama`, `no_wa`, `domisili`, `no_rek`, `shift`, `hp`, `email`. Nomor rekening/WA disimpan string agar nol awal tidak hilang. Email tidak unik karena satu akun dapat dipakai beberapa pekerja. |
| `payslips` | `id`, `employee_name`, `period`, `total_logged_hours`, `total_approved_hours`, `total_thp`, `bonus`, `status` (`belum`/`sudah`), `file_path`, `evidence_data`, `created_at`. Tidak memiliki `updated_at` sesuai sumber. Nominal uang integer rupiah; durasi double untuk menjaga presisi nilai lama. |
| `payroll_statuses` | `id`, `worker_name`, `period`, `status`, `notes`, `created_at`, `updated_at`. Unik pada pekerja + periode. Status ini mengungguli status slip dalam ringkasan live. |
| `studio_recordings` | `record_id` primary key string; `participant_email`, `task_title`, `recorded_utc` (label WITA historis meskipun namanya UTC), `recorded_at` (UTC), `duration_sec`, `duration_min`, `processing_status`, `billable_status`, `billable_min`, `rejected_min`, `pending_min`, `deferred_min`, `reviewed_utc` (label tampilan lama), `reconciliation`, `assigned_contributor_id`, `assigned_worker_name`, `manual_payment_status`, `notes`, `assigned_at`, `last_synced_at`, `createdAt`, `updatedAt`. Timestamp camelCase dipertahankan. |
| `shift_settings` | `id`, `setting_key`, `setting_value` JSON, `updated_at`. Key: `main_shift_config`, `finance_config`, `atlas_sync_state`, `atlas_auto_sync`. |
| `studio_accounts` | `id`, `email` unik, `name`, `active`, `participant_id`, `created_at`, `updated_at`. Berasal dari 38 entri JSON akun sumber dan pemetaan UUID eksternal. |
| Infrastruktur Laravel | `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`. Akun administrator baru terpisah dari kontributor. |

Assignment tidak memakai foreign key cascade, sehingga penghapusan kontributor tidak menghapus histori kerja. Nama pekerja historis tetap disimpan. Indeks tersedia untuk email, pekerja, waktu rekaman, status pembayaran, serta pekerja/periode slip.

`evidence_data` berisi array hari: `date`, `approved_mins`, `rejected_mins`, `pending_mins`, `total_video_mins`, `notes`, `image_url`/`image_urls`, `tasks`. Setiap task memuat `task_name`, `time_str`, empat nilai menit, dan `notes` (pada data live berisi ID rekaman). Properti lama lain tetap tersalin dalam snapshot. `is_open`/`is_uploading` adalah state UI, tidak mempengaruhi pembayaran.

## Aturan payroll

- Cut-off: tanggal 22 pukul 00.00.00 WITA sampai tanggal 21 bulan berikutnya pukul 23.59.59 WITA. Query memakai batas UTC. Pergantian tahun dan jumlah hari per bulan ditangani kalender, bukan perkiraan 29 hari.
- Training historis: 18–20 September 2026; ID `training_2026` dan label lama tetap tersedia.
- Jika hari memiliki tasks, nilai menit hari dijumlahkan dari tasks dan dibulatkan dua desimal. Jika total durasi task tidak tersedia/0, gunakan approved + rejected + pending, sama seperti sumber.
- `total_logged_hours = logged_minutes / 60`; `total_approved_hours = approved_minutes / 60`.
- `base_thp = round(approved_minutes / 60 * 50000)`; `total_thp = base_thp + bonus`. Pembayaran dihitung dari menit asli sebelum pembulatan tampilan jam.
- Bonus berasal dari slip pekerja pada periode yang sama. Status berasal dari `payroll_statuses`, lalu slip periode tersebut, lalu `belum`.
- Ringkasan mengabaikan nama kosong, `???`, `?`, `-`, `Unknown`, `null`, `undefined`, sesuai sumber.
- Ringkasan statistik rekaman menerapkan seluruh filter sebelum pagination. Pending fallback tetap mengikuti aturan lama jika rekaman masih processing/tidak memiliki hasil QA.
- Status rekap rekaman (`Paid`, `Belum`, `Belum Rekap`) dan status pembayaran pekerja (`sudah`, `belum`) adalah dua variabel berbeda, tetap dipertahankan.
- Slip web dan PDF live mengambil hasil QA terkini, tetapi pembacaan tidak menimpa evidence tersimpan. Edit eksplisit tetap menggunakan snapshot slip yang tersimpan.

## Shift dan profit

Pembaruan 3 November 2026: halaman shift memakai daftar anggota yang dapat diedit per nama dan sesi. Roster, jumlah sesi, alat, durasi, serta hari libur menjadi sumber jadwal harian dan simulasi bulanan. Tidak ada lagi daftar nama tetap dalam kode simulasi. Backend memvalidasi bentrok alat/sesi dan menghitung ulang jam kerja/produksi saat menyimpan. Konfigurasi produksi lama dipertahankan saat deployment pembaruan.

Konfigurasi default asli dipindahkan tanpa perubahan ke `resources/data/default-shifts.json`: `start_time`, `charging_duration_min`, `session_duration_min`, `sessions_count`, `tools_count`, `hp_per_tool`, `hourly_rate`, `day_off`, `qa_yield_rate`, `karyawan`.

Setiap `karyawan` memiliki `name`, `short`, `role`, `daily_work_hrs`, `daily_prod_hrs`, `sessions`, `alat`. Simulasi kalender, hari libur, jam produksi, gaji bulanan, dan yield QA tetap dihitung di Alpine sebagaimana aplikasi asal. Tarif simulasi shift dapat berbeda dari tarif payroll aktual; mengubah simulasi tidak mengubah slip gaji.

Variabel profit: `rateMode` (USD/IDR), `clientRateUsd` (default 5), `kursUsd` (16000), `clientRateIdr` (80000), `operationalExpense` (0). Nilai ini sekarang disimpan di database melalui `/api/finance/settings`; localStorage tetap menjadi cache browser. Nilai khusus yang hanya tersimpan di browser origin lama tidak dapat ditemukan dalam file/database sumber. Masukkan kembali nilai khusus tersebut pada halaman profit setelah pindah origin. Preferensi sidebar `aiku_sidebar_open` tetap lokal per browser.

## Kontrak route

| Domain | Endpoint |
| --- | --- |
| Halaman | `/`, `/index.html`, `/contributors.html`, `/create.html`, `/payslips.html`, `/profit.html`, `/recordings.html`, `/shifts.html` |
| Login | GET/POST `/login`, POST `/logout` |
| Kontributor | GET/POST `/api/contributors`, PUT/DELETE `/api/contributors/{id}` |
| Slip | GET `/api/slips`; POST `/api/payslips`; GET/PUT/DELETE `/api/payslips/{id}`; PATCH `/api/payslips/{id}/status` |
| Dokumen | `/slip/{id}`, `/slip/live/{worker}`; GET `/api/payslips/{id}/export`, `/api/payroll/export-live/{worker}`; POST `/api/payslips/{id}/email` |
| Bukti | POST `/api/upload-evidence`; GET `/evidence_images/{path}`, `/pdf_slips/{path}` |
| Rekaman | GET `/api/studio-recordings`; PUT `/api/studio-recordings/batch-assign` |
| Akun | GET/POST `/api/studio-accounts`; DELETE `/api/studio-accounts/{email}`; PUT `/api/studio-accounts/{email}/toggle` |
| Payroll | GET `/api/payroll/periods`, `/api/payroll/live-summary`, `/api/payroll/worker-evidence`; POST `/api/payroll/status` |
| Shift/profit | GET/POST `/api/shifts/config`, GET/POST `/api/finance/settings` |
| Sinkronisasi | POST `/api/studio-recordings/sync-now`, GET `/api/studio-recordings/sync-status`, POST `/api/studio-recordings/auto-sync-toggle` |

Parameter filter rekaman: `email`, `worker` (termasuk `unassigned`), `status`, `search`, `startDate`, `endDate`, `page`, `limit` (1–10000). Urutan tetap recorded_at DESC lalu createdAt DESC seperti sumber; parameter sort lama tidak pernah diterapkan oleh backend sumber. Parameter periode: `periodId`, `period`, atau `startDate` + `endDate`.

## Sinkronisasi Atlas

`AtlasParser` membaca payload `self.__next_f.push(...)` dan menelusuri props `recordings`, mengikuti algoritme Python asli. Sesi dibaca dari `ATLAS_COOKIE` atau `storage/app/private/payroll/atlas-session.json`. File sesi dan UUID peserta telah disalin secara privat; tidak dimasukkan ke kode atau Git.

Pemetaan: `userTaskId` → `record_id`; `taskTitle` → `task_title`; `recordedAt` → UTC `recorded_at` + label WITA; `durationSeconds` → detik dan menit; `processingStatus` → `processing_status`; `tileBillableHours`/`tileRejectedHours`/`tilePendingHours`/`tileDeferredHours` → menit ×60; `billable` → yes/no/null; `reviewedAt` → label review; `reconciliationStatus` → `reconciliation`.

Sync mempertahankan assignment, notes, status pembayaran manual, serta waktu assignment. Paging adaptif 38 hari/pending dipertahankan. Akun tanpa UUID dicoba dideteksi ulang dan dilaporkan sebagai warning bila belum ditemukan. Lock mencegah dua proses menulis state bersamaan. Cron memproses maksimal tiga halaman per tick agar tidak memerlukan proses permanen. Durasi satu siklus bergantung jumlah akun dan halaman.

File Excel eksternal `F:/#AI Ku Studio/...xlsx` tidak ditulis dari shared hosting. Ekspor rekaman dari halaman web tetap tersedia. File master Excel/SQLite scraper di luar proyek merupakan artefak alat lokal, bukan database operasional panel.

## Perbaikan yang disengaja

1. Login session, pembatasan percobaan login, CSRF, validasi input, batas upload, dan file privat.
2. Bonus/status tidak lagi diambil dari periode lain ketika slip periode aktif belum ada.
3. Periode invalid ditolak, bukan diam-diam memakai periode lain.
4. Bukti yang dipakai beberapa slip tidak dihapus otomatis saat satu slip diedit/dihapus. Retensi ini melindungi histori; pembersihan arsip dilakukan terpisah setelah pemeriksaan referensi.
5. Email tidak menyatakan sukses hanya karena request `no-cors` selesai. Transport log/array ditolak sebagai pengiriman nyata.
6. Konfigurasi akun, jadwal sinkronisasi, progress, dan profit bertahan setelah restart PHP.
7. PDF memiliki layout A4 khusus yang kompatibel Dompdf; isi dan hitungan dipertahankan, tampilan PDF tidak identik dengan template Chromium lama.

## Bukti verifikasi

- Snapshot: 16 kontributor, 16 slip, 16 status, 2296 rekaman, 0 shift setting; 38 akun studio dari JSON.
- Audit impor: 53224 field dan SHA-256 dari 243 file (73 bukti + 170 PDF asli) cocok, diverifikasi pada SQLite dan MySQL.
- Handler ringkasan Node.js asli dijalankan dalam sandbox dengan koneksi DB read-only. Laravel cocok pada field payroll per pekerja untuk periode Sep–Okt, Okt–Nov, dan training. Total live Sep–Okt: Rp26.902.959; training live: Rp852.008. Angka live bisa berbeda dari slip historis tersimpan karena hasil QA terkini.
- 17 tes / 112 assertion lulus: akses, halaman, boundary WITA/tahun, rumus, isolasi bonus periode, CRUD, status, filter, upload, Atlas parser/upsert/cron, setting, PDF, dan input invalid.
- Browser: tujuh halaman desktop HTTP 200, Alpine aktif, tanpa error console. Dashboard/rekaman/payroll pada lebar 390px tidak melampaui viewport.
- PDF hasil PHP dirender dan diperiksa visual; memuat rincian task lintas halaman, nominal, WITA, dan status pembayaran.
- Integrasi Atlas diuji dengan fixture HTTP. Probe live dari komputer ini gagal pada DNS `atlasdashboard.vtlabs.dev`, jadi keberhasilan sinkronisasi ke Atlas nyata belum dapat dinyatakan. Perlu DNS/koneksi dan sesi valid pada server tujuan.
- Email SMTP tidak dikirim selama migrasi. Isi konfigurasi hosting lalu lakukan pengiriman uji yang disetujui pengguna.

Laporan rinci privat berada di `storage/app/private/migration/`: snapshot, audit, parity, laporan browser, dan screenshot. Folder ini tidak disajikan web dan diabaikan Git.
