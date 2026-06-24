# Mini Command Center (Police Hazard & Fitur 110)

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-2D3441?style=for-the-badge&logo=alpinedotjs&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

Mini Command Center adalah sebuah sistem terpadu berbasis web dan *mobile web* yang dibangun khusus untuk operasional institusi kepolisian. Sistem ini diciptakan untuk mendigitalkan, mengotomatisasi, dan menyatukan pemantauan presensi personel di lapangan serta sistem komando respons cepat untuk penanganan kedaruratan masyarakat.

Sistem ini terbagi menjadi dua ranah fungsional yang beroperasi secara independen namun terpusat di dalam satu *dashboard*: **Police Hazard** dan **Fitur 110**.

---

## 📑 Daftar Isi
1. [Modul 1: Police Hazard (Presensi & Penugasan)](#1-modul-police-hazard-presensi--penugasan)
2. [Modul 2: Fitur 110 (Respons Kedaruratan)](#2-modul-fitur-110-respons-kedaruratan)
3. [Keamanan & Multi-Tenancy](#3-keamanan--multi-tenancy)
4. [Kebutuhan Sistem (System Requirements)](#kebutuhan-sistem-system-requirements)
5. [Panduan Instalasi (Development)](#panduan-instalasi-development)
6. [Panduan Deploy (Production)](#panduan-deploy-production)

---

## 1. Modul Police Hazard (Presensi & Penugasan)

Modul Police Hazard didesain untuk memastikan kedisiplinan dan kehadiran fisik personel di titik-titik rawan secara presisi. Modul ini menjamin validasi kehadiran yang sangat ketat dan menghasilkan bukti digital yang tidak dapat dimanipulasi (Immutable).

### ✨ Fitur Lengkap Police Hazard
- **Manajemen Operasi & Shift**: Pembuatan operasi keamanan berskala wilayah, pembagian zona (Zone), dan penentuan jadwal shift untuk personel kepolisian di bawah Satuan Kerja (*Saker*) masing-masing.
- **Visual Geofencing**: Penentuan batas radius operasional (Geofence) secara presisi dengan menggambar / memosisikan koordinat langsung di atas peta komando.
- **Check-In Berbasis GPS Real-time**: Petugas wajib membuka aplikasi via telepon pintar dan melakukan *check-in* di dalam area radius lokasi. Jarak petugas ke titik pusat dihitung secara *real-time*.
- **Anti-Spoofing & Fake GPS Detection**: Algoritma berlapis mendeteksi jika petugas menggunakan *Mock Location* (Fake GPS). Presensi akan otomatis ditolak (*auto-reject*) atau ditandai mencurigakan (*flagged*) jika terdeteksi anomali.
- **Log Kehadiran Immutable**: Data presensi dikunci menggunakan aturan di level *database*. Setelah data masuk, data tersebut bersifat permanen dan tidak dapat diedit maupun dihapus oleh siapapun.
- **Live Rekapitulasi**: *Dashboard* analitik dan pencarian mutakhir untuk melihat *progress* persentase kehadiran per wilayah secara langsung.

### 🛠 Teknologi Utama (Police Hazard)
- **PostgreSQL & PostGIS**: Mesin utama yang menangani data spasial. Menggunakan fungsi `ST_DWithin` dan tipe data `GEOGRAPHY` untuk kalkulasi jarak radius melengkung bumi secara hiper-akurat, jauh lebih baik dari kalkulasi matematis standar.
- **Alpine.js & TailwindCSS**: Membangun antarmuka *Mobile Web App* untuk gawai petugas yang sangat responsif, memiliki *state management* yang reaktif (seperti *native app*), tanpa *overhead* framework berat.
- **PostgreSQL Database Triggers**: Keamanan tingkat database yang mengunci baris data (Row-Level Lock) pada tabel *attendances* untuk mencegah *Data Tampering*.

---

## 2. Modul Fitur 110 (Respons Kedaruratan)

Modul ini adalah pusat penanganan kejadian darurat secara *live* yang memfasilitasi komunikasi antara Operator Command Center dan Unit/Petugas (Pamapta) di lokasi insiden.

### ✨ Fitur Lengkap Fitur 110
- **Manajemen Tiket Kedaruratan**: Pencatatan pelaporan dari masyarakat secara terstruktur, pendistribusian tugas kepada armada terdekat, dan pembaruan status penyelesaian (Open, In Progress, Resolved).
- **Bypass Token Link (Login-less Action)**: Operator mengirimkan *link* unik via WhatsApp kepada petugas di lapangan. *Link* ini mengandung Token rahasia dengan batas kadaluarsa (TTL) yang memungkinkan petugas melaporkan foto dan titik koordinat tanpa perlu melalui proses *login* yang memakan waktu di saat darurat.
- **Auto-Watermarking Bukti Foto**: Setiap foto dokumentasi yang dikirim oleh petugas di lapangan akan secara otomatis dicetak *watermark* digital berlapis yang berisi:
  - Logo Institusi
  - Nama & NRP Petugas
  - Nomor Tiket 110
  - Koordinat Akurat
  - Alamat (Hasil Reverse Geocoding)
  - Waktu (Terikat pada *Timezone* lokasi setempat)
- **EXIF Metadata Stripping**: Demi keamanan, *metadata* asli gambar dikosongkan selama kompresi untuk menghindari kebocoran data gawai pelapor.
- **Peta Pantauan Live (Leaflet.js)**: Menyajikan peta interaktif yang menampilkan penempatan Unit secara *live* dan insiden 110 aktif di wilayah tersebut.

### 🛠 Teknologi Utama (Fitur 110)
- **Intervention Image (v3/v4) & GD Library**: Pustaka pemrosesan gambar mutakhir yang menangani *downsizing*, *compression*, dan *text rendering* untuk *watermark* foto.
- **Laravel Queues & Supervisor**: Mengingat manipulasi gambar mengkonsumsi *resource* tinggi, pemrosesan ini dilimpahkan kepada *Background Jobs* secara asinkron. Hal ini menjamin gawai petugas tidak mengalami *freeze* saat mengirim gambar resolusi tinggi.
- **AWS S3 / Cloud Storage**: Arsitektur penyimpanan modular. Dapat menyimpan foto ke sistem lokal (Local Disk) atau secara transparan diunggah ke AWS S3 Bucket dengan skema URL terkunci batas waktu (*Presigned URLs*) demi keamanan ekstra.

---

## 3. Keamanan & Multi-Tenancy

Selain dua modul fungsional di atas, aplikasi ini dibekali dengan modul arsitektur *backend* yang tangguh:
- **Multi-Tenancy (SakerScope)**: Menerapkan Global Scope di Laravel untuk membatasi visibilitas data. Admin Polsek hanya dapat melihat data Polseknya, sementara Admin Polres membawahi Polsek. Hal ini menjamin isolasi data secara ketat.
- **God Admin (Propam/Mabes)**: Hak akses tertinggi tanpa batas kewilayahan yang mampu memantau Peta Panas (*Heatmap*) kehadiran di seluruh penjuru negeri secara terpusat.
- **Rate Limiting & Brute Force Protection**: Membatasi laju unggahan foto dan permintaan *bypass token* untuk mencegah serangan *Denial of Service* (DoS).
- **Full Audit Trails**: Setiap perubahan sensitif, manipulasi penugasan, atau pencabutan status dicatat ke dalam `audit_logs` secara otomatis yang melacak *User ID*, alamat IP, dan perubahan atribut asli (*diff*).

---

## Kebutuhan Sistem (System Requirements)

- **Sistem Operasi**: Linux (Ubuntu/Debian) atau Windows (menggunakan WSL 2 / Laragon).
- **Web Server**: Nginx atau Apache.
- **PHP**: Versi **8.3** atau yang lebih baru.
- **Database**: PostgreSQL (Versi 16+) terpasang ekstensi **PostGIS (Versi 3.4+)**. *(Sistem tidak akan bekerja di MySQL/MariaDB)*.
- **Package Manager**: Composer (Backend PHP) dan Node.js/NPM (Frontend UI).
- **Layanan Antrean (Queue)**: Redis sangat direkomendasikan untuk produksi.
- **Lainnya**: Supervisor (untuk menjaga Queue Worker tetap hidup di latar belakang).

---

## Panduan Instalasi (Development)

Berikut adalah langkah-langkah untuk menyiapkan aplikasi di lingkungan lokal Anda.

### 1. Kloning & Instalasi Dependensi
Clone repositori ke dalam folder lokal (Misal: `C:\laragon\www\Police-Hazard`).
```bash
composer install
npm install
```

### 2. Konfigurasi Database & Environment
Duplikat file contoh konfigurasi dan buat *Application Key* yang baru.
```bash
cp .env.example .env
php artisan key:generate
```
Sesuaikan isi `.env` Anda. Pastikan driver yang digunakan adalah `pgsql` dan cocok dengan kredensial PostgreSQL Anda:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=police_hazard
DB_USERNAME=postgres
DB_PASSWORD=secret

QUEUE_CONNECTION=database
PH_PHOTO_DISK=local
```
*(**Penting:** Mengatur `PH_PHOTO_DISK=local` akan menginstruksikan sistem untuk menyimpan gambar di *hard drive* PC Anda daripada memaksa unggahan S3 yang membutuhkan kredensial AWS.)*

### 3. Eksekusi Database Migrasi & Seeder
Pastikan Anda sudah mengaktifkan `CREATE EXTENSION postgis;` pada database Anda sebelum tahap ini.
```bash
php artisan migrate:fresh --seed
```

### 4. Konfigurasi Penyimpanan (Symlink)
Menghubungkan folder publik dengan direktori penyimpanan agar foto dapat ditampilkan oleh browser:
```bash
php artisan storage:link
```

### 5. Menjalankan Aplikasi (Local vs Mobile Testing)

**Opsi A: Pengembangan Normal di Laptop**
Gunakan opsi ini jika Anda mengedit kode CSS/JS (akan ada efek *Hot Reloading*).
1. Buka terminal 1: `npm run dev`
2. Buka terminal 2: `php artisan serve`
3. Buka terminal 3: `php artisan queue:listen`

**Opsi B: Pengujian via Handphone & Ngrok (Sangat Disarankan)**
Jika Anda menggunakan **Ngrok** untuk mengetes kamera HP ke *localhost* Anda, *Hot Reloading* dari Vite akan merusak tampilan CSS di HP Anda. Oleh karena itu, gunakan perintah spesial ini:
```bash
composer dev:mobile
```
*Perintah `dev:mobile` di atas akan otomatis mem-*build* CSS statis, lalu langsung menyalakan `php artisan serve` dan `php artisan queue:listen` di terminal yang sama. Cukup biarkan perintah ini menyala lalu buka Ngrok.*

---

## Panduan Deploy (Production)

Untuk merilis aplikasi ini ke dalam Server / VPS Linux (contoh berbasis Ubuntu):

1. **Persiapan**: Pastikan PHP 8.3, Nginx, PostgreSQL, dan PostGIS telah terinstal. Atur Document Root Nginx agar menunjuk ke `/public`.
2. **Ubah Environment**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-kepolisian.go.id
   QUEUE_CONNECTION=redis
   PH_PHOTO_DISK=s3  # (Jika menggunakan AWS S3 Cloud)
   ```
3. **Optimasi Tingkat Produksi**:
   Jalankan perintah ini agar Laravel berjalan jauh lebih cepat tanpa overhead:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```
4. **Konfigurasi Supervisor (Kritikal!)**:
   Agar sistem tidak macet saat antrean foto menumpuk, jadikan antrean latar belakang sebagai layanan abadi menggunakan Supervisor.
   Buat file di `/etc/supervisor/conf.d/police-hazard-worker.conf`:
   ```ini
   [program:police-hazard-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/project/storage/logs/worker.log
   ```
   Lalu terapkan konfigurasinya:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start police-hazard-worker:*
   ```
5. **Izin Folder**: Pastikan pengguna *web server* memiliki hak tulis:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```
