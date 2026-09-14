# SIMOLI - Sistem Informasi Monitoring & Absensi PKL PTPN IV Regional 3

Aplikasi Progressive Web App (PWA) modern, responsif, dan berbasis lokasi (GPS Geofencing + Radius) untuk absensi presensi siswa magang (SMK) dan mahasiswa magang (Universitas) di lingkungan **PT Perkebunan Nusantara IV (PTPN IV) Regional 3**.

---

## ✨ Fitur Utama

1. **Progressive Web App (PWA)**:
   - Dapat di-install langsung di perangkat **Android** dan **Apple iOS (Safari Add to Home Screen Guidance)**.
   - Dilengkapi icon resolusi tinggi dan manifest terstandarisasi.
2. **GPS Geofencing & Interactive Map**:
   - Deteksi lokasi waktu nyata (Geolokasi HTML5 & formula Haversine).
   - Validasi radius presensi kantor yang dapat disesuaikan oleh Administrator melalui peta interaktif **Leaflet.js**.
3. **Presensi Selfie Kamera Live**:
   - Pengambilan foto langsung melalui kamera saat melakukan Absen Masuk dan Absen Pulang.
   - Mencegah kecurangan presensi.
4. **Manajemen Pengajuan Izin / Sakit**:
   - Pengajuan izin/sakit mandiri oleh peserta PKL disertai lampiran surat dokter/keterangan.
   - Panel persetujuan (Approve / Reject) untuk Admin SDM / Pembimbing.
5. **Manajemen Akun & Profil Mandiri**:
   - Siswa & Mahasiswa dapat mengubah kata sandi login dan mengunggah foto profil mandiri (*live avatar preview*).
   - Kartu Identitas Digital Magang (*Digital ID Card*).
6. **Multi-Tema Warna Dinamis (Ultra Glossy)**:
   - Mendukung 5 pilihan tema warna (*Hijau Kebun, Biru PTPN, Pink Fuchsia, Sunset Oren, Ungu Violet*) yang tersimpan di *Local Storage*.
   - Desain modern Glassmorphism, Micro-Animations, dan responsif ramah smartphone (*Mobile-First*).
7. **Laporan & Rekapitulasi Presensi**:
   - Ringkasan harian dan bulanan dengan filter tanggal.
   - Ekspor laporan kehadiran ke format **CSV / Excel**.

---

## 🛠️ Persyaratan Sistem & Tech Stack

- **Server Web**: Apache / Nginx
- **PHP**: PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 dengan ekstensi `pdo_mysql`, `gd`, `json`, `mbstring`
- **Database**: MariaDB / MySQL 5.7+
- **Frontend**: Vanilla HTML5, Modern Vanilla CSS3 (Glassmorphism), Vanilla JavaScript, Lucide Icons, Leaflet.js

---

## 🚀 Panduan Instalasi

1. **Clone Repository**:
   ```bash
   git clone https://github.com/mrhdyt404/simoli.git
   cd simoli
   ```

2. **Impor Database**:
   - Buat database baru di MySQL/MariaDB (misal: `absensi_pkl`).
   - Impor file skema [install.sql](install.sql):
     ```bash
     mysql -u root -p absensi_pkl < install.sql
     ```

3. **Konfigurasi Database**:
   - Sesuaikan kredensial koneksi database pada [config/database.php](config/database.php):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_db_user');
     define('DB_PASS', 'your_db_password');
     define('DB_NAME', 'absensi_pkl');
     ```

4. **Izin Folder Uploads**:
   - Pastikan direktori `uploads/` memiliki izin tulis oleh web server:
     ```bash
     chmod -R 777 uploads/
     ```

5. **Akses Aplikasi**:
   - Buka browser dan arahkan ke domain/subdirektori web server Anda (misal: `http://localhost/absensiPKL/` atau `https://your-domain.com/`).

---

## 🔑 Akun Bawaan (Default Demo)

| Peran (Role) | Username | Password | Keterangan |
|---|---|---|---|
| **Admin SDM** | `admin` | `admin123` | Administrator & Pembimbing |
| **Mahasiswa PKL** | `mahasiswa1` | `pkl123` | Mahasiswa Universitas |
| **Siswa PKL** | `siswa1` | `pkl123` | Siswa SMK |

---

## 🏢 Hak Cipta & Pengembang

Dikembangkan untuk unit kerja dan program magang **PTPN IV Regional 3**.
