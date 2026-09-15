# SIMOLI - Sistem Informasi Monitoring & Absensi PKL

<p align="center">
  <img src="assets/icons/icon-192x192.png" alt="SIMOLI Logo" width="120" height="120" style="border-radius: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);" />
</p>

<p align="center">
  <strong>Sistem Informasi Monitoring & Presensi Online Peserta PKL / Magang Berbasis Progressive Web App (PWA) & GPS Geofencing</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%20%7C%208.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA" />
  <img src="https://img.shields.io/badge/Design-Glassmorphism%20CSS3-2962FF?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License" />
</p>

---

## 📖 Tentang Aplikasi

**SIMOLI** (*Sistem Informasi Monitoring & Absensi PKL*) adalah aplikasi presensi online modern berbasis **Progressive Web App (PWA)** yang dirancang untuk memonitoring kehadiran, kedisiplinan, dan aktivitas peserta magang/PKL (Siswa SMK & Mahasiswa Perguruan Tinggi) di lingkungan perusahaan maupun instansi (seperti **PT Perkebunan Nusantara IV Regional 3**).

Aplikasi ini mengintegrasikan **GPS Geofencing radius waktu nyata (Real-Time)** dan **Live Camera Selfie** untuk mencegah kecurangan absensi (*anti-fake attendance*), dilengkapi dengan manajemen izin/sakit, profil mandiri, kartu identitas digital (*Digital ID Card*), dan ekspor laporan rekapitulasi.

---

## ✨ Fitur Unggulan

### 📱 1. Progressive Web App (PWA) & Mobile-First
- **Instalasi Mandiri**: Dapat diinstall langsung di perangkat **Android (WebAPK / Chrome Prompt)** dan **Apple iOS (Safari "Add to Home Screen" Guide)** tanpa perlu melalui App Store / Play Store.
- **Offline & Cache Service Worker**: Navigasi cepat dengan caching aset statis dan service worker (`sw.js`).
- **Dynamic Manifest**: Dilengkapi `manifest.php` & `manifest.webmanifest` dengan maskable icons dan splash screen terintegrasi.
- **PWA Update Toast**: Otomatis memberi notifikasi pembaharuan versi saat ada pembaruan kode aplikasi.

### 📍 2. Real-Time GPS Geofencing & Leaflet Map
- **Geolokasi Presisi**: Menggunakan Geolocation API HTML5 dan formula matematis **Haversine** untuk menghitung jarak akurat antara koordinat pengguna dan kantor pusat/lokasi magang.
- **Pengaturan Radius Fleksibel**: Admin dapat mengatur titik koordinat kantor dan batas radius presensi (dalam meter) langsung melalui peta interaktif **Leaflet.js** dengan draggable marker.

### 📸 3. Presensi Kamera Live (Anti-Fraud Selfie)
- Mengambil foto selfie secara langsung (*live web camera snapshot*) saat melakukan **Absen Masuk** dan **Absen Pulang**.
- Validasi status lokasi: Tombol absen otomatis dinonaktifkan jika pengguna berada di luar radius kantor yang diizinkan.

### 📝 4. Manajemen Izin & Sakit
- Pengajuan izin / sakit mandiri oleh peserta PKL disertai lampiran bukti surat keterangan dokter atau dokumen pendukung.
- Dasbor persetujuan (*Approve / Reject / Pending*) dengan notifikasi badge status untuk Administrator SDM / Pembimbing.

### 🪪 5. Kartu Identitas Digital & Profil Mandiri
- **Digital ID Card**: Kartu tanda pengenal magang digital interaktif dengan QR Code, status instansi/sekolah, dan foto profil.
- **Manajemen Profil**: Siswa/Mahasiswa dapat memperbarui foto avatar mandiri (*live preview*) dan mengganti kata sandi akun kapan saja.

### 🎨 6. Multi-Tema Ultra Glossy (5 Pilihan Warna)
- Tampilan modern dengan estetika **Glassmorphism**, efek backdrop-filter blur, micro-animations, dan tata letak responsif.
- 5 Pilihan Tema Warna yang tersimpan di *Local Storage*:
  - 🌿 **Emerald Green** (Hijau Kebun PTPN)
  - 🌊 **Corporate Blue** (Biru Korporat)
  - 🌸 **Fuchsia Pink** (Pink Energik)
  - 🌅 **Sunset Orange** (Jingga Senja)
  - 🔮 **Deep Violet** (Ungu Elegan)

### 📊 7. Rekapitulasi & Ekspor Laporan
- Ringkasan statistik kehadiran harian dan bulanan (Hadir, Terlambat, Izin, Sakit, Alpa).
- Filter data berdasarkan rentang tanggal dan nama peserta.
- Ekspor data laporan presensi ke format **CSV / Excel** dalam satu klik.

---

## 🏗️ Struktur Direktori

```text
absensiPKL/
├── .htaccess                 # Konfigurasi Apache rewrite, security headers, & MIME types
├── .gitignore                # Aturan ignore file git (uploads, OS caches, env)
├── index.php                 # Single Page Interface aplikasi utama
├── manifest.php              # Dynamic PWA Web App Manifest
├── manifest.webmanifest      # Static PWA Web App Manifest
├── sw.js                     # Service Worker untuk caching & offline support
├── install.sql               # Database schema & initial seed data
├── README.md                 # Dokumentasi proyek
├── api/                      # RESTful Backend API Endpoints
│   ├── auth.php              # Login, Logout, dan validasi sesi
│   ├── attendance.php        # Clock-in, Clock-out, Geofencing, Riwayat presensi
│   ├── leave.php             # Pengajuan dan approval izin/sakit
│   ├── report.php            # Rekapitulasi statistik dan ekspor CSV
│   ├── settings.php          # Konfigurasi koordinat kantor & radius geofence
│   └── users.php             # CRUD peserta magang, avatar upload, ganti password
├── assets/                   # Aset statis antarmuka
│   ├── css/
│   │   └── style.css         # Styling Glassmorphism modern & tema warna dinamis
│   ├── js/
│   │   ├── app.js            # Logika aplikasi, navigasi tab, camera, geolokasi
│   │   └── pwa.js            # Registrasi Service Worker, install prompts (iOS/Android)
│   └── icons/                # Ikon PWA berbagai ukuran (72px s.d. 512px)
├── config/
│   └── db.php                # Koneksi PDO Database & Helper Functions
└── uploads/                  # Direktori penyimpanan media (avatars, selfies, proofs)
    ├── avatars/              # Foto profil pengguna
    ├── proofs/               # Lampiran surat keterangan sakit/izin
    └── selfies/              # Foto selfie absensi masuk & pulang
```

---

## 🛠️ Persyaratan Sistem & Tech Stack

| Komponen | Spesifikasi / Teknologi |
|---|---|
| **Web Server** | Apache / Nginx (Mendukung HTTPS untuk izin Kamera & GPS) |
| **Bahasa Pemrograman** | PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 |
| **Ekstensi PHP** | `pdo_mysql`, `gd`, `json`, `mbstring`, `fileinfo` |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ |
| **Frontend** | HTML5 Semantic, Modern Vanilla CSS3, Vanilla ES6+ JavaScript |
| **Libraries / CDN** | Leaflet.js (Peta), Lucide Icons, Canvas Confetti |

> **Catatan Penting HTTPS**: Fitur **Geolokasi GPS** dan **Akses Kamera Live** pada browser modern (Chrome, Safari, Edge) mewajibkan protokol aman **HTTPS** atau `localhost`.

---

## 🚀 Panduan Instalasi & Setup

### 1. Clone Repository
```bash
git clone https://github.com/mrhdyt404/absensiPKL.git
cd absensiPKL
```

### 2. Impor Skema Database
Buat database baru di MySQL/MariaDB, kemudian impor file `install.sql`:
```bash
mysql -u root -p -e "CREATE DATABASE absensi_pkl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p absensi_pkl < install.sql
```

### 3. Konfigurasi Koneksi Database
Buka file `config/db.php` dan sesuaikan kredensial database Anda:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'absensi_pkl');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

### 4. Atur Hak Akses Direktori Uploads
Pastikan direktori `uploads/` dapat ditulisi oleh web server (*write permission*):
```bash
chmod -R 775 uploads/
# atau jika di server Linux (www-data / apache):
# chown -R www-data:www-data uploads/
```

### 5. Jalankan Aplikasi
Buka web browser dan akses aplikasi melalui URL server Anda:
```text
http://localhost/absensiPKL/
```
*(Gunakan HTTPS jika di-deploy ke server produksi / domain publik)*

---

## 🔑 Akun Demo Bawaan

| Role | Username | Password | Deskripsi |
|---|---|---|---|
| 👑 **Admin SDM / Pembimbing** | `admin` | `admin123` | Akses penuh dashboard, approval izin, manajemen user, setting radius |
| 🎓 **Mahasiswa Magang** | `mahasiswa1` | `pkl123` | Presensi mahasiswa, kartu digital ID, pengajuan izin, riwayat |
| 🎒 **Siswa SMK Magang** | `siswa1` | `pkl123` | Presensi siswa SMK, kartu digital ID, pengajuan izin, riwayat |

---

## 📡 Ringkasan API Endpoints

| Endpoint | Metode | Deskripsi |
|---|---|---|
| `/api/auth.php?action=login` | `POST` | Autentikasi user & pembuatan sesi |
| `/api/auth.php?action=logout` | `POST` | Mengakhiri sesi pengguna |
| `/api/auth.php?action=check` | `GET` | Memeriksa status sesi login aktif |
| `/api/attendance.php?action=today` | `GET` | Mengambil status absensi hari ini |
| `/api/attendance.php?action=clock_in` | `POST` | Melakukan presensi masuk (Selfie + Koordinat) |
| `/api/attendance.php?action=clock_out` | `POST` | Melakukan presensi pulang (Selfie + Koordinat) |
| `/api/attendance.php?action=history` | `GET` | Mengambil riwayat presensi pengguna |
| `/api/leave.php?action=submit` | `POST` | Mengajukan izin / sakit dengan lampiran file |
| `/api/leave.php?action=list` | `GET` | Daftar permohonan izin (User / Admin) |
| `/api/leave.php?action=update_status`| `POST` | Menyetujui atau menolak izin (Admin) |
| `/api/settings.php?action=get` | `GET` | Mengambil titik koordinat kantor & radius |
| `/api/settings.php?action=update` | `POST` | Memperbarui titik koordinat kantor & radius (Admin) |
| `/api/users.php?action=update_profile`| `POST` | Memperbarui foto profil avatar & data profil |
| `/api/users.php?action=change_password`| `POST` | Mengganti kata sandi akun |
| `/api/report.php?action=summary` | `GET` | Ringkasan statistik kehadiran |
| `/api/report.php?action=export_csv` | `GET` | Mengunduh berkas laporan CSV kehadiran |

---

## 📄 Lisensi

Proyek ini dirilis di bawah lisensi [MIT License](LICENSE).

---

<p align="center">
  Dibuat dengan ❤️ untuk kemudahan monitoring & digitalisasi program magang / PKL.
</p>
