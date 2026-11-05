# AIKu Studio — aturan agen

## Hemat token
- Jawab singkat dalam bahasa Indonesia. Baca hanya file dan bagian yang relevan.
- Perubahan kecil: cek bagian terkait saja. Hindari audit atau pengujian berulang.
- Dokumentasi/menu statis: cukup review diff, format, dan render bila relevan;
  tidak perlu seluruh test suite, build aset, atau rebuild Graphify.
- Jalankan tes terarah jika perilaku berubah. Build hanya jika CSS/JS/aset atau
  konfigurasi build berubah. Cek tampilan hanya pada halaman/ukuran terdampak.
- Full suite untuk perubahan lintas modul, autentikasi/otorisasi, kalkulasi
  payroll, migrasi, atau risiko regresi luas. Jangan menjalankan PHPUnit bersamaan
  dengan build yang menghapus/menulis ulang manifest.
- Setelah pemeriksaan yang relevan lulus, commit dan langsung push. Ulangi
  pemeriksaan hanya jika ada perubahan relevan, kegagalan, atau masalah terbuka.

## Koordinasi dengan Antigravity
- Awal task: git pull origin main, lalu baca .agent/STATE.json.
- Jangan sentuh file terkunci oleh agen lain, termasuk file koordinasi. Tunggu
  lock dilepas; jangan menimpa atau membuat lock alternatif tanpa izin pengguna.
- Klaim file di STATE.json: active_agent, locked_files, task, updated_at ISO.
  Sertakan aset/graph yang benar-benar akan dihasilkan.
- Pertahankan perubahan agen lain. Stage hanya file task sendiri secara eksplisit;
  jangan gunakan git add . atau ikut commit pekerjaan orang lain.
- Akhir task: tulis 2–3 baris serah terima di atas .agent/HANDOVER.md tanpa
  menghapus riwayat, set active_agent IDLE dan locked_files [], lalu commit dan
  langsung git push origin main. Tes/build mengikuti dampak perubahan di atas.

## Graphify dan data
- Gunakan Graphify untuk arsitektur, relasi lintas file, investigasi kompleks,
  atau permintaan eksplisit. Edit kecil pada file yang sudah diketahui boleh
  langsung memakai pencarian/bacaan terarah.
- Mulai query dengan budget 800–1200 token. Jangan memuat seluruh graph JSON.
  Verifikasi temuan pada source; baca docs/AI_CONTEXT.md hanya bila relevan.
- Refresh graph dengan python scripts/graphify_project.py build untuk perubahan
  struktur, relasi, migrasi, atau permintaan eksplisit. Kosmetik/dokumentasi
  tidak perlu rebuild. Cek kesegaran sebelum mengandalkan graph yang tersimpan.
- Graphify hanya alat pengembangan. Jangan membuka graph/skill/script developer
  lewat public_html. Pertahankan .graphifyignore.
- Jangan indeks .env, kredensial, payroll/GPS pribadi, dump atau log produksi.
  Ekspor skema memakai scripts/graphify-schema.php (SQLite :memory:). Tes memakai
  database terisolasi; jangan import/restore data produksi untuk pengecekan.
