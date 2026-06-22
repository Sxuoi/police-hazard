# Mini Command Center (Police Hazard & Fitur 110)

## Gambaran Umum Proyek
Mini Command Center (Police Hazard & Fitur 110) adalah sebuah sistem terpadu berbasis web dan *mobile web* yang dibangun khusus untuk operasional institusi kepolisian. Sistem ini bertujuan untuk mendigitalkan, mengotomatisasi, dan menyatukan pemantauan presensi personel di lapangan (Police Hazard) serta sistem komando respons cepat untuk penanganan kedaruratan masyarakat (Fitur 110).

Sistem ini memastikan validasi kehadiran yang ketat (menggunakan teknologi Geofence dan Anti-Spoofing GPS) dan menghasilkan bukti digital yang tidak dapat dimanipulasi (Immutable) melalui pembubuhan *watermark* otomatis pada foto pelaporan dokumentasi lapangan.

## Fitur Utama

### 1. Modul Police Hazard (Presensi & Penugasan)
- **Manajemen Operasi & Shift**: Pembuatan operasi operasional dan penentuan jadwal shift untuk personel kepolisian di wilayah komando masing-masing (*Saker*).
- **Geofencing PostGIS**: Menentukan batas radius koordinat patroli atau penjagaan (Police Hazard) secara visual di atas peta.
- **Check-In Berbasis GPS**: Personel wajib melakukan absensi (*check-in*) di dalam radius lokasi dengan verifikasi titik lokasi yang akurat (*real-time*).
- **Deteksi Anti-Spoofing**: Algoritma sistem akan menolak presensi jika terdeteksi penggunaan *Mock Location* (Fake GPS) pada perangkat petugas.
- **Data Immutable**: Rekam data kehadiran (Attendance) memiliki proteksi *database-level rules* dan bersifat kebal ubah (tidak dapat dihapus atau diedit setelah terekam).

### 2. Modul Fitur 110 (Respons Kedaruratan)
- **Manajemen Tiket 110**: Operator Command Center mencatat laporan insiden darurat masyarakat, membuat tiket, dan menugaskannya ke armada (*Unit*) terdekat.
- **Bypass Token Link (Tanpa Login)**: Petugas Pamapta di lapangan menerima tautan khusus (*URL Token*) melalui WhatsApp untuk langsung melaporkan kedatangan, titik koordinat, dan laporan lengkap tanpa perlu repot melakukan tahapan *login* klasik.
- **Auto-Watermarking Bukti Foto**: Foto dokumentasi lapangan yang beresolusi besar dikompresi di sisi *client* dan diproses secara latar belakang (*asynchronous Queue*) di *server* untuk ditempelkan *watermark* berlapis (Berisi Logo, Nama, NRP, Waktu, Koordinat, Alamat Reverse Geocode, dan Nomor Tiket).
- **Peta Pantauan Live (Leaflet.js)**: Menyajikan peta komando interaktif yang memvisualisasikan posisi penanganan kejadian darurat secara *live* di layar Command Center.

### 3. Modul Sistem Keamanan & Tenancy
- **Multi-Tenancy (SakerScope)**: Pembatasan visibilitas data agar terisolasi per wilayah operasional komando masing-masing (Polda/Polres/Polsek).
- **God Admin & Heatmap Global**: Akses Super Admin (Propam) tingkat nasional untuk melakukan pengawasan terpadu (*Global Audit*) dan melihat sebaran Peta Panas (*Heatmap*) tanpa batasan sekat kewilayahan.
- **Full Audit Trails**: Seluruh aktivitas pembaruan data, hingga persetujuan manipulasi izin tercatat secara otomatis dan permanen dalam riwayat `audit_logs`.

---

## Kebutuhan Sistem (System Requirements)

Proyek ini dibangun menggunakan arsitektur modern berbasis PHP dan Database Relasional Geospatial.

- **Sistem Operasi**: Linux (Ubuntu/Debian) atau Windows (menggunakan WSL 2 / Laragon).
- **Web Server**: Nginx atau Apache.
- **PHP**: Versi **8.3** atau yang lebih baru.
- **Database**: PostgreSQL (Versi 16+) yang wajib di-install dengan ekstensi **PostGIS (Versi 3.4+)**.
- **Composer**: Untuk instalasi dependensi *backend* PHP.
- **Node.js & NPM**: Untuk kompilasi (*compiling*) aset Frontend berbasis Vite dan TailwindCSS.
- **Supervisor** (Server): Untuk menjaga *Laravel Queue Workers* tetap berjalan menangani antrean proses seperti *watermarking* gambar.

---

## Panduan Instalasi Lokal (Local Development)

1. **Clone repositori proyek ini** ke dalam direktori lokal (Misal: folder `www` pada Laragon).
2. **Install Dependensi PHP & Node.js**:
   ```bash
   composer install
   npm install
   ```
3. **Konfigurasi Environment**:
   Duplikat file konfigurasi `.env.example` menjadi `.env`.
   ```bash
   cp .env.example .env
   ```
   Lalu pastikan konfigurasi database sudah menggunakan Driver **PostgreSQL** dan sesuai dengan kredensial database lokal Anda:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=nama_database_anda
   DB_USERNAME=user_db_anda
   DB_PASSWORD=password_db_anda

   # Pastikan mengatur QUEUE_CONNECTION (database/redis)
   QUEUE_CONNECTION=database
   ```
4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```
5. **Jalankan Migrasi Database & Seeder**:
   *(Perhatian: Ekstensi PostGIS di PostgreSQL Anda harus sudah diaktifkan sebelum menjalankan perintah ini!)*
   ```bash
   php artisan migrate --seed
   ```
6. **Hubungkan Symlink Storage**:
   ```bash
   php artisan storage:link
   ```
7. **Jalankan Queue Worker (Penting untuk Fitur 110)**:
   Mengingat pemrosesan *watermark* foto menggunakan *background jobs*, jalankan antrean pada tab terminal terpisah:
   ```bash
   php artisan queue:listen
   ```
8. **Jalankan Aplikasi**:
   Jalankan Vite untuk *hot-reloading* aset UI:
   ```bash
   npm run dev
   ```
   Dan jalankan server PHP:
   ```bash
   php artisan serve
   ```
   Aplikasi siap diakses pada web browser.

---

## Panduan Deploy ke Server (Production)

Untuk merilis aplikasi ini ke dalam Server / VPS (contoh berbasis Ubuntu):

1. **Persiapan Server**: Pastikan PHP 8.3, Nginx/Apache, PostgreSQL, dan PostGIS telah terinstal.
2. **Konfigurasi Virtual Host**: Atur Document Root (Nginx/Apache) agar menunjuk ke *path* folder `/public` pada proyek ini.
3. **Penyesuaian Environment**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-kepolisian.go.id
   QUEUE_CONNECTION=redis # Sangat disarankan memakai Redis di Production
   ```
4. **Optimasi Laravel**:
   Jalankan rentetan perintah optimasi berikut di *production*:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. **Build Aset Frontend**:
   ```bash
   npm run build
   ```
6. **Supervisor untuk Queue Workers (Sangat Kritikal)**:
   Agar sistem tidak macet saat mengirimkan foto lapangan 110, buat konfigurasi Supervisor untuk menjaga ketersediaan Queue Worker.
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
   Lalu restart supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   ```
7. **File Permissions**: Pastikan pengguna *web server* (`www-data` atau `nginx`) memiliki hak tulis pada folder `storage/` dan `bootstrap/cache/`.
