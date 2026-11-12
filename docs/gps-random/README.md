# Daftar awal titik dekat rumah untuk random GPS

Disiapkan 12 November 2026. Daftar berisi **20 titik yang diperiksa secara visual dari jarak jauh**: Banjarmasin 8, Banjarbaru 7, dan Martapura 5. Ini persiapan data, belum mengubah fitur aplikasi. Pilihan lokasi manual tetap menjadi alur yang tersedia.

## Arti valid dalam daftar ini

`reviewed_near_house` berarti sumber resmi memiliki foto rumah contoh dan koordinatnya tampak berada di/dekat bangunan hunian pada citra pembanding. Koordinat dipertahankan dari sumber, bukan digeser atau dibuat acak. Titik dapat berada di depan rumah/jalan kompleks, sehingga **bukan jaminan tepat di dalam atap atau kavling rumah tertentu**. Tidak ada survei lapangan, verifikasi penghuni, atau pengukuran jarak ke dinding rumah.

Foto sumber untuk entri terpilih memiliki timestamp pembaruan tahun 2020. Tanggal pengambilan citra pembanding tidak diperoleh. Tanggal review 2026-11-12 bukan tanggal pengambilan foto/citra; keberadaan rumah saat ini belum dijamin.

## Berkas

- `points.json`: 20 titik terpilih, sumber, tingkat verifikasi, aturan penggunaan, serta alasan 16 kandidat dikeluarkan.
- `points.csv`: 20 titik terpilih untuk review/import; koordinat WGS84/EPSG:4326.

## Cara pemeriksaan

1. Ambil 12 kandidat per wilayah dari pencarian lokasi publik SiKumbang: `kodeWilayah=6371`, `6372`, `630305`; total 36 kandidat. Angka keseluruhan saat pencarian masing-masing 138, 486, dan 208 kompleks, bukan jumlah rumah.
2. Ambil `foto.koordinatFotoContohGeo` dan `foto.fotoContoh` dari data publik halaman detail. Tidak memakai koordinat gerbang maupun kantor pemasaran. Periksa apakah fotonya benar menunjukkan rumah.
3. Uji point-in-polygon terhadap batas ADM2 geoBoundaries versi tetap `9469f09`, geometri simplified, tahun wilayah 2020: Kota Banjarmasin, Kota Banjar Baru, dan Kabupaten Banjar. Konflik label/batas disisihkan, tanpa menganggap salah satu sumber pasti paling mutakhir.
4. Periksa posisi titik pada Esri World Imagery: zoom 19 untuk Banjarmasin, zoom 17 untuk Banjarbaru/Martapura karena zoom 18/19 tidak menyediakan citra di kandidat tersebut. Foto rumah saja tidak cukup jika citra menunjukkan titik berada di lahan terbuka atau bukti hunian meragukan.
5. Pilih satu titik per kompleks yang lolos. Tiga kandidat tanpa koordinat rumah contoh, tujuh konflik batas, dan enam bukti visual tidak memadai dikeluarkan.

**Batas Martapura:** label kecamatan berasal dari SiKumbang; pemeriksaan poligon hanya sampai Kabupaten Banjar, bukan poligon Kecamatan Martapura. Martapura Barat dan Martapura Timur tidak sengaja ditambahkan. Batas 2020 ini merupakan penyaring awal, bukan batas hukum terbaru.

## Daftar titik

| Wilayah | Perumahan | Lintang | Bujur | Bukti sumber | Peta |
| --- | --- | --- | --- | --- | --- |
| Banjarmasin | Pondok Melati Albanjary | -3.339868056 | 114.640098556 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0210052020T010) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.339868056&mlon=114.640098556#map=19/-3.339868056/114.640098556) |
| Banjarmasin | SURYA GEMILANG | -3.291616389 | 114.579226944 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0410092020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.291616389&mlon=114.579226944#map=19/-3.291616389/114.579226944) |
| Banjarmasin | GRAHA MELATI INDAH | -3.336182639 | 114.633160222 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0210052020T003) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.336182639&mlon=114.633160222#map=19/-3.336182639/114.633160222) |
| Banjarmasin | Grand Batuah Mahatama (Sindang 2) | -3.345397972 | 114.604336000 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0110092020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.345397972&mlon=114.604336#map=19/-3.345397972/114.604336) |
| Banjarmasin | THE GREEN RAHAYU 2 | -3.338463083 | 114.639007556 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0210052020T008) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.338463083&mlon=114.639007556#map=19/-3.338463083/114.639007556) |
| Banjarmasin | Grand Batuah Mahatama | -3.347017972 | 114.605269000 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0110092020T005) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.347017972&mlon=114.605269#map=19/-3.347017972/114.605269) |
| Banjarmasin | Komplek Mahendra Sejahtera | -3.345597300 | 114.636119800 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0210052020T011) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.3455973&mlon=114.6361198#map=19/-3.3455973/114.6361198) |
| Banjarmasin | Aldi Citra Persada II | -3.350935100 | 114.560458197 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJM0110012020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.3509351&mlon=114.560458197#map=19/-3.3509351/114.560458197) |
| Banjarbaru | GRIYA IDAMAN TRIKORA | -3.459506300 | 114.766605200 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0210052020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.4595063&mlon=114.7666052#map=19/-3.4595063/114.7666052) |
| Banjarbaru | DWIKA MANDIRI MAKMUR | -3.415555556 | 114.737222222 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0610032020T006) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.415555556&mlon=114.737222222#map=19/-3.415555556/114.737222222) |
| Banjarbaru | PURI PERAMUAN INDAH | -3.456839200 | 114.734896400 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0610022020T004) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.4568392&mlon=114.7348964#map=19/-3.4568392/114.7348964) |
| Banjarbaru | Nabilla Asri | -3.453708861 | 114.706487056 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0610042021T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.453708861&mlon=114.706487056#map=19/-3.453708861/114.706487056) |
| Banjarbaru | Berkat Pesona Sei Sipai | -3.428139917 | 114.821655250 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0410012021T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.428139917&mlon=114.82165525#map=19/-3.428139917/114.82165525) |
| Banjarbaru | Bumi Hunian Permai 3 | -3.470777000 | 114.843188083 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0310042020T004) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.470777&mlon=114.843188083#map=19/-3.470777/114.843188083) |
| Banjarbaru | Suryatama Residence | -3.483962806 | 114.777910667 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/BJB0210052021T010) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.483962806&mlon=114.777910667#map=19/-3.483962806/114.777910667) |
| Martapura | PERUMAHAN GRIYA IHSAN | -3.416428833 | 114.870922722 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/MTP0520122022T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.416428833&mlon=114.870922722#map=19/-3.416428833/114.870922722) |
| Martapura | Grha Anjung Mahatama | -3.420120083 | 114.823649583 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/MTP0520342020T006) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.420120083&mlon=114.823649583#map=19/-3.420120083/114.823649583) |
| Martapura | RAYHAN REGENCY | -3.421513889 | 114.825972222 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/MTP0520092020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.421513889&mlon=114.825972222#map=19/-3.421513889/114.825972222) |
| Martapura | KOMPLEK AL FALAH | -3.414298306 | 114.869743333 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/MTP0520402020T001) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.414298306&mlon=114.869743333#map=19/-3.414298306/114.869743333) |
| Martapura | PERUMAHAN PESONA MANDIRI BLOK B | -3.438333333 | 114.884166667 | [SiKumbang](https://sikumbang.tapera.go.id/lokasi-perumahan/MTP0520122020T008) | [Lihat titik](https://www.openstreetmap.org/?mlat=-3.438333333&mlon=114.884166667#map=19/-3.438333333/114.884166667) |

## Ketika fitur random dibuat

- Filter `eligible_for_random=true` dan wilayah. Hanya gunakan koordinat yang tercantum; jangan menambah radius/jitter.
- Acak semua HP tanpa mengulang titik dalam satu pembagian. Kapasitas awal: 8/7/5 HP berbeda per wilayah, atau 20 jika lintas wilayah memang dipilih pengguna.
- Jika titik tidak cukup, tampilkan jumlah tersedia; jangan diam-diam mengulang atau membuat koordinat baru.
- Tampilkan pratinjau sebelum penerapan, dan pertahankan pemilihan/geser titik manual. Perubahan manual tidak otomatis masuk daftar terverifikasi.
- Daftar ini baru tahap awal dan belum mencakup seluruh kecamatan atau persebaran kota secara merata.

## Sumber dan atribusi

- [SiKumbang BP Tapera](https://sikumbang.tapera.go.id/): lokasi perumahan dan foto rumah contoh. Tautan bukti masing-masing disimpan di JSON/CSV. Nama/telepon/email pengembang dan identitas penghuni tidak disalin.
- [geoBoundaries IDN ADM2](https://www.geoboundaries.org/api/current/gbOpen/IDN/ADM2/): BPS / World Food Programme / OCHA ROAP; CC BY 3.0 IGO, representasi batas 2020. [Geometri versi yang diperiksa](https://media.githubusercontent.com/media/wmgeolab/geoBoundaries/9469f09/releaseData/gbOpen/IDN/ADM2/geoBoundaries-IDN-ADM2_simplified.geojson).
- [Esri World Imagery](https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer): Source: Esri, Vantor, Earthstar Geographics, and the GIS User Community. Citra/foto hanya dipakai untuk pemeriksaan; berkas gambar tidak didistribusikan ulang di repo.

Data ini disimpan di `docs/`, bukan `public/` atau `public_html`. Belum ada perubahan endpoint, UI GPS, atau data produksi.
