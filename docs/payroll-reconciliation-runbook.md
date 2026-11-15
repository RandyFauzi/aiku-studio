# Rekonsiliasi slip pada server

## Pemeriksaan sebelum perubahan

1. Verifikasi commit dan hash Git blob server, bukan hash working tree Windows
   yang dapat berbeda karena CRLF. Tolak perubahan tracked yang belum ditinjau.
2. Cocokkan snapshot slip yang telah dibayar dengan dokumen sumber pengguna.
3. Bandingkan ID rekaman satu per satu untuk memisahkan pending yang selesai,
   revisi QA lain, dan perubahan penugasan. Jangan menganggap seluruh selisih
   approved hanya berasal dari pending.
4. Periksa slip periode lama yang bertumpang tindih dengan periode transisi.
   Label berbeda belum tentu berarti pekerjaan berbeda.

## Mutasi yang diizinkan

- Backup privat row slip lengkap, status pembayaran, serta bukti perbandingan.
  Simpan di storage privat di luar document root, bukan repo atau graph.
- Gunakan transaksi dan worker lock, kemudian baca ulang status dan angka.
- Pilih slip dengan evidence dan carry-over yang terverifikasi. Versi nol
  carry-over yang memakai evidence sama menjadi arsip, bukan dijumlahkan.
- Slip beda rentang hanya boleh diarsipkan lewat pemeliharaan terotorisasi bila
  seluruh evidence identik, tidak ada pekerjaan tambahan di bagian rentang yang
  lebih panjang, dan keduanya belum final. Selain itu hentikan untuk pemeriksaan.
- Arsipkan dengan superseded_by_id dan superseded_at. Pemeliharaan otomatis
  memakai actor null pada kolom user dan catatan otorisasi di audit privat;
  jangan mengatribusikan tindakan ke ID admin yang tidak melakukan tindakan.
- Jangan mengubah nominal, evidence, file PDF lama, waktu pembuatan, atau snapshot
  pembayaran periode sebelumnya. Jangan menjalankan sync-drafts saat deploy.

## Verifikasi sesudah perubahan

Jumlah dokumen tetap; nilai snapshot periode dibayar dan slip utama tetap.
Hanya satu slip aktif untuk pekerjaan/periode yang direkonsiliasi. Saldo pada
rekap harus merupakan saldo tambahan belum dialokasikan; carry-over yang sudah
masuk slip ditampilkan terpisah. Form edit dapat mengecualikan reservasinya
sendiri untuk memeriksa rincian klaim, tetapi rekap tidak boleh melakukannya.

Ringkasan rejected pada PDF/web memakai rejected_mins evidence. Pending tidak
boleh dihitung dua kali sebagai rejected. Perbaikan tampilan tidak mengubah THP
atau dokumen PDF lama yang sudah diterbitkan.

Endpoint pemeliharaan sementara harus memakai token header, kedaluwarsa singkat,
request POST, dan menghapus dirinya setelah eksekusi. Simpan backup/audit dan hasil
rinci secara privat; laporan repo tidak memuat identitas atau angka payroll.
