-- Database schema for Sistem Absensi PKL PTPN IV Regional 3

CREATE DATABASE IF NOT EXISTS absensi_pkl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_pkl;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    identifier_no VARCHAR(50) NOT NULL, -- NIS or NIM
    user_type ENUM('siswa', 'mahasiswa', 'admin') NOT NULL DEFAULT 'siswa',
    institution VARCHAR(100) DEFAULT NULL, -- Asal Sekolah / Kampus
    major VARCHAR(100) DEFAULT NULL, -- Jurusan / Program Studi
    division VARCHAR(100) DEFAULT NULL, -- Unit Kerja / Divisi PTPN IV
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    period_start DATE DEFAULT NULL,
    period_end DATE DEFAULT NULL,
    role ENUM('admin', 'pkl') NOT NULL DEFAULT 'pkl',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Office / Location Settings Table
CREATE TABLE IF NOT EXISTS settings_location (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(100) NOT NULL DEFAULT 'Kantor Direksi PTPN IV Regional 3',
    address TEXT DEFAULT NULL,
    latitude DECIMAL(10, 8) NOT NULL DEFAULT 0.528383, -- Koordinat Pekanbaru / PTPN IV Reg 3
    longitude DECIMAL(11, 8) NOT NULL DEFAULT 101.447781,
    radius_meters INT NOT NULL DEFAULT 150, -- Radius batas absensi (meter)
    work_start_time TIME NOT NULL DEFAULT '07:30:00',
    work_late_tolerance TIME NOT NULL DEFAULT '08:00:00',
    work_end_time TIME NOT NULL DEFAULT '16:30:00',
    work_days VARCHAR(50) DEFAULT '1,2,3,4,5', -- Senin - Jumat
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Attendances Table
CREATE TABLE IF NOT EXISTS attendances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME DEFAULT NULL,
    lat_in DECIMAL(10, 8) DEFAULT NULL,
    long_in DECIMAL(11, 8) DEFAULT NULL,
    distance_in_meters INT DEFAULT NULL,
    photo_in VARCHAR(255) DEFAULT NULL,
    notes_in TEXT DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    lat_out DECIMAL(10, 8) DEFAULT NULL,
    long_out DECIMAL(11, 8) DEFAULT NULL,
    distance_out_meters INT DEFAULT NULL,
    photo_out VARCHAR(255) DEFAULT NULL,
    notes_out TEXT DEFAULT NULL,
    status ENUM('hadir', 'terlambat', 'pulang_cepat', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Leaves & Permits Table
CREATE TABLE IF NOT EXISTS leaves_permits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type ENUM('sakit', 'izin_akademik', 'izin_pribadi', 'dispensasi', 'lainnya') NOT NULL DEFAULT 'izin_pribadi',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    proof_file VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Seed initial settings
INSERT INTO settings_location (id, office_name, address, latitude, longitude, radius_meters, work_start_time, work_late_tolerance, work_end_time)
VALUES (1, 'Kantor Direksi PTPN IV Regional 3', 'Jl. Rambutan No. 43, Pekanbaru, Riau', 0.52838300, 101.44778100, 200, '07:30:00', '08:00:00', '16:30:00')
ON DUPLICATE KEY UPDATE office_name=VALUES(office_name);

-- 6. Seed Default Admin & Sample PKL
-- Password admin: admin123
-- Password siswa/mahasiswa: pkl123
INSERT INTO users (id, username, password, full_name, identifier_no, user_type, institution, major, division, phone, email, period_start, period_end, role, is_active)
VALUES 
(1, 'admin', '$2y$10$w4rUe91wX97mK6H20KzPduUqH1tIuB2.V8h8sCvhUdfQfG6P44wzC', 'Administrator SDM PTPN IV', 'ADM-001', 'admin', 'PTPN IV Regional 3', 'Biro SDM & Umum', 'Bagian SDM & Hubungan Industri', '081234567890', 'sdm.ptpn4reg3@ptpn4.co.id', '2026-01-01', '2026-12-31', 'admin', 1),
(2, 'mahasiswa1', '$2y$10$w4rUe91wX97mK6H20KzPduUqH1tIuB2.V8h8sCvhUdfQfG6P44wzC', 'Rizky Pratama', '2201082001', 'mahasiswa', 'Universitas Riau', 'Teknik Informatika', 'Bagian TI & Transformasi Digital', '081298765432', 'rizky.pkl@student.unri.ac.id', '2026-08-01', '2026-11-30', 'pkl', 1),
(3, 'siswa1', '$2y$10$w4rUe91wX97mK6H20KzPduUqH1tIuB2.V8h8sCvhUdfQfG6P44wzC', 'Siti Nurhaliza', '20241055', 'siswa', 'SMK Negeri 1 Pekanbaru', 'Akuntansi & Keuangan Lembaga', 'Bagian Keuangan & Akuntansi', '081377889900', 'siti.smk1@gmail.com', '2026-08-01', '2026-10-31', 'pkl', 1)
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);
