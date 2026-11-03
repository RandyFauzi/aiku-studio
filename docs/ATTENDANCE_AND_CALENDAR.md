# Kalender shift dan absensi

## Jadwal

Administrator membuka `/shifts.html`. Tab Kalender memilih satu tanggal dan menyesuaikan sesi/alat setiap nama. Tanggal tanpa jadwal khusus mengikuti pola rutin serta hari libur. Penyimpanan tanggal membuat snapshot pengaturan untuk tanggal itu saja dalam tabel `shift_days`; perubahan pola rutin berikutnya tidak mengubah snapshot yang sudah disimpan.

Tombol Libur menonaktifkan sesi satu orang, Liburkan semua mengosongkan seluruh sesi pada tanggal terpilih, dan Kembali ke pola rutin menghapus snapshot tanggal tersebut. Perubahan tidak mempengaruhi tanggal lain atau slip payroll. Simulasi bulanan menjumlahkan jam dari setiap tanggal, menggunakan tarif simulasi saat ini.

Endpoint admin: GET `/api/shifts/days?month=YYYY-MM`; PUT/DELETE `/api/shifts/days/YYYY-MM-DD`. Penulisan memakai `expected_revision` untuk menolak perubahan dari versi lama. Bentrok alat/sesi divalidasi di server memakai `ShiftConfiguration`, sama dengan pola rutin.

## Akun kontributor

Email pada Teams & Kontributor menjadi email login. Setiap kontributor harus memakai email unik. Kontributor tanpa email tidak mendapat akun. Akun baru menggunakan password awal yang diminta pengguna: `password`. Mengedit profil/email tidak mereset password akun yang sudah ada. Menghapus kontributor atau mengosongkan email menonaktifkan akses; riwayat absensi dipertahankan.

`contributors:sync-logins` menyinkronkan kontributor lama tanpa mengubah password yang sudah ada. Konflik dengan email akun lain, termasuk administrator, ditolak. Migration memberi role admin hanya kepada akun yang telah ada sebelum fitur ini; akun kontributor baru selalu ber-role contributor. Akun tidak aktif ditolak juga pada sesi yang masih terbuka.

Kontributor masuk melalui `/login`, lalu diarahkan ke `/me`. Portal hanya memuat jadwal dan absensi orang yang sedang login. Seluruh halaman/API payroll, setting, berkas privat, data kontributor lain, dan rekap admin dilindungi middleware administrator.

## Absensi masuk

Kontributor menekan Masuk dan mengizinkan lokasi browser. Geolocation diambil saat tombol ditekan dengan high accuracy, tanpa cache lokasi dan tanpa pelacakan terus-menerus. Server menyimpan koordinat, akurasi meter, waktu pengambilan GPS, tanggal kerja WITA, serta timestamp masuk dari server dalam UTC untuk ditampilkan kembali sebagai WITA.

Tidak ada checkout. Satu akun hanya mendapat satu catatan per tanggal WITA, dengan unique constraint dan row lock untuk klik bersamaan. Request berulang mengembalikan catatan yang sama. ID pengguna, nama, dan tanggal kerja berasal dari sesi/server; tidak dipercaya dari payload klien. GPS yang tidak valid atau lebih dari lima menit ditolak. Ini pencatatan lokasi browser, bukan verifikasi anti-spoofing atau pembatasan radius kantor.

Admin melihat rekap pada `/attendance.html`, memilih tanggal dan mencari nama. Detail menampilkan jam masuk, koordinat, akurasi, dan tautan peta. Lokasi tidak dikirim otomatis ke layanan geocoding pihak ketiga.

Endpoint pribadi: GET `/api/me/attendance?month=YYYY-MM`, POST `/api/me/attendance`. Endpoint admin: GET `/api/attendance?date=YYYY-MM-DD`.

## Deployment dan verifikasi

Migration menambahkan `shift_days`, role/link kontributor/status aktif pada `users`, serta tabel `attendances`. Data payroll, primary administrator, password utama, dan pola shift lama dipertahankan. Jalankan migration sebelum menyinkronkan login. Deployment memakai maintenance singkat dan menyimpan backup privat konfigurasi serta akun sebelum perubahan.

26 tes / 185 assertion memeriksa isolasi tanggal, revision, validasi shift, login default, otorisasi, perubahan email, penonaktifan akun, idempotensi absensi, batas tanggal WITA, GPS, dan kerahasiaan data. Browser lokal menguji penyimpanan tanggal serta check-in dengan GPS simulasi pada akun sementara yang kemudian dihapus. Verifikasi hosting menguji login kontributor dan pembatasan akses tanpa mencatat absensi palsu di produksi.
