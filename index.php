<?php
// index.php - Main Single Page Application for Sistem Absensi PKL PTPN IV Regional 3
require_once __DIR__ . '/config/db.php';

$initTheme = $_COOKIE['absensi_pkl_theme'] ?? 'hijau';
$initCustomColor = $_COOKIE['absensi_pkl_custom_color'] ?? '';
$themeMap = [
    'hijau' => ['theme' => '#0b6e4f', 'bg' => '#064e3b'],
    'biru'  => ['theme' => '#1d4ed8', 'bg' => '#1e3a8a'],
    'pink'  => ['theme' => '#be185d', 'bg' => '#831843'],
    'oren'  => ['theme' => '#c2410c', 'bg' => '#7c2d12'],
    'ungu'  => ['theme' => '#7e22ce', 'bg' => '#581c87'],
    'teal'  => ['theme' => '#0f766e', 'bg' => '#134e4a'],
    'merah' => ['theme' => '#b91c1c', 'bg' => '#7f1d1d'],
];
$serverThemeColor = '#0b6e4f';
if ($initTheme === 'custom' && !empty($initCustomColor)) {
    $serverThemeColor = '#' . ltrim($initCustomColor, '#');
} elseif (isset($themeMap[$initTheme])) {
    $serverThemeColor = $themeMap[$initTheme]['theme'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>ABSENSI PKL TEKPOL | PTPN IV Regional 3</title>
    
    <!-- PWA & Apple Meta Tags -->
    <link rel="manifest" href="./manifest.php" id="pwa-manifest-link">
    <meta name="theme-color" content="<?php echo htmlspecialchars($serverThemeColor); ?>" id="meta-theme-color">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi PKL">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="64x64" href="assets/icons/favicon.png">
    <link rel="shortcut icon" href="assets/icons/favicon.png">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Script Dependencies -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body <?php if ($initTheme !== 'hijau' && $initTheme !== 'custom') echo 'data-theme="' . htmlspecialchars($initTheme) . '"'; ?>>

    <!-- Toast Notifications Container -->
    <div id="toast-container"></div>

    <!-- PWA Installation Banner for Android/Desktop -->
    <div id="pwa-install-banner" class="glass hidden" style="position: sticky; top: 0; z-index: 1100; border-radius: 0; padding: 0.6rem 1.25rem; display: flex; align-items: center; justify-content: space-between; background: var(--theme-dark); color: #ffffff;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <img src="assets/icons/logo.png" style="width: 36px; height: 36px; border-radius: 8px; background: #ffffff; padding: 2px;" alt="PTPN 4 Logo">
            <div>
                <strong style="font-size: 0.85rem; display: block;">Install Aplikasi Absensi PKL</strong>
                <span style="font-size: 0.75rem; opacity: 0.85;">Akses lebih cepat langsung dari layar utama</span>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="installPWA()" class="btn btn-gold btn-sm">Install Sekarang</button>
            <button onclick="document.getElementById('pwa-install-banner').classList.add('hidden')" class="btn btn-secondary btn-sm" style="background: transparent; border-color: rgba(255,255,255,0.3); color: #fff;">Tutup</button>
        </div>
    </div>

    <!-- Top Navigation Bar with Glossy Theme Switcher -->
    <header class="app-navbar" id="app-navbar">
        <div class="navbar-brand">
            <img src="assets/icons/logo.png" alt="PTPN IV Logo" class="brand-logo-img">
            <div class="brand-info">
                <h1>PTPN IV REGIONAL 3</h1>
                <span id="nav-subtitle">Sistem Absensi Digital PKL</span>
            </div>
        </div>

        <div class="navbar-actions">
            <!-- Theme Color Selector Trigger Button (Icon) -->
            <button id="btn-theme-picker" type="button" class="btn-navbar-action" onclick="openThemePickerModal()" title="Pilih & Kustomisasi Tema Warna">
                <i data-lucide="palette" style="width: 18px; height: 18px; display: inline-block;"></i>
                <span class="hide-mobile" style="font-size: 0.75rem;">Tema</span>
            </button>

            <!-- Install PWA Button -->
            <button id="btn-pwa-install-header" onclick="showPWAInstructions()" class="btn btn-sm btn-gold hidden">
                <i data-lucide="download" style="width: 16px; height: 16px;"></i> <span class="hide-mobile">Install</span>
            </button>

            <!-- Top Logout Button -->
            <button id="btn-logout-top" type="button" class="btn-navbar-action btn-logout-nav hidden" onclick="logoutUser()" title="Keluar / Logout">
                <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                <span class="hide-mobile" style="font-size: 0.75rem;">Keluar</span>
            </button>
        </div>
    </header>

    <!-- Main Container Content Area -->
    <main class="main-container">

        <!-- ================= VIEW 1: LOGIN ================= -->
        <section id="view-login" class="view-section">
            <div style="max-width: 440px; margin: 2rem auto;">
                <div class="card" style="box-shadow: var(--shadow-xl); border: 1px solid rgba(11, 110, 79, 0.15); border-radius: var(--radius-xl); overflow: hidden; padding: 0;">
                    <div class="gradient-ptpn" style="padding: 2.2rem 1.5rem; text-align: center; color: white;">
                        <img src="assets/icons/logo.png" style="width: 80px; height: 80px; border-radius: 20px; background: #ffffff; padding: 6px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); margin-bottom: 0.8rem; border: 2px solid rgba(255,255,255,0.4);" alt="Logo PTPN IV">
                        <h2 style="color: white; font-size: 1.4rem; font-weight: 800; margin-bottom: 0.2rem;">PORTAL ABSENSI PKL</h2>
                        <p style="font-size: 0.85rem; opacity: 0.9;">PT Perkebunan Nusantara IV Regional 3</p>
                    </div>

                    <div style="padding: 1.75rem;">
                        <form id="form-login" onsubmit="handleLogin(event)">
                            <div class="form-group">
                                <label class="form-label" for="login-username">Username / NIS / NIM / Email</label>
                                <div style="position: relative;">
                                    <input type="text" id="login-username" class="form-control" placeholder="Masukkan username atau NIS/NIM" required autofocus style="padding-left: 2.4rem;">
                                    <i data-lucide="user" style="position: absolute; left: 0.8rem; top: 0.8rem; width: 18px; height: 18px; color: var(--text-muted);"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="login-password">Kata Sandi</label>
                                <div style="position: relative;">
                                    <input type="password" id="login-password" class="form-control" placeholder="Masukkan kata sandi" required style="padding-left: 2.4rem;">
                                    <i data-lucide="lock" style="position: absolute; left: 0.8rem; top: 0.8rem; width: 18px; height: 18px; color: var(--text-muted);"></i>
                                </div>
                            </div>

                            <button type="submit" id="btn-submit-login" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.25rem;">
                                <i data-lucide="log-in"></i> Masuk Sekarang
                            </button>
                        </form>

                        <div style="text-align: center; margin-top: 1.5rem;">
                            <button type="button" onclick="showPWAInstructions()" class="btn btn-secondary btn-sm" style="background: none; border: none; color: var(--theme-primary); font-weight: 600;">
                                <i data-lucide="smartphone"></i> Panduan Pasang PWA di HP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= VIEW 2: SISWA / MAHASISWA PKL ================= -->
        <section id="view-pkl" class="view-section hidden">
            <!-- Student Profile Header Banner (Ultra Glossy & Mobile-First) -->
            <div class="card glass-dark student-profile-card">
                <div class="profile-card-top">
                    <div class="profile-avatar-wrapper" onclick="openProfileSettingsModal()" title="Ubah Foto Profil & Password">
                        <div id="pkl-avatar-container" class="profile-avatar-box">
                            <img id="pkl-avatar-img" class="hidden" src="" alt="Foto Profil">
                            <span id="pkl-avatar-text">PKL</span>
                        </div>
                        <div class="profile-avatar-badge" title="Ganti Foto">
                            <i data-lucide="camera"></i>
                        </div>
                    </div>
                    <div class="profile-info-wrapper">
                        <div class="profile-name-row">
                            <h2 id="pkl-name" class="profile-name">-</h2>
                            <span id="pkl-type-badge" class="badge badge-gold">MAHASISWA</span>
                        </div>
                        <div class="profile-meta-line" id="pkl-institution">-</div>
                        <div class="profile-meta-sub" id="pkl-division">
                            <i data-lucide="map-pin"></i>
                            <span id="pkl-division-text">Divisi</span>
                        </div>
                    </div>
                    <div class="profile-card-actions">
                        <button type="button" onclick="openProfileSettingsModal()" class="btn-profile-action" title="Edit Profil & Password">
                            <i data-lucide="user-cog"></i>
                            <span class="hide-mobile">Profil</span>
                        </button>
                    </div>
                </div>
                <div class="profile-clock-bar">
                    <div class="clock-date-group">
                        <i data-lucide="calendar"></i>
                        <span id="live-date-text">Senin, 14 September 2026</span>
                    </div>
                    <div class="clock-time-group">
                        <span class="live-dot-indicator" title="Waktu Nyata"></span>
                        <span id="live-digital-clock">00:00:00 WIB</span>
                    </div>
                </div>
            </div>

            <!-- GPS Status Radar Card -->
            <div class="card" style="margin-bottom: 1rem; border: 1.5px solid rgba(16, 185, 129, 0.25); padding: 0.95rem;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1;">
                        <div class="radar-pulse">
                            <i data-lucide="crosshair" style="color: var(--theme-secondary); width: 20px; height: 20px;"></i>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                <span style="font-size: 0.85rem; font-weight: 700; color: #0f172a;">Radius GPS Kantor</span>
                                <span id="gps-status-badge" class="badge badge-gray">Memeriksa GPS...</span>
                            </div>
                            <p id="gps-distance-desc" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.15rem; line-height: 1.3;">
                                Menghubungkan ke satelit GPS...
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.4rem; flex-shrink: 0;">
                        <button onclick="refreshCurrentGPS()" class="btn btn-secondary btn-sm" id="btn-refresh-gps" title="Perbarui GPS">
                            <i data-lucide="refresh-cw"></i> <span class="hide-mobile">Perbarui Posisi</span>
                        </button>
                        <button onclick="toggleMapModal(true)" class="btn btn-secondary btn-sm" title="Buka Peta">
                            <i data-lucide="map"></i> <span class="hide-mobile">Lihat Peta</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Attendance Action Cards (Clock In & Clock Out) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.85rem; margin-bottom: 1rem;">
                
                <!-- CARD ABSEN MASUK -->
                <div class="card" id="card-absen-masuk" style="border-top: 4px solid var(--theme-primary); padding: 1.15rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-size: 1.05rem; color: var(--theme-primary);"><i data-lucide="log-in" style="display: inline; vertical-align: middle; width: 18px;"></i> Absen Masuk</h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted);" id="label-jam-masuk">Jadwal: 07:30 - 08:00 WIB</span>
                        </div>
                        <span id="badge-masuk-status" class="badge badge-gray">Belum Absen</span>
                    </div>

                    <div id="masuk-detail-box" class="hidden" style="background: rgba(248, 250, 252, 0.9); border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 0.85rem; border: 1px solid var(--border-subtle);">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <img id="masuk-photo-preview" src="" style="width: 46px; height: 46px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1);" alt="Selfie Masuk">
                            <div>
                                <strong id="masuk-time-text" style="font-size: 1.05rem; color: var(--theme-primary); display: block;">-</strong>
                                <span id="masuk-distance-text" style="font-size: 0.72rem; color: var(--text-muted);">-</span>
                            </div>
                        </div>
                    </div>

                    <button id="btn-trigger-clockin" onclick="openAttendanceCameraModal('clock_in')" class="btn btn-primary btn-block btn-lg">
                        <i data-lucide="camera"></i> Absen Masuk Sekarang
                    </button>
                </div>

                <!-- CARD ABSEN PULANG -->
                <div class="card" id="card-absen-pulang" style="border-top: 4px solid var(--accent-gold); padding: 1.15rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-size: 1.05rem; color: var(--accent-gold-dark);"><i data-lucide="log-out" style="display: inline; vertical-align: middle; width: 18px;"></i> Absen Pulang</h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted);" id="label-jam-pulang">Jadwal Mulai: 16:30 WIB</span>
                        </div>
                        <span id="badge-pulang-status" class="badge badge-gray">Belum Absen</span>
                    </div>

                    <div id="pulang-detail-box" class="hidden" style="background: rgba(248, 250, 252, 0.9); border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 0.85rem; border: 1px solid var(--border-subtle);">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <img id="pulang-photo-preview" src="" style="width: 46px; height: 46px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1);" alt="Selfie Pulang">
                            <div>
                                <strong id="pulang-time-text" style="font-size: 1.05rem; color: var(--accent-gold-dark); display: block;">-</strong>
                                <span id="pulang-distance-text" style="font-size: 0.72rem; color: var(--text-muted);">-</span>
                            </div>
                        </div>
                    </div>

                    <button id="btn-trigger-clockout" onclick="openAttendanceCameraModal('clock_out')" class="btn btn-gold btn-block btn-lg" disabled>
                        <i data-lucide="camera"></i> Absen Pulang Sekarang
                    </button>
                </div>
            </div>

            <!-- Quick Action Grid (2x2 on Mobile) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem; margin-bottom: 1.25rem;">
                <div class="card" style="cursor: pointer; padding: 0.9rem;" onclick="openLeaveModal()">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="file-text" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="min-width: 0;">
                            <strong style="font-size: 0.85rem; display: block; line-height: 1.2;">Ajukan Izin</strong>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Sakit & Dispensasi</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="cursor: pointer; padding: 0.9rem;" onclick="openHistoryTab()">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #f3e8ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="calendar" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="min-width: 0;">
                            <strong style="font-size: 0.85rem; display: block; line-height: 1.2;">Riwayat</strong>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Log Bulanan</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="cursor: pointer; padding: 0.9rem;" onclick="openDigitalCardModal()">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="credit-card" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="min-width: 0;">
                            <strong style="font-size: 0.85rem; display: block; line-height: 1.2;">Kartu ID</strong>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Tanda Pengenal</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="cursor: pointer; padding: 0.9rem;" onclick="showPWAInstructions()">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="smartphone" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="min-width: 0;">
                            <strong style="font-size: 0.85rem; display: block; line-height: 1.2;">Pasang PWA</strong>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Android & iOS</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PKL Attendance History Table Preview -->
            <div class="card" id="section-pkl-history">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h3 style="font-size: 1rem;"><i data-lucide="clock" style="display: inline; width: 18px;"></i> Log Presensi Bulan Ini</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <select id="select-history-month" onchange="loadPKLHistory()" class="form-control" style="width: auto; padding: 0.35rem 0.65rem; font-size: 0.82rem;">
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <select id="select-history-year" onchange="loadPKLHistory()" class="form-control" style="width: auto; padding: 0.35rem 0.65rem; font-size: 0.82rem;">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Jarak GPS</th>
                                <th>Status</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-pkl-history">
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat data riwayat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ================= VIEW 3: ADMIN SDM & PEMBIMBING ================= -->
        <section id="view-admin" class="view-section hidden">
            <!-- Admin Top Navigation Tabs (Hidden on mobile) -->
            <div class="admin-top-nav-tabs">
                <button class="btn btn-sm btn-primary tab-admin-btn" onclick="switchAdminTab('monitoring')" id="tab-btn-monitoring">
                    <i data-lucide="layout-dashboard"></i> Monitoring Harian
                </button>
                <button class="btn btn-sm btn-secondary tab-admin-btn" onclick="switchAdminTab('users')" id="tab-btn-users">
                    <i data-lucide="users"></i> Data Akun PKL
                </button>
                <button class="btn btn-sm btn-secondary tab-admin-btn" onclick="switchAdminTab('location')" id="tab-btn-location">
                    <i data-lucide="map-pin"></i> Lokasi & Radius GPS
                </button>
                <button class="btn btn-sm btn-secondary tab-admin-btn" onclick="switchAdminTab('leaves')" id="tab-btn-leaves">
                    <i data-lucide="file-check"></i> Verifikasi Izin <span id="badge-admin-leave-count" class="badge badge-danger hidden" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">0</span>
                </button>
                <button class="btn btn-sm btn-secondary tab-admin-btn" onclick="switchAdminTab('report')" id="tab-btn-report">
                    <i data-lucide="file-spreadsheet"></i> Rekap & Laporan
                </button>
            </div>

            <!-- ADMIN TAB 1: MONITORING HARIAN -->
            <div id="admin-tab-monitoring" class="admin-tab-content">
                <!-- Statistics Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="card" style="border-left: 4px solid var(--theme-primary);">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Total Siswa/Mahasiswa</span>
                        <h3 id="stat-total-pkl" style="font-size: 1.75rem; color: var(--theme-primary); margin-top: 0.2rem;">0</h3>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Peserta PKL aktif</span>
                    </div>
                    <div class="card" style="border-left: 4px solid #10b981;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Hadir Tepat Waktu</span>
                        <h3 id="stat-hadir-ontime" style="font-size: 1.75rem; color: #10b981; margin-top: 0.2rem;">0</h3>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Presensi hari ini</span>
                    </div>
                    <div class="card" style="border-left: 4px solid #f59e0b;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Terlambat</span>
                        <h3 id="stat-terlambat" style="font-size: 1.75rem; color: #f59e0b; margin-top: 0.2rem;">0</h3>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Lewat toleransi</span>
                    </div>
                    <div class="card" style="border-left: 4px solid #0284c7;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Izin / Sakit</span>
                        <h3 id="stat-izin-sakit" style="font-size: 1.75rem; color: #0284c7; margin-top: 0.2rem;">0</h3>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Dengan konfirmasi</span>
                    </div>
                    <div class="card" style="border-left: 4px solid #ef4444;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Belum Presensi</span>
                        <h3 id="stat-belum-absen" style="font-size: 1.75rem; color: #ef4444; margin-top: 0.2rem;">0</h3>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Belum ada catatan</span>
                    </div>
                </div>

                <!-- Filter & Attendance Realtime Table -->
                <div class="card">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
                        <h3 style="font-size: 1.05rem;"><i data-lucide="list-check" style="display: inline; width: 18px;"></i> Rekap Presensi Harian</h3>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                            <input type="date" id="admin-filter-date" class="form-control" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.85rem;" onchange="loadAdminMonitoring()">
                            <select id="admin-filter-type" class="form-control" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.85rem;" onchange="loadAdminMonitoring()">
                                <option value="">Semua Kategori</option>
                                <option value="siswa">Siswa SMK</option>
                                <option value="mahasiswa">Mahasiswa</option>
                            </select>
                            <input type="text" id="admin-filter-search" placeholder="Cari nama / NIS / NIM..." class="form-control" style="width: 200px; padding: 0.4rem 0.75rem; font-size: 0.85rem;" onkeyup="loadAdminMonitoring()">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Nama Peserta PKL</th>
                                    <th>Institusi / Divisi</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Jarak GPS</th>
                                    <th>Status</th>
                                    <th>Bukti Foto</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-admin-monitoring">
                                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat monitoring...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ADMIN TAB 2: MANAJEMEN AKUN PKL -->
            <div id="admin-tab-users" class="admin-tab-content hidden">
                <div class="card">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <h3 style="font-size: 1.05rem;"><i data-lucide="user-plus" style="display: inline; width: 18px;"></i> Manajemen Akun Siswa & Mahasiswa PKL</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Buat dan kelola akun magang peserta PTPN IV Regional 3</p>
                        </div>
                        <button onclick="openUserModal('create')" class="btn btn-primary">
                            <i data-lucide="plus-circle"></i> Tambah Akun PKL
                        </button>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                        <input type="text" id="user-mgmt-search" placeholder="Cari nama, NIS/NIM, sekolah/kampus..." class="form-control" style="max-width: 320px; font-size: 0.85rem;" onkeyup="loadAdminUsers()">
                        <select id="user-mgmt-filter-type" class="form-control" style="width: auto; font-size: 0.85rem;" onchange="loadAdminUsers()">
                            <option value="">Semua Jenjang</option>
                            <option value="siswa">Siswa SMK</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Nama & NIS/NIM</th>
                                    <th>Jenjang</th>
                                    <th>Sekolah / Kampus</th>
                                    <th>Divisi Penempatan</th>
                                    <th>Periode Magang</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-admin-users">
                                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat data akun...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ADMIN TAB 3: PENGATURAN LOKASI & RADIUS GPS -->
            <div id="admin-tab-location" class="admin-tab-content hidden">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
                    <!-- Settings Form -->
                    <div class="card">
                        <h3 style="font-size: 1.05rem; margin-bottom: 0.3rem;"><i data-lucide="settings" style="display: inline; width: 18px;"></i> Parameter Lokasi & Radius GPS</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">Tentukan titik koordinat kantor dan batas radius absensi</p>

                        <form id="form-location-settings" onsubmit="saveLocationSettings(event)">
                            <div class="form-group">
                                <label class="form-label">Nama Kantor / Unit Kerja</label>
                                <input type="text" id="set-office-name" class="form-control" required value="Kantor Direksi PTPN IV Regional 3">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Alamat Kantor</label>
                                <input type="text" id="set-address" class="form-control" value="Jl. Rambutan No. 43, Pekanbaru, Riau">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <div class="form-group">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" step="any" id="set-latitude" class="form-control" required oninput="updateAdminMapCircle()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" step="any" id="set-longitude" class="form-control" required oninput="updateAdminMapCircle()">
                                </div>
                            </div>

                            <div class="form-group" style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <label class="form-label" style="margin: 0;">Radius Batas Absensi (Meter)</label>
                                    <span id="label-radius-val" class="badge badge-success" style="font-size: 0.9rem; font-weight: 800;">200 m</span>
                                </div>
                                <input type="range" id="set-radius-range" min="20" max="2000" step="10" value="200" class="form-control" style="padding: 0; cursor: pointer;" oninput="onRadiusSliderChange(this.value)">
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.4rem;">Siswa/Mahasiswa hanya dapat presensi jika berada di dalam radius ini.</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-top: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 0.78rem;">Jam Masuk</label>
                                    <input type="time" id="set-work-start" class="form-control" style="font-size: 0.85rem;" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 0.78rem;">Batas Toleransi</label>
                                    <input type="time" id="set-work-late" class="form-control" style="font-size: 0.85rem;" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 0.78rem;">Jam Pulang</label>
                                    <input type="time" id="set-work-end" class="form-control" style="font-size: 0.85rem;" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                                <i data-lucide="save"></i> Simpan Parameter Lokasi
                            </button>
                        </form>
                    </div>

                    <!-- Interactive Map Picker -->
                    <div class="card" style="display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <h3 style="font-size: 1rem;"><i data-lucide="map" style="display: inline; width: 18px;"></i> Peta Interaktif & Radius Geofence</h3>
                            <button type="button" onclick="setCurrentLocationAsOffice()" class="btn btn-secondary btn-sm">
                                <i data-lucide="locate-fixed"></i> Gunakan Lokasi Saya
                            </button>
                        </div>
                        <div id="admin-map" style="height: 420px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                            💡 <i>Tips: Klik atau seret pin marker pada peta untuk menentukan titik koordinat kantor secara presisi.</i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ADMIN TAB 4: VERIFIKASI IZIN / SAKIT -->
            <div id="admin-tab-leaves" class="admin-tab-content hidden">
                <div class="card">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <h3 style="font-size: 1.05rem;"><i data-lucide="file-check" style="display: inline; width: 18px;"></i> Verifikasi Pengajuan Izin & Sakit</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Tinjau surat dokter / bukti permohonan dispensasi siswa & mahasiswa</p>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <select id="admin-leave-filter-status" class="form-control" style="width: auto; font-size: 0.85rem;" onchange="loadAdminLeaves()">
                                <option value="">Semua Status</option>
                                <option value="pending" selected>Menunggu Verifikasi (Pending)</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Nama Peserta</th>
                                    <th>Jenis Izin</th>
                                    <th>Rentang Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Bukti / Surat</th>
                                    <th>Status</th>
                                    <th>Aksi Review</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-admin-leaves">
                                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat daftar pengajuan izin...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ADMIN TAB 5: REKAP & LAPORAN -->
            <div id="admin-tab-report" class="admin-tab-content hidden">
                <div class="card">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <h3 style="font-size: 1.05rem;"><i data-lucide="file-spreadsheet" style="display: inline; width: 18px;"></i> Rekapitulasi Presensi Peserta PKL</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Laporan bulanan kehadiran magang siap cetak dan export</p>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <button onclick="exportReportCSV()" class="btn btn-primary btn-sm">
                                <i data-lucide="download"></i> Export Excel (CSV)
                            </button>
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i data-lucide="printer"></i> Cetak Laporan
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-md);">
                        <select id="report-month" class="form-control" style="width: auto; font-size: 0.85rem;" onchange="loadAdminReport()">
                            <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option>
                            <option value="4">April</option><option value="5">Mei</option><option value="6">Juni</option>
                            <option value="7">Juli</option><option value="8">Agustus</option><option value="9">September</option>
                            <option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                        </select>
                        <select id="report-year" class="form-control" style="width: auto; font-size: 0.85rem;" onchange="loadAdminReport()">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>
                        <input type="text" id="report-filter-inst" placeholder="Filter sekolah/kampus..." class="form-control" style="max-width: 200px; font-size: 0.85rem;" onkeyup="loadAdminReport()">
                        <input type="text" id="report-filter-div" placeholder="Filter divisi..." class="form-control" style="max-width: 200px; font-size: 0.85rem;" onkeyup="loadAdminReport()">
                    </div>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Peserta</th>
                                    <th>NIS / NIM</th>
                                    <th>Sekolah / Kampus</th>
                                    <th>Divisi</th>
                                    <th>Hadir</th>
                                    <th>Terlambat</th>
                                    <th>Izin</th>
                                    <th>Sakit</th>
                                    <th>Alpha</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-admin-report">
                                <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat rekapitulasi...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- ================= MODALS ================= -->

    <!-- 1. MODAL PRESENSI KAMERA & GPS (Clock In / Clock Out) -->
    <div id="modal-camera-attendance" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header gradient-ptpn" style="color: white;">
                <div>
                    <h3 id="modal-camera-title" style="color: white; font-size: 1.15rem;">Presensi Kehadiran</h3>
                    <span id="modal-camera-subtitle" style="font-size: 0.78rem; opacity: 0.9;">Ambil foto selfie di lokasi kantor</span>
                </div>
                <button onclick="closeAttendanceCameraModal()" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border-radius: 50%; width: 32px; height: 32px; padding: 0;">✕</button>
            </div>

            <div class="modal-body" style="padding: 1.25rem;">
                <!-- Live GPS Distance Badge -->
                <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 1rem; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Jarak ke Titik Kantor:</span>
                        <strong id="modal-camera-distance" style="font-size: 1.1rem; color: var(--theme-primary);">Menghitung...</strong>
                    </div>
                    <span id="modal-camera-status-badge" class="badge badge-gray">Memeriksa GPS</span>
                </div>

                <!-- Camera Stream Viewport -->
                <div style="position: relative; width: 100%; height: clamp(200px, 42vh, 300px); background: #000; border-radius: var(--radius-lg); overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 20px rgba(0,0,0,0.5);">
                    <video id="camera-video-feed" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <img id="camera-captured-preview" src="" class="hidden" style="width: 100%; height: 100%; object-fit: cover;" alt="Hasil Selfie">
                    
                    <!-- Framing Guide Target Box -->
                    <div id="camera-guide-overlay" style="position: absolute; width: clamp(140px, 40vw, 180px); height: clamp(160px, 35vh, 220px); border: 2px dashed rgba(255,255,255,0.7); border-radius: 100px; pointer-events: none; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 0.72rem; color: rgba(255,255,255,0.85); background: rgba(0,0,0,0.55); padding: 0.2rem 0.5rem; border-radius: 4px; margin-top: auto; margin-bottom: 8px;">Posisikan Wajah</span>
                    </div>

                    <!-- Switch Camera Button on Mobile -->
                    <button type="button" onclick="toggleCameraFacing()" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i data-lucide="flip-horizontal" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>

                <!-- Camera Trigger & Retake Action -->
                <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                    <button type="button" id="btn-snap-photo" onclick="takeSnapshot()" class="btn btn-gold btn-block">
                        <i data-lucide="camera"></i> Ambil Foto Selfie
                    </button>
                    <button type="button" id="btn-retake-photo" onclick="retakeSnapshot()" class="btn btn-secondary btn-block hidden">
                        <i data-lucide="rotate-ccw"></i> Foto Ulang
                    </button>
                </div>

                <div class="form-group" style="margin-top: 1rem; margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.8rem;">Catatan Aktivitas (Opsional)</label>
                    <input type="text" id="attendance-notes" class="form-control" placeholder="Contoh: Mengikuti briefing divisi SDM">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeAttendanceCameraModal()" class="btn btn-secondary">Batal</button>
                <button type="button" id="btn-confirm-attendance" onclick="submitAttendance()" class="btn btn-primary" disabled>
                    <i data-lucide="check-circle"></i> Kirim Presensi
                </button>
            </div>
        </div>
    </div>

    <!-- 2. MODAL PENGAJUAN IZIN / SAKIT -->
    <div id="modal-leave" class="modal-backdrop hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem;"><i data-lucide="file-text" style="display: inline; width: 20px;"></i> Form Pengajuan Izin / Sakit</h3>
                <button onclick="closeLeaveModal()" class="btn btn-sm" style="background: none; border: none; font-size: 1.2rem;">✕</button>
            </div>

            <form id="form-submit-leave" onsubmit="submitLeaveRequest(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Jenis Permohonan</label>
                        <select id="leave-type" class="form-control" required>
                            <option value="sakit">Sakit (Dengan Surat Dokter / Keterangan)</option>
                            <option value="izin_akademik">Izin Keperluan Sekolah / Kampus (Ujian/Bimbingan)</option>
                            <option value="izin_pribadi">Izin Keperluan Keluarga / Pribadi</option>
                            <option value="dispensasi">Dispensasi Tugas Khusus</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" id="leave-start-date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" id="leave-end-date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan / Keterangan Lengkap</label>
                        <textarea id="leave-reason" class="form-control" rows="3" required placeholder="Jelaskan alasan izin secara rinci..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unggah Bukti / Surat Keterangan (Opsional/Disarankan)</label>
                        <input type="file" id="leave-proof-file" class="form-control" accept="image/*,application/pdf">
                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">Format: Foto JPG/PNG atau Dokumen PDF (Maks. 5MB)</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeLeaveModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btn-submit-leave" class="btn btn-primary">
                        <i data-lucide="send"></i> Ajukan Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. MODAL TAMBAH / EDIT AKUN PKL (Admin) -->
    <div id="modal-user-form" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modal-user-title" style="font-size: 1.15rem;"><i data-lucide="user-plus" style="display: inline; width: 20px;"></i> Tambah Akun Siswa/Mahasiswa PKL</h3>
                <button onclick="closeUserModal()" class="btn btn-sm" style="background: none; border: none; font-size: 1.2rem;">✕</button>
            </div>

            <form id="form-user-crud" onsubmit="saveUserData(event)">
                <input type="hidden" id="crud-user-id" value="">
                <input type="hidden" id="crud-user-action" value="create">

                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" id="crud-full-name" class="form-control" required placeholder="Nama lengkap siswa">
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIS / NIM</label>
                            <input type="text" id="crud-identifier-no" class="form-control" required placeholder="Contoh: 2201082001">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Kategori Peserta</label>
                            <select id="crud-user-type" class="form-control" required>
                                <option value="siswa">Siswa/Siswi SMK/SMA</option>
                                <option value="mahasiswa">Mahasiswa/Mahasiswi Kampus</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Divisi Penempatan PTPN IV</label>
                            <input type="text" id="crud-division" class="form-control" required placeholder="Contoh: Bagian TI & Transformasi Digital">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Asal Sekolah / Universitas</label>
                            <input type="text" id="crud-institution" class="form-control" required placeholder="Contoh: Universitas Riau">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jurusan / Program Studi</label>
                            <input type="text" id="crud-major" class="form-control" required placeholder="Contoh: Teknik Informatika">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <input type="text" id="crud-phone" class="form-control" placeholder="081234567890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" id="crud-email" class="form-control" placeholder="nama@email.com">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Periode Mulai</label>
                            <input type="date" id="crud-period-start" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Periode Selesai</label>
                            <input type="date" id="crud-period-end" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; background: #f8fafc; padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Username Login</label>
                            <input type="text" id="crud-username" class="form-control" required placeholder="Username unik">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" id="crud-label-pass">Kata Sandi</label>
                            <input type="text" id="crud-password" class="form-control" placeholder="Minimal 6 karakter">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeUserModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btn-save-user" class="btn btn-primary">
                        <i data-lucide="save"></i> Simpan Data Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. MODAL REVIEW IZIN OLEH ADMIN -->
    <div id="modal-review-leave" class="modal-backdrop hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem;"><i data-lucide="check-square" style="display: inline; width: 20px;"></i> Verifikasi Pengajuan Izin</h3>
                <button onclick="closeReviewLeaveModal()" class="btn btn-sm" style="background: none; border: none; font-size: 1.2rem;">✕</button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="review-leave-id">
                <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a;" id="review-applicant-name">-</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);" id="review-applicant-meta">-</div>
                    <hr style="margin: 0.5rem 0; border: none; border-top: 1px dashed var(--border-color);">
                    <div style="font-size: 0.85rem; margin-top: 0.3rem;">
                        <strong>Jenis:</strong> <span id="review-leave-type">-</span> | <strong>Tanggal:</strong> <span id="review-leave-dates">-</span>
                    </div>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem; color: #334155;" id="review-leave-reason">-</p>
                </div>

                <div id="review-proof-container" class="hidden" style="margin-bottom: 1rem;">
                    <label class="form-label">Bukti Lampiran:</label>
                    <div id="review-proof-content"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Admin / Pembimbing (Opsional)</label>
                    <textarea id="review-admin-notes" class="form-control" rows="2" placeholder="Catatan untuk siswa (misal: Disetujui, jaga kesehatan)"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="submitReviewLeave('rejected')" class="btn btn-danger">
                    <i data-lucide="x-circle"></i> Tolak Permohonan
                </button>
                <button type="button" onclick="submitReviewLeave('approved')" class="btn btn-primary">
                    <i data-lucide="check-circle"></i> Setujui Izin
                </button>
            </div>
        </div>
    </div>

    <!-- 5. MODAL KARTU DIGITAL MAGANG PKL -->
    <div id="modal-digital-card" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 420px; background: transparent; box-shadow: none;">
            <div class="id-card-wrap">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <img src="assets/icons/logo.png" style="width: 34px; height: 34px; border-radius: 6px; background: #ffffff; padding: 2px;" alt="Logo">
                        <div>
                            <h4 style="color: white; font-size: 0.95rem; line-height: 1;">PTPN IV REGIONAL 3</h4>
                            <span style="font-size: 0.68rem; color: #fde68a;">KARTU TANDA PESERTA MAGANG</span>
                        </div>
                    </div>
                    <button onclick="closeDigitalCardModal()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer;">✕</button>
                </div>

                <div style="text-align: center; margin-bottom: 1rem;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: white; color: var(--theme-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; border: 3px solid var(--accent-gold); overflow: hidden;" id="card-avatar-container">
                        <img id="card-avatar-img" class="hidden" src="" style="width: 100%; height: 100%; object-fit: cover;" alt="Foto ID">
                        <div id="card-avatar-text" style="font-size: 2rem; font-weight: 800;">PKL</div>
                    </div>
                    <h3 style="color: white; font-size: 1.15rem; margin-bottom: 0.1rem;" id="card-full-name">-</h3>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.3); padding: 0.2rem 0.6rem; border-radius: 12px; color: #a7f3d0;" id="card-identifier-no">NIS/NIM: -</span>
                </div>

                <div style="background: rgba(0,0,0,0.25); border-radius: 12px; padding: 0.85rem; font-size: 0.8rem; line-height: 1.6; margin-bottom: 1rem;">
                    <div><strong>Institusi:</strong> <span id="card-institution">-</span></div>
                    <div><strong>Program Studi:</strong> <span id="card-major">-</span></div>
                    <div><strong>Penempatan:</strong> <span id="card-division">-</span></div>
                    <div><strong>Masa Magang:</strong> <span id="card-period">-</span></div>
                </div>

                <div style="text-align: center; font-size: 0.7rem; opacity: 0.8;">
                    PT Perkebunan Nusantara IV Regional 3 • Pekanbaru, Riau
                </div>
            </div>
        </div>
    </div>

    <!-- 6. MODAL PETUNJUK PWA INSTALL (ANDROID, IOS & DESKTOP) -->
    <div id="pwa-modal-guide" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header gradient-ptpn" style="color: white; border-top-left-radius: inherit; border-top-right-radius: inherit; display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="assets/icons/icon-192.png" alt="Logo" style="width: 36px; height: 36px; border-radius: 10px; background: #fff; padding: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    <div>
                        <h3 style="color: white; font-size: 1.05rem; margin: 0; line-height: 1.2;">Pasang Aplikasi Absensi</h3>
                        <span style="font-size: 0.75rem; color: rgba(255,255,255,0.85);">PTPN IV Regional 3</span>
                    </div>
                </div>
                <button onclick="closePWAGuide()" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border-radius: 50%; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer;">✕</button>
            </div>

            <div class="modal-body" style="padding: 1.25rem;">
                <!-- App Highlights -->
                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem; justify-content: center;">
                    <span class="pwa-feature-chip"><i data-lucide="zap" style="width: 14px; height: 14px;"></i> Akses Instan</span>
                    <span class="pwa-feature-chip"><i data-lucide="map-pin" style="width: 14px; height: 14px;"></i> GPS Lebih Stabil</span>
                    <span class="pwa-feature-chip"><i data-lucide="bell" style="width: 14px; height: 14px;"></i> Notifikasi Presensi</span>
                </div>

                <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-bottom: 1rem;">
                    Pasang aplikasi di layar utama perangkat Anda untuk pengalaman presensi PKL yang lebih cepat, praktis, dan tanpa perlu membuka browser setiap hari.
                </p>

                <!-- Platform Tabs -->
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <button id="pwa-tab-android" type="button" class="pwa-tab-btn active" onclick="switchPWATab('android')">
                        <i data-lucide="smartphone" style="width: 16px; height: 16px;"></i> Android / Desktop
                    </button>
                    <button id="pwa-tab-ios" type="button" class="pwa-tab-btn" onclick="switchPWATab('ios')">
                        <i data-lucide="apple" style="width: 16px; height: 16px;"></i> Apple iPhone (iOS)
                    </button>
                </div>

                <!-- Android & Desktop Section -->
                <div id="pwa-guide-android">
                    <button type="button" onclick="installPWA()" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 0.85rem; font-weight: 700; box-shadow: 0 4px 15px rgba(11, 110, 79, 0.3);">
                        <i data-lucide="download"></i> Pasang / Install Aplikasi Sekarang
                    </button>

                    <div id="pwa-android-manual-note" style="font-size: 0.82rem; line-height: 1.5; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem;">
                        <strong style="color: var(--theme-dark); display: block; margin-bottom: 0.35rem;">Jika tombol di atas tidak otomatis memunculkan instalasi:</strong>
                        <div class="pwa-step-card" style="margin-bottom: 0.4rem; padding: 0.5rem 0.75rem;">
                            <div class="pwa-step-num">1</div>
                            <div style="font-size: 0.8rem;">Ketuk menu titik tiga (⋮) di pojok kanan atas browser Chrome / Samsung Internet.</div>
                        </div>
                        <div class="pwa-step-card" style="margin-bottom: 0; padding: 0.5rem 0.75rem;">
                            <div class="pwa-step-num">2</div>
                            <div style="font-size: 0.8rem;">Pilih menu <strong>"Install Aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.</div>
                        </div>
                    </div>
                </div>

                <!-- iOS Section -->
                <div id="pwa-guide-ios" class="hidden">
                    <div style="margin-bottom: 0.75rem; font-size: 0.83rem; color: var(--text-muted); text-align: center;">
                        Buka situs ini di browser <strong>Safari</strong> pada iPhone/iPad Anda, lalu ikuti langkah mudah berikut:
                    </div>

                    <div class="pwa-step-card">
                        <div class="pwa-step-num">1</div>
                        <div style="font-size: 0.83rem;">
                            Ketuk tombol <strong>Bagikan / Share</strong> <i data-lucide="share" style="width: 15px; height: 15px; display: inline; vertical-align: middle; color: #0284c7;"></i> pada bilah bawah browser Safari.
                        </div>
                    </div>

                    <div class="pwa-step-card">
                        <div class="pwa-step-num">2</div>
                        <div style="font-size: 0.83rem;">
                            Gulir menu ke bawah lalu pilih opsi <strong>"Tambahkan ke Layar Utama"</strong> (<em>Add to Home Screen</em>) <i data-lucide="plus-square" style="width: 15px; height: 15px; display: inline; vertical-align: middle; color: #10b981;"></i>.
                        </div>
                    </div>

                    <div class="pwa-step-card">
                        <div class="pwa-step-num">3</div>
                        <div style="font-size: 0.83rem;">
                            Ketuk tombol <strong>"Tambah"</strong> (<em>Add</em>) di pojok kanan atas. Icon aplikasi Absensi PKL akan langsung siap di layar utama iPhone Anda!
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.8rem;">
                    <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--text-muted);">
                        <input type="checkbox" id="pwa-dont-show-again" style="accent-color: var(--theme-primary); cursor: pointer;">
                        Jangan tampilkan popup ini lagi
                    </label>
                </div>
            </div>

            <div class="modal-footer" style="padding: 0.85rem 1.25rem; display: flex; gap: 0.5rem; justify-content: flex-end; background: #f8fafc; border-bottom-left-radius: inherit; border-bottom-right-radius: inherit;">
                <button type="button" onclick="closePWAGuide(true)" class="btn btn-secondary btn-sm" style="font-size: 0.85rem;">
                    Nanti Saja
                </button>
                <button type="button" onclick="closePWAGuide()" class="btn btn-primary btn-sm" style="font-size: 0.85rem;">
                    <i data-lucide="check"></i> Mengerti
                </button>
            </div>
        </div>
    </div>

    <!-- 7. MODAL PETA LOKASI PKL & RADIUS -->
    <div id="modal-map-view" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem;"><i data-lucide="map-pin" style="display: inline; width: 20px;"></i> Peta Lokasi & Radius Absensi</h3>
                <button onclick="toggleMapModal(false)" class="btn btn-sm" style="background: none; border: none; font-size: 1.2rem;">✕</button>
            </div>
            <div class="modal-body" style="padding: 1rem;">
                <div id="student-map" style="height: 380px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
                <div id="student-map-legend" style="display: flex; gap: 1rem; margin-top: 0.75rem; font-size: 0.8rem; justify-content: center; flex-wrap: wrap;">
                    <span style="display: flex; align-items: center; gap: 0.3rem;"><span style="width: 12px; height: 12px; background: #0b6e4f; border-radius: 50%; display: inline-block;"></span> Titik Kantor PTPN IV</span>
                    <span style="display: flex; align-items: center; gap: 0.3rem;"><span style="width: 12px; height: 12px; background: #0284c7; border-radius: 50%; display: inline-block;"></span> Posisi Anda</span>
                    <span style="display: flex; align-items: center; gap: 0.3rem;"><span style="width: 12px; height: 12px; background: rgba(16, 185, 129, 0.4); border: 1px solid #10b981; border-radius: 50%; display: inline-block;"></span> Radius Diizinkan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 8. MODAL PENGATURAN PROFIL & UBAH KATA SANDI -->
    <div id="modal-profile-settings" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header gradient-ptpn" style="color: white;">
                <div>
                    <h3 style="color: white; font-size: 1.15rem;"><i data-lucide="user-cog" style="display: inline; width: 20px;"></i> Pengaturan Akun & Profil</h3>
                    <span style="font-size: 0.78rem; opacity: 0.9;">Ubah foto profil, kontak, dan kata sandi</span>
                </div>
                <button onclick="closeProfileSettingsModal()" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border-radius: 50%; width: 32px; height: 32px; padding: 0;">✕</button>
            </div>

            <form id="form-profile-settings" onsubmit="saveProfileSettings(event)">
                <div class="modal-body" style="padding: 1.25rem;">
                    <!-- Avatar Upload Area -->
                    <div style="text-align: center; margin-bottom: 1.15rem;">
                        <div style="position: relative; display: inline-block;">
                            <div id="edit-profile-avatar-box" style="width: 88px; height: 88px; border-radius: 50%; background: #ffffff; color: var(--theme-primary); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 800; border: 3px solid var(--accent-gold); overflow: hidden; box-shadow: 0 8px 20px var(--theme-glow); margin: 0 auto;">
                                <img id="edit-profile-avatar-preview" class="hidden" src="" style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar Preview">
                                <span id="edit-profile-avatar-fallback">PKL</span>
                            </div>
                            <label for="edit-profile-avatar-input" style="position: absolute; bottom: 0; right: 0; background: var(--theme-primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.25);" title="Pilih foto profil baru">
                                <i data-lucide="camera" style="width: 16px; height: 16px;"></i>
                            </label>
                            <input type="file" id="edit-profile-avatar-input" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewSelectedAvatar(this)">
                        </div>
                        <span style="display: block; font-size: 0.74rem; color: var(--text-muted); margin-top: 0.4rem;">Ketuk icon kamera untuk ganti foto profil</span>
                    </div>

                    <!-- User Information Details -->
                    <div style="background: rgba(248, 250, 252, 0.9); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; margin-bottom: 1rem; border: 1px solid var(--border-subtle); font-size: 0.82rem; line-height: 1.5;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.2rem;">
                            <span style="color: var(--text-muted);">Nama Lengkap:</span>
                            <strong id="edit-info-name" style="color: #0f172a;">-</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.2rem;">
                            <span style="color: var(--text-muted);">NIS / NIM:</span>
                            <strong id="edit-info-nim" style="color: #0f172a;">-</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Institusi:</span>
                            <strong id="edit-info-inst" style="color: #0f172a;">-</strong>
                        </div>
                    </div>

                    <!-- Contact Fields -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label class="form-label">WhatsApp / HP</label>
                            <input type="text" id="edit-profile-phone" class="form-control" placeholder="081234567890">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label class="form-label">Email</label>
                            <input type="email" id="edit-profile-email" class="form-control" placeholder="nama@email.com">
                        </div>
                    </div>

                    <!-- Password Change Accordion / Box -->
                    <div style="border-top: 1px dashed var(--border-subtle); padding-top: 0.85rem; margin-top: 0.4rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
                            <strong style="font-size: 0.88rem; color: #0f172a;"><i data-lucide="lock" style="width: 15px; height: 15px; display: inline;"></i> Ganti Kata Sandi</strong>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Kosongkan jika tidak diubah</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.65rem;">
                            <label class="form-label" style="font-size: 0.78rem;">Kata Sandi Saat Ini</label>
                            <input type="password" id="edit-current-password" class="form-control" placeholder="Masukkan kata sandi lama">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.78rem;">Kata Sandi Baru</label>
                                <input type="password" id="edit-new-password" class="form-control" placeholder="Min. 6 karakter">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.78rem;">Ulangi Sandi Baru</label>
                                <input type="password" id="edit-confirm-password" class="form-control" placeholder="Konfirmasi sandi">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeProfileSettingsModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btn-save-profile" class="btn btn-primary">
                        <i data-lucide="save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. MODAL KUSTOMISASI TEMA WARNA -->
    <div id="modal-theme-picker" class="modal-backdrop hidden">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header gradient-ptpn" style="color: white; border-top-left-radius: inherit; border-top-right-radius: inherit; display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <i data-lucide="palette" style="width: 22px; height: 22px;"></i>
                    <h3 style="color: white; font-size: 1.1rem; margin: 0;">Pilih & Kustomisasi Tema</h3>
                </div>
                <button onclick="closeThemePickerModal()" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border-radius: 50%; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer;">✕</button>
            </div>

            <div class="modal-body" style="padding: 1.25rem;">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Sesuaikan skema warna tampilan aplikasi sesuai selera Anda. Background card dan header tabel otomatis menyesuaikan secara konsisten.
                </p>

                <h4 style="font-size: 0.88rem; font-weight: 700; margin-bottom: 0.65rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="sparkles" style="width: 16px; height: 16px; color: var(--accent-gold);"></i> Pilihan Tema Warna Siap Pakai
                </h4>

                <div class="theme-preset-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1.25rem;">
                    <button type="button" class="theme-preset-card theme-card-hijau" onclick="setAppTheme('hijau')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #0b6e4f, #10b981);"></span>
                        <span class="theme-preset-label">Hijau Emerald</span>
                    </button>
                    <button type="button" class="theme-preset-card theme-card-biru" onclick="setAppTheme('biru')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #1d4ed8, #38bdf8);"></span>
                        <span class="theme-preset-label">Biru Sapphire</span>
                    </button>
                    <button type="button" class="theme-preset-card theme-card-pink" onclick="setAppTheme('pink')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #be185d, #f472b6);"></span>
                        <span class="theme-preset-label">Pink Magenta</span>
                    </button>
                    <button type="button" class="theme-preset-card theme-card-oren" onclick="setAppTheme('oren')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #c2410c, #fb923c);"></span>
                        <span class="theme-preset-label">Oren Sunburst</span>
                    </button>
                    <button type="button" class="theme-preset-card theme-card-ungu" onclick="setAppTheme('ungu')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #7e22ce, #c084fc);"></span>
                        <span class="theme-preset-label">Ungu Violet</span>
                    </button>
                    <button type="button" class="theme-preset-card theme-card-teal" onclick="setAppTheme('teal')">
                        <span class="theme-preset-preview" style="background: linear-gradient(135deg, #0f766e, #2dd4bf);"></span>
                        <span class="theme-preset-label">Teal Cyan</span>
                    </button>
                </div>

                <h4 style="font-size: 0.88rem; font-weight: 700; margin-bottom: 0.65rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="sliders" style="width: 16px; height: 16px; color: var(--theme-primary);"></i> Kustom Warna Bebas (Color Picker)
                </h4>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.9rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="position: relative; width: 44px; height: 44px; border-radius: var(--radius-md); overflow: hidden; border: 2px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;">
                            <input type="color" id="custom-theme-color-input" value="#0b6e4f" style="position: absolute; inset: -10px; width: 65px; height: 65px; cursor: pointer; border: none;" oninput="syncCustomColorInput(this.value)">
                        </div>
                        <div style="flex: 1;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.2rem;">Kode Hex Warna</label>
                            <div style="display: flex; gap: 0.4rem;">
                                <input type="text" id="custom-theme-color-hex" class="form-control" value="#0b6e4f" maxlength="7" style="font-size: 0.85rem; padding: 0.35rem 0.65rem; font-family: monospace; font-weight: 700;" oninput="syncCustomColorHex(this.value)">
                                <button type="button" class="btn btn-primary btn-sm" onclick="applyCustomThemeColor()" style="padding: 0.35rem 0.75rem;">
                                    <i data-lucide="check"></i> Terapkan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Live preview swatch -->
                    <div id="custom-theme-preview-box" style="padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); background: var(--theme-card-tint); border: 1px solid var(--theme-card-border); font-size: 0.78rem; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-weight: 600; color: var(--theme-primary);">Preview Tint Background Card & Tabel</span>
                        <span class="badge" style="background: var(--theme-primary); color: white; font-size: 0.68rem;">Aktif</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 0.85rem 1.25rem; display: flex; gap: 0.5rem; justify-content: space-between; background: #f8fafc; border-bottom-left-radius: inherit; border-bottom-right-radius: inherit;">
                <button type="button" onclick="setAppTheme('hijau')" class="btn btn-secondary btn-sm" style="font-size: 0.8rem;">
                    <i data-lucide="rotate-ccw"></i> Reset Standar
                </button>
                <button type="button" onclick="closeThemePickerModal()" class="btn btn-primary btn-sm" style="font-size: 0.85rem;">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar (Dynamic for PKL & Admin) -->
    <nav class="mobile-bottom-nav hidden" id="mobile-bottom-nav">
        <!-- PKL Nav Items -->
        <div id="mob-nav-pkl-group" style="display: flex; width: 100%; justify-content: space-around;">
            <button class="nav-item-mobile active" onclick="handleMobileNav('home')" id="mob-tab-home">
                <i data-lucide="home"></i>
                <span>Beranda</span>
            </button>
            <button class="nav-item-mobile" onclick="handleMobileNav('presence')" id="mob-tab-presence">
                <i data-lucide="camera"></i>
                <span>Presensi</span>
            </button>
            <button class="nav-item-mobile" onclick="handleMobileNav('leave')" id="mob-tab-leave">
                <i data-lucide="file-text"></i>
                <span>Izin</span>
            </button>
            <button class="nav-item-mobile" onclick="handleMobileNav('history')" id="mob-tab-history">
                <i data-lucide="calendar"></i>
                <span>Riwayat</span>
            </button>
            <button class="nav-item-mobile" onclick="handleMobileNav('idcard')" id="mob-tab-idcard">
                <i data-lucide="credit-card"></i>
                <span>Kartu ID</span>
            </button>
        </div>

        <!-- Admin Nav Items -->
        <div id="mob-nav-admin-group" class="hidden" style="display: flex; width: 100%; justify-content: space-around;">
            <button class="nav-item-mobile active" onclick="switchAdminTab('monitoring'); updateMobileAdminNav('monitoring');" id="mob-adm-monitoring">
                <i data-lucide="layout-dashboard"></i>
                <span>Monitor</span>
            </button>
            <button class="nav-item-mobile" onclick="switchAdminTab('users'); updateMobileAdminNav('users');" id="mob-adm-users">
                <i data-lucide="users"></i>
                <span>Akun</span>
            </button>
            <button class="nav-item-mobile" onclick="switchAdminTab('location'); updateMobileAdminNav('location');" id="mob-adm-location">
                <i data-lucide="map-pin"></i>
                <span>Lokasi</span>
            </button>
            <button class="nav-item-mobile" onclick="switchAdminTab('leaves'); updateMobileAdminNav('leaves');" id="mob-adm-leaves">
                <i data-lucide="file-check"></i>
                <span>Izin</span>
            </button>
            <button class="nav-item-mobile" onclick="switchAdminTab('report'); updateMobileAdminNav('report');" id="mob-adm-report">
                <i data-lucide="file-spreadsheet"></i>
                <span>Laporan</span>
            </button>
        </div>
    </nav>

    <!-- JavaScript Application Logic -->
    <script src="assets/js/gps.js"></script>
    <script src="assets/js/camera.js"></script>
    <script src="assets/js/pwa.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
