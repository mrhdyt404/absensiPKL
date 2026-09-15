// assets/js/app.js - Main Application State and View Controller

// Global App State
const AppState = {
    currentUser: null,
    officeSettings: {
        latitude: 0.528383,
        longitude: 101.447781,
        radius_meters: 200,
        office_name: 'Kantor Direksi PTPN IV Regional 3',
        work_start_time: '07:30:00',
        work_late_tolerance: '08:00:00',
        work_end_time: '16:30:00'
    },
    todayAttendance: null,
    todayLeave: null,
    currentGPS: null,
    currentAttendanceMode: 'clock_in', // 'clock_in' or 'clock_out'
    capturedPhotoData: null,
    adminMap: null,
    adminMarker: null,
    adminCircle: null,
    studentMap: null,
    studentMarker: null,
    studentCircle: null
};

// Color helper: convert hex to RGB
function hexToRgb(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
    }
    const num = parseInt(hex, 16);
    if (isNaN(num) || hex.length !== 6) return null;
    return {
        r: (num >> 16) & 255,
        g: (num >> 8) & 255,
        b: num & 255
    };
}

// Color helper: adjust brightness
function adjustColor(hex, percent) {
    const rgb = hexToRgb(hex);
    if (!rgb) return hex;
    const r = Math.max(0, Math.min(255, Math.round(rgb.r + (rgb.r * percent) / 100)));
    const g = Math.max(0, Math.min(255, Math.round(rgb.g + (rgb.g * percent) / 100)));
    const b = Math.max(0, Math.min(255, Math.round(rgb.b + (rgb.b * percent) / 100)));
    return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}

// PWA Manifest and Theme Color Synchronizer
function updatePWAManifest(themeColor, bgColor) {
    const manifestLink = document.getElementById('pwa-manifest-link');
    const customColor = localStorage.getItem('absensi_pkl_custom_color') || '';
    const activeTheme = localStorage.getItem('absensi_pkl_theme') || 'hijau';
    
    // Update manifest tag URL with theme & color parameters
    const manifestUrl = `./manifest.php?theme=${encodeURIComponent(activeTheme)}&color=${encodeURIComponent(customColor)}&t=${Date.now()}`;
    if (manifestLink) {
        manifestLink.setAttribute('href', manifestUrl);
    }
    
    // Save cookies with 1 year lifetime for server-side persistence
    document.cookie = `absensi_pkl_theme=${encodeURIComponent(activeTheme)}; path=/; max-age=31536000; SameSite=Lax`;
    if (customColor) {
        document.cookie = `absensi_pkl_custom_color=${encodeURIComponent(customColor)}; path=/; max-age=31536000; SameSite=Lax`;
    } else {
        document.cookie = `absensi_pkl_custom_color=; path=/; max-age=0; SameSite=Lax`;
    }
}

// Theme Engine (Presets & Custom Color Picker)
function setAppTheme(themeName, customHex = null) {
    const validThemes = ['hijau', 'biru', 'pink', 'oren', 'ungu', 'teal', 'merah'];
    
    if (themeName !== 'custom') {
        if (!validThemes.includes(themeName)) themeName = 'hijau';
        
        // Remove inline CSS overrides to let preset classes take effect
        const rootStyle = document.documentElement.style;
        rootStyle.removeProperty('--theme-primary');
        rootStyle.removeProperty('--theme-primary-hover');
        rootStyle.removeProperty('--theme-secondary');
        rootStyle.removeProperty('--theme-dark');
        rootStyle.removeProperty('--theme-light');
        rootStyle.removeProperty('--theme-primary-rgb');
        rootStyle.removeProperty('--theme-glow');
        rootStyle.removeProperty('--theme-card-tint');
        rootStyle.removeProperty('--theme-card-border');
        rootStyle.removeProperty('--theme-th-bg');
        rootStyle.removeProperty('--theme-th-color');
        rootStyle.removeProperty('--theme-th-border');
        rootStyle.removeProperty('--theme-gradient');
        rootStyle.removeProperty('--theme-gradient-glass');

        if (themeName === 'hijau') {
            document.body.removeAttribute('data-theme');
        } else {
            document.body.setAttribute('data-theme', themeName);
        }

        localStorage.setItem('absensi_pkl_theme', themeName);
        localStorage.removeItem('absensi_pkl_custom_color');

        const themeMap = {
            hijau: { theme: '#0b6e4f', bg: '#064e3b' },
            biru:  { theme: '#1d4ed8', bg: '#1e3a8a' },
            pink:  { theme: '#be185d', bg: '#831843' },
            oren:  { theme: '#c2410c', bg: '#7c2d12' },
            ungu:  { theme: '#7e22ce', bg: '#581c87' },
            teal:  { theme: '#0f766e', bg: '#134e4a' },
            merah: { theme: '#b91c1c', bg: '#7f1d1d' }
        };
        const currentColors = themeMap[themeName] || themeMap.hijau;
        const metaTheme = document.getElementById('meta-theme-color');
        if (metaTheme) metaTheme.setAttribute('content', currentColors.theme);

        updatePWAManifest(currentColors.theme, currentColors.bg);
        updateThemeModalActive(themeName, currentColors.theme);
    } else if (customHex) {
        let hex = customHex.trim();
        if (!hex.startsWith('#')) hex = '#' + hex;
        const rgb = hexToRgb(hex);
        if (!rgb) return;

        const darkHex = adjustColor(hex, -35);
        const hoverHex = adjustColor(hex, -20);
        const secondaryHex = adjustColor(hex, 25);
        const rgbStr = `${rgb.r}, ${rgb.g}, ${rgb.b}`;

        document.body.removeAttribute('data-theme');
        const rootStyle = document.documentElement.style;
        rootStyle.setProperty('--theme-primary', hex);
        rootStyle.setProperty('--theme-primary-hover', hoverHex);
        rootStyle.setProperty('--theme-secondary', secondaryHex);
        rootStyle.setProperty('--theme-dark', darkHex);
        rootStyle.setProperty('--theme-light', `rgba(${rgbStr}, 0.08)`);
        rootStyle.setProperty('--theme-primary-rgb', rgbStr);
        rootStyle.setProperty('--theme-glow', `rgba(${rgbStr}, 0.28)`);
        rootStyle.setProperty('--theme-card-tint', `rgba(${rgbStr}, 0.035)`);
        rootStyle.setProperty('--theme-card-border', `rgba(${rgbStr}, 0.14)`);
        rootStyle.setProperty('--theme-th-bg', `rgba(${rgbStr}, 0.08)`);
        rootStyle.setProperty('--theme-th-color', hex);
        rootStyle.setProperty('--theme-th-border', `rgba(${rgbStr}, 0.2)`);
        rootStyle.setProperty('--theme-gradient', `linear-gradient(135deg, ${darkHex} 0%, ${hex} 50%, ${secondaryHex} 100%)`);
        rootStyle.setProperty('--theme-gradient-glass', `linear-gradient(135deg, rgba(${rgbStr}, 0.95) 0%, rgba(${rgbStr}, 0.88) 50%, rgba(${rgbStr}, 0.8) 100%)`);

        localStorage.setItem('absensi_pkl_theme', 'custom');
        localStorage.setItem('absensi_pkl_custom_color', hex);

        const metaTheme = document.getElementById('meta-theme-color');
        if (metaTheme) metaTheme.setAttribute('content', hex);

        updatePWAManifest(hex, darkHex);
        updateThemeModalActive('custom', hex);
    }

    if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
}

function updateThemeModalActive(themeName, customHex = null) {
    document.querySelectorAll('.theme-preset-card').forEach(c => c.classList.remove('active'));
    if (themeName !== 'custom') {
        const activeCard = document.querySelector(`.theme-card-${themeName}`);
        if (activeCard) activeCard.classList.add('active');
    }
    const colorInput = document.getElementById('custom-theme-color-input');
    const colorHex = document.getElementById('custom-theme-color-hex');
    if (customHex && colorInput && colorHex) {
        colorInput.value = customHex;
        colorHex.value = customHex.toUpperCase();
    }
}

function initAppTheme() {
    const saved = localStorage.getItem('absensi_pkl_theme') || 'hijau';
    const customColor = localStorage.getItem('absensi_pkl_custom_color');
    if (saved === 'custom' && customColor) {
        setAppTheme('custom', customColor);
    } else {
        setAppTheme(saved);
    }
}

function openThemePickerModal() {
    const modal = document.getElementById('modal-theme-picker');
    if (modal) {
        modal.classList.remove('hidden');
        const saved = localStorage.getItem('absensi_pkl_theme') || 'hijau';
        const customColor = localStorage.getItem('absensi_pkl_custom_color');
        updateThemeModalActive(saved, customColor || '#0b6e4f');
        if (window.lucide) lucide.createIcons();
    }
}

function closeThemePickerModal() {
    const modal = document.getElementById('modal-theme-picker');
    if (modal) modal.classList.add('hidden');
}

function syncCustomColorInput(val) {
    const hexInput = document.getElementById('custom-theme-color-hex');
    if (hexInput) hexInput.value = val.toUpperCase();
}

function syncCustomColorHex(val) {
    const colorInput = document.getElementById('custom-theme-color-input');
    if (/^#[0-9A-Fa-f]{6}$/.test(val) && colorInput) {
        colorInput.value = val;
    }
}

function applyCustomThemeColor() {
    const hexInput = document.getElementById('custom-theme-color-hex');
    let val = hexInput ? hexInput.value.trim() : '';
    if (!val.startsWith('#')) val = '#' + val;
    if (!/^#[0-9A-Fa-f]{6}$/.test(val)) {
        showToast('Format warna hex tidak valid (contoh: #0b6e4f)', 'error');
        return;
    }
    setAppTheme('custom', val);
    showToast('Tema warna kustom berhasil diterapkan!', 'success');
}

window.setAppTheme = setAppTheme;
window.initAppTheme = initAppTheme;
window.openThemePickerModal = openThemePickerModal;
window.closeThemePickerModal = closeThemePickerModal;
window.syncCustomColorInput = syncCustomColorInput;
window.syncCustomColorHex = syncCustomColorHex;
window.applyCustomThemeColor = applyCustomThemeColor;

// Initialize App on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initAppTheme();
    initDigitalClock();
    checkAuthSession();
    lucide.createIcons();

    // Auto check GPS periodically if user is logged in as PKL
    setInterval(() => {
        if (AppState.currentUser && AppState.currentUser.role === 'pkl') {
            checkCurrentLocationStatus();
        }
    }, 15000);
});

// Toast notification helper
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type === 'error' ? 'toast-error' : (type === 'warning' ? 'toast-warning' : '')}`;
    
    let iconName = 'check-circle';
    if (type === 'error') iconName = 'alert-circle';
    if (type === 'warning') iconName = 'alert-triangle';

    toast.innerHTML = `
        <i data-lucide="${iconName}" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    lucide.createIcons({ root: toast });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
window.showToast = showToast;

// Live Digital Clock
function initDigitalClock() {
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    function update() {
        const now = new Date();
        const clockEl = document.getElementById('live-digital-clock');
        const dateEl = document.getElementById('live-date-text');

        if (clockEl) {
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            clockEl.textContent = `${h}:${m}:${s} WIB`;
        }

        if (dateEl) {
            const dayName = days[now.getDay()];
            const day = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            dateEl.textContent = `${dayName}, ${day} ${monthName} ${year}`;
        }
    }

    update();
    setInterval(update, 1000);
}

// Check Login Session
async function checkAuthSession() {
    try {
        const res = await fetch('api/auth.php?action=check');
        const data = await res.json();

        if (data.authenticated && data.user) {
            AppState.currentUser = data.user;
            if (data.settings) AppState.officeSettings = data.settings;
            AppState.todayAttendance = data.today_attendance;
            AppState.todayLeave = data.today_leave;
            renderUserInterface();
        } else {
            AppState.currentUser = null;
            showView('login');
        }
    } catch (err) {
        console.error('Session check error:', err);
        showView('login');
    }
}

// View switcher
function showView(viewName) {
    const views = ['login', 'pkl', 'admin'];
    views.forEach(v => {
        const el = document.getElementById(`view-${v}`);
        if (el) el.classList.add('hidden');
    });

    const target = document.getElementById(`view-${viewName}`);
    if (target) target.classList.remove('hidden');

    const userHeaderInfo = document.getElementById('user-header-info');
    const userBadgeName = document.getElementById('user-badge-name');
    const navSubtitle = document.getElementById('nav-subtitle');
    const bottomNav = document.getElementById('mobile-bottom-nav');
    const pklNavGroup = document.getElementById('mob-nav-pkl-group');
    const adminNavGroup = document.getElementById('mob-nav-admin-group');
    const btnLogoutTop = document.getElementById('btn-logout-top');

    if (viewName === 'login') {
        if (userHeaderInfo) userHeaderInfo.classList.add('hidden');
        if (btnLogoutTop) btnLogoutTop.classList.add('hidden');
        if (navSubtitle) navSubtitle.textContent = 'Sistem Absensi Digital PKL';
        if (bottomNav) bottomNav.classList.add('hidden');
    } else {
        if (userHeaderInfo) userHeaderInfo.classList.remove('hidden');
        if (btnLogoutTop) btnLogoutTop.classList.remove('hidden');
        if (userBadgeName && AppState.currentUser) {
            userBadgeName.textContent = AppState.currentUser.full_name;
        }
        if (navSubtitle) {
            navSubtitle.textContent = AppState.currentUser?.role === 'admin' ? 'Portal Admin SDM' : 'Portal Peserta PKL';
        }

        // Configure Bottom Nav Bar for Mobile
        if (bottomNav) {
            bottomNav.classList.remove('hidden');
            if (viewName === 'pkl') {
                if (pklNavGroup) pklNavGroup.classList.remove('hidden');
                if (adminNavGroup) adminNavGroup.classList.add('hidden');
            } else if (viewName === 'admin') {
                if (pklNavGroup) pklNavGroup.classList.add('hidden');
                if (adminNavGroup) adminNavGroup.classList.remove('hidden');
            }
        }
    }

    lucide.createIcons();
}

// Mobile Bottom Nav Handler for Student PKL
function handleMobileNav(action) {
    // Reset active nav buttons
    document.querySelectorAll('#mob-nav-pkl-group .nav-item-mobile').forEach(btn => btn.classList.remove('active'));
    const targetBtn = document.getElementById(`mob-tab-${action}`);
    if (targetBtn) targetBtn.classList.add('active');

    if (action === 'home') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (action === 'presence') {
        // If already clocked in and not clocked out, open clock out; else open clock in
        const att = AppState.todayAttendance;
        if (att && att.time_in && !att.time_out) {
            openAttendanceCameraModal('clock_out');
        } else {
            openAttendanceCameraModal('clock_in');
        }
    } else if (action === 'leave') {
        openLeaveModal();
    } else if (action === 'history') {
        document.getElementById('section-pkl-history')?.scrollIntoView({ behavior: 'smooth' });
    } else if (action === 'idcard') {
        openDigitalCardModal();
    }
}
window.handleMobileNav = handleMobileNav;

// Mobile Admin Tab Switcher Sync
function updateMobileAdminNav(tabName) {
    document.querySelectorAll('#mob-nav-admin-group .nav-item-mobile').forEach(btn => btn.classList.remove('active'));
    const targetBtn = document.getElementById(`mob-adm-${tabName}`);
    if (targetBtn) targetBtn.classList.add('active');
}
window.updateMobileAdminNav = updateMobileAdminNav;

// Render Dashboard based on Role
function renderUserInterface() {
    if (!AppState.currentUser) {
        showView('login');
        return;
    }

    if (AppState.currentUser.role === 'admin') {
        showView('admin');
        initAdminDashboard();
    } else {
        showView('pkl');
        initPKLDashboard();
    }
}

// Toggle Password Visibility (Eye icon)
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    if (btn) {
        const iconName = isPassword ? 'eye-off' : 'eye';
        const iconSize = inputId === 'login-password' ? '18px' : '15px';
        btn.innerHTML = `<i data-lucide="${iconName}" style="width: ${iconSize}; height: ${iconSize};"></i>`;
        btn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
        btn.setAttribute('title', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons({ root: btn });
        }
    }
}
window.togglePasswordVisibility = togglePasswordVisibility;

// Role Selector on Login Page
function selectLoginRole(role) {
    const btnPkl = document.getElementById('role-btn-pkl');
    const btnAdmin = document.getElementById('role-btn-admin');
    const labelUser = document.getElementById('text-login-username');
    const inputUser = document.getElementById('login-username');

    if (role === 'admin') {
        if (btnAdmin) btnAdmin.classList.add('active');
        if (btnPkl) btnPkl.classList.remove('active');
        if (labelUser) labelUser.textContent = 'Username Administrator';
        if (inputUser) {
            inputUser.placeholder = 'Masukkan username admin';
        }
    } else {
        if (btnPkl) btnPkl.classList.add('active');
        if (btnAdmin) btnAdmin.classList.remove('active');
        if (labelUser) labelUser.textContent = 'Username / NIS / NIM / Email';
        if (inputUser) {
            inputUser.placeholder = 'Masukkan username atau NIS/NIM';
        }
    }
}
window.selectLoginRole = selectLoginRole;

// Quick Demo Login Helper
function quickFillRole(user, pass, role) {
    selectLoginRole(role);
    const userInput = document.getElementById('login-username');
    const passInput = document.getElementById('login-password');
    if (userInput && passInput) {
        userInput.value = user;
        passInput.value = pass;
        showToast(`Akun demo ${role === 'admin' ? 'Admin SDM' : 'Peserta PKL'} (${user}) terisi otomatis.`, 'info');
    }
}
window.quickFillRole = quickFillRole;

function quickFillLogin(user, pass) {
    document.getElementById('login-username').value = user;
    document.getElementById('login-password').value = pass;
    document.getElementById('form-login').dispatchEvent(new Event('submit'));
}
window.quickFillLogin = quickFillLogin;

// Login Help Information Dialog
function showLoginHelp() {
    showToast('Peserta PKL: Masuk dengan NIS/NIM atau username. Lupa kata sandi? Hubungi tim SDM PTPN IV.', 'info');
}
window.showLoginHelp = showLoginHelp;

// Handle Login Submission
async function handleLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-login');
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value.trim();

    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Memproses...`;

    try {
        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            await checkAuthSession();
        } else {
            showToast(data.message || 'Login gagal.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan jaringan.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="log-in"></i> Masuk Sekarang`;
        lucide.createIcons();
    }
}

// Logout
async function logoutUser() {
    try {
        await fetch('api/auth.php?action=logout');
        AppState.currentUser = null;
        showToast('Anda telah berhasil keluar.', 'success');
        showView('login');
    } catch (err) {
        location.reload();
    }
}

// ====================================================================
// ========================= SISWA / MAHASISWA PKL ====================
// ====================================================================

function initPKLDashboard() {
    const user = AppState.currentUser;
    if (!user) return;

    // Set Profile Info
    document.getElementById('pkl-name').textContent = user.full_name;
    document.getElementById('pkl-institution').textContent = `${user.identifier_no} • ${user.institution} (${user.major || '-'})`;
    document.getElementById('pkl-division-text').textContent = user.division || 'Divisi Penempatan Belum Diatur';

    // Avatar display
    const avatarImg = document.getElementById('pkl-avatar-img');
    const avatarText = document.getElementById('pkl-avatar-text');
    if (avatarImg && avatarText) {
        if (user.avatar) {
            avatarImg.src = user.avatar;
            avatarImg.classList.remove('hidden');
            avatarText.classList.add('hidden');
        } else {
            avatarImg.classList.add('hidden');
            avatarText.classList.remove('hidden');
            avatarText.textContent = user.full_name.substring(0, 2).toUpperCase();
        }
    }

    const typeBadge = document.getElementById('pkl-type-badge');
    if (typeBadge) {
        typeBadge.textContent = user.user_type === 'siswa' ? 'SISWA SMK' : 'MAHASISWA';
    }

    // Set Schedule Labels
    const workStart = AppState.officeSettings.work_start_time.substring(0, 5);
    const workLate = AppState.officeSettings.work_late_tolerance.substring(0, 5);
    const workEnd = AppState.officeSettings.work_end_time.substring(0, 5);
    document.getElementById('label-jam-masuk').textContent = `Jadwal: ${workStart} - ${workLate} WIB`;
    document.getElementById('label-jam-pulang').textContent = `Jadwal Mulai: ${workEnd} WIB`;

    // Update Attendance Cards UI
    updateTodayAttendanceCards();

    // Trigger GPS Check
    checkCurrentLocationStatus();

    // Load History
    loadPKLHistory();
}

function updateTodayAttendanceCards() {
    const att = AppState.todayAttendance;
    const btnMasuk = document.getElementById('btn-trigger-clockin');
    const btnPulang = document.getElementById('btn-trigger-clockout');
    const badgeMasuk = document.getElementById('badge-masuk-status');
    const badgePulang = document.getElementById('badge-pulang-status');
    const masukDetail = document.getElementById('masuk-detail-box');
    const pulangDetail = document.getElementById('pulang-detail-box');

    // Reset default
    btnMasuk.disabled = false;
    btnPulang.disabled = true;

    if (att && att.time_in) {
        btnMasuk.disabled = true;
        btnMasuk.classList.add('btn-disabled');
        btnMasuk.innerHTML = `<i data-lucide="check"></i> Sudah Absen Masuk`;
        
        badgeMasuk.className = att.status === 'terlambat' ? 'badge badge-warning' : 'badge badge-success';
        badgeMasuk.textContent = att.status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu';

        if (masukDetail) {
            masukDetail.classList.remove('hidden');
            document.getElementById('masuk-time-text').textContent = `${att.time_in.substring(0, 5)} WIB`;
            document.getElementById('masuk-distance-text').textContent = `Jarak GPS: ${att.distance_in_meters || 0}m dari kantor`;
            if (att.photo_in) {
                document.getElementById('masuk-photo-preview').src = att.photo_in;
            }
        }

        // Enable Clock Out button if clock in is done
        btnPulang.disabled = false;
        btnPulang.classList.remove('btn-disabled');
    } else {
        btnMasuk.innerHTML = `<i data-lucide="camera"></i> Absen Masuk Sekarang`;
        badgeMasuk.className = 'badge badge-gray';
        badgeMasuk.textContent = 'Belum Absen';
        if (masukDetail) masukDetail.classList.add('hidden');
    }

    if (att && att.time_out) {
        btnPulang.disabled = true;
        btnPulang.classList.add('btn-disabled');
        btnPulang.innerHTML = `<i data-lucide="check-check"></i> Sudah Absen Pulang`;

        badgePulang.className = 'badge badge-success';
        badgePulang.textContent = 'Sudah Pulang';

        if (pulangDetail) {
            pulangDetail.classList.remove('hidden');
            document.getElementById('pulang-time-text').textContent = `${att.time_out.substring(0, 5)} WIB`;
            document.getElementById('pulang-distance-text').textContent = `Jarak GPS: ${att.distance_out_meters || 0}m dari kantor`;
            if (att.photo_out) {
                document.getElementById('pulang-photo-preview').src = att.photo_out;
            }
        }
    } else {
        if (!att || !att.time_in) {
            btnPulang.disabled = true;
            btnPulang.classList.add('btn-disabled');
        }
        badgePulang.className = 'badge badge-gray';
        badgePulang.textContent = 'Belum Absen';
        if (pulangDetail) pulangDetail.classList.add('hidden');
    }

    lucide.createIcons();
}

// GPS Check & Distance Calculation
async function checkCurrentLocationStatus() {
    const badge = document.getElementById('gps-status-badge');
    const desc = document.getElementById('gps-distance-desc');
    const btnRefresh = document.getElementById('btn-refresh-gps');

    if (badge) {
        badge.className = 'badge badge-gray';
        badge.textContent = 'Mendeteksi GPS...';
    }

    try {
        const pos = await GPSEngine.getCurrentPosition();
        AppState.currentGPS = pos;

        const targetLat = parseFloat(AppState.officeSettings.latitude);
        const targetLon = parseFloat(AppState.officeSettings.longitude);
        const maxRadius = parseInt(AppState.officeSettings.radius_meters);

        const check = GPSEngine.checkRadius(targetLat, targetLon, maxRadius);

        if (check.isInside) {
            if (badge) {
                badge.className = 'badge badge-success';
                badge.innerHTML = `<i data-lucide="check"></i> Di Dalam Radius (${check.distance}m)`;
            }
            if (desc) {
                desc.innerHTML = `<strong style="color: #166534;">✅ Lokasi Valid.</strong> Jarak Anda: <strong>${check.distance} meter</strong> dari ${AppState.officeSettings.office_name} (Maks. ${maxRadius}m).`;
            }
        } else {
            if (badge) {
                badge.className = 'badge badge-danger';
                badge.innerHTML = `<i data-lucide="x"></i> Di Luar Radius (${check.distance}m)`;
            }
            if (desc) {
                desc.innerHTML = `<strong style="color: #991b1b;">❌ Posisi Di Luar Radius.</strong> Jarak Anda: <strong>${check.distance} meter</strong> (Maks. ${maxRadius}m). Anda harus berada di area kantor untuk presensi.`;
            }
        }
    } catch (err) {
        if (badge) {
            badge.className = 'badge badge-danger';
            badge.textContent = 'GPS Tidak Aktif';
        }
        if (desc) {
            desc.textContent = err.message || 'Gagal membaca koordinat GPS perangkat.';
        }
    }

    lucide.createIcons();
}

async function refreshCurrentGPS() {
    const btn = document.getElementById('btn-refresh-gps');
    if (btn) btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Membaca GPS...`;
    await checkCurrentLocationStatus();
    if (btn) btn.innerHTML = `<i data-lucide="refresh-cw"></i> Perbarui Posisi`;
    lucide.createIcons();
    showToast('Posisi GPS diperbarui.', 'success');
}

// Camera Attendance Modal Trigger
async function openAttendanceCameraModal(mode) {
    AppState.currentAttendanceMode = mode;
    AppState.capturedPhotoData = null;

    const modal = document.getElementById('modal-camera-attendance');
    const title = document.getElementById('modal-camera-title');
    const sub = document.getElementById('modal-camera-subtitle');
    const video = document.getElementById('camera-video-feed');
    const imgPreview = document.getElementById('camera-captured-preview');
    const btnSnap = document.getElementById('btn-snap-photo');
    const btnRetake = document.getElementById('btn-retake-photo');
    const btnConfirm = document.getElementById('btn-confirm-attendance');
    const guideOverlay = document.getElementById('camera-guide-overlay');

    title.textContent = mode === 'clock_in' ? 'Presensi Masuk PKL' : 'Presensi Pulang PKL';
    sub.textContent = mode === 'clock_in' ? 'Ambil foto selfie di lingkungan kantor PTPN IV' : 'Ambil foto selfie saat jam pulang kerja';

    video.classList.remove('hidden');
    imgPreview.classList.add('hidden');
    guideOverlay.classList.remove('hidden');
    btnSnap.classList.remove('hidden');
    btnRetake.classList.add('hidden');
    btnConfirm.disabled = true;

    modal.classList.remove('hidden');

    // Update GPS status in modal
    await updateCameraModalGPS();

    // Start Camera Stream
    try {
        await CameraEngine.startCamera(video, 'user');
    } catch (err) {
        showToast(err.message || 'Gagal mengakses kamera.', 'error');
    }

    lucide.createIcons();
}

async function updateCameraModalGPS() {
    const distEl = document.getElementById('modal-camera-distance');
    const badgeEl = document.getElementById('modal-camera-status-badge');

    try {
        const pos = await GPSEngine.getCurrentPosition();
        AppState.currentGPS = pos;
        const targetLat = parseFloat(AppState.officeSettings.latitude);
        const targetLon = parseFloat(AppState.officeSettings.longitude);
        const maxRadius = parseInt(AppState.officeSettings.radius_meters);
        const check = GPSEngine.checkRadius(targetLat, targetLon, maxRadius);

        distEl.textContent = `${check.distance} Meter`;
        if (check.isInside) {
            badgeEl.className = 'badge badge-success';
            badgeEl.textContent = 'Dalam Radius ✅';
        } else {
            badgeEl.className = 'badge badge-danger';
            badgeEl.textContent = `Luar Radius (> ${maxRadius}m) ❌`;
        }
    } catch (err) {
        distEl.textContent = 'GPS Error';
        badgeEl.className = 'badge badge-danger';
        badgeEl.textContent = 'Izin Lokasi Ditolak';
    }
}

function takeSnapshot() {
    const video = document.getElementById('camera-video-feed');
    const imgPreview = document.getElementById('camera-captured-preview');
    const btnSnap = document.getElementById('btn-snap-photo');
    const btnRetake = document.getElementById('btn-retake-photo');
    const btnConfirm = document.getElementById('btn-confirm-attendance');
    const guideOverlay = document.getElementById('camera-guide-overlay');

    try {
        const photoData = CameraEngine.captureSnapshot(video);
        AppState.capturedPhotoData = photoData;

        imgPreview.src = photoData;
        imgPreview.classList.remove('hidden');
        video.classList.add('hidden');
        guideOverlay.classList.add('hidden');

        btnSnap.classList.add('hidden');
        btnRetake.classList.remove('hidden');
        btnConfirm.disabled = false;
        showToast('Foto selfie berhasil diambil!', 'success');
    } catch (err) {
        showToast(err.message || 'Gagal mengambil foto.', 'error');
    }

    lucide.createIcons();
}

function retakeSnapshot() {
    const video = document.getElementById('camera-video-feed');
    const imgPreview = document.getElementById('camera-captured-preview');
    const btnSnap = document.getElementById('btn-snap-photo');
    const btnRetake = document.getElementById('btn-retake-photo');
    const btnConfirm = document.getElementById('btn-confirm-attendance');
    const guideOverlay = document.getElementById('camera-guide-overlay');

    AppState.capturedPhotoData = null;
    imgPreview.classList.add('hidden');
    video.classList.remove('hidden');
    guideOverlay.classList.remove('hidden');

    btnSnap.classList.remove('hidden');
    btnRetake.classList.add('hidden');
    btnConfirm.disabled = true;

    CameraEngine.startCamera(video);
    lucide.createIcons();
}

async function toggleCameraFacing() {
    const video = document.getElementById('camera-video-feed');
    try {
        await CameraEngine.toggleCamera(video);
        showToast('Kamera berhasil dialihkan.', 'success');
    } catch (err) {
        showToast('Gagal beralih kamera.', 'error');
    }
}

function closeAttendanceCameraModal() {
    CameraEngine.stopCamera();
    document.getElementById('modal-camera-attendance').classList.add('hidden');
}

// Submit Attendance Payload
async function submitAttendance() {
    if (!AppState.capturedPhotoData) {
        showToast('Silakan ambil foto selfie terlebih dahulu.', 'warning');
        return;
    }

    if (!AppState.currentGPS) {
        showToast('Koordinat GPS belum terdeteksi. Silakan periksa izin lokasi.', 'error');
        return;
    }

    const btn = document.getElementById('btn-confirm-attendance');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Mengirim Presensi...`;

    const notes = document.getElementById('attendance-notes').value.trim();

    try {
        const payload = {
            action: AppState.currentAttendanceMode,
            latitude: AppState.currentGPS.lat,
            longitude: AppState.currentGPS.lon,
            photo: AppState.capturedPhotoData,
            notes: notes
        };

        const res = await fetch('api/attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeAttendanceCameraModal();
            await checkAuthSession();
        } else {
            showToast(data.message || 'Presensi gagal.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan pengiriman data.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="check-circle"></i> Kirim Presensi`;
        lucide.createIcons();
    }
}

// Load PKL Monthly History
async function loadPKLHistory() {
    const month = document.getElementById('select-history-month').value;
    const year = document.getElementById('select-history-year').value;
    const tbody = document.getElementById('tbody-pkl-history');

    try {
        const res = await fetch(`api/attendance.php?action=history&month=${month}&year=${year}`);
        const result = await res.json();

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(row => {
                let badgeClass = 'badge-success';
                let statusLabel = 'Hadir Tepat Waktu';

                if (row.status === 'terlambat') {
                    badgeClass = 'badge-warning';
                    statusLabel = 'Terlambat';
                } else if (row.status === 'pulang_cepat') {
                    badgeClass = 'badge-purple';
                    statusLabel = 'Pulang Cepat';
                } else if (row.status === 'izin') {
                    badgeClass = 'badge-info';
                    statusLabel = 'Izin';
                } else if (row.status === 'sakit') {
                    badgeClass = 'badge-danger';
                    statusLabel = 'Sakit';
                }

                const photoIn = row.photo_in ? `<img src="${row.photo_in}" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover; cursor: pointer;" onclick="previewImage('${row.photo_in}')" alt="Foto">` : '-';

                return `
                    <tr>
                        <td><strong>${row.attendance_date}</strong></td>
                        <td>${row.time_in ? row.time_in.substring(0, 5) + ' WIB' : '-'}</td>
                        <td>${row.time_out ? row.time_out.substring(0, 5) + ' WIB' : '-'}</td>
                        <td>${row.distance_in_meters ? row.distance_in_meters + ' m' : '-'}</td>
                        <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                        <td>${photoIn}</td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada catatan presensi di bulan ini.</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #ef4444; padding: 2rem;">Gagal memuat riwayat presensi.</td></tr>`;
    }
}

// Student Map Modal
function toggleMapModal(show) {
    const modal = document.getElementById('modal-map-view');
    if (show) {
        modal.classList.remove('hidden');
        setTimeout(initStudentMap, 200);
    } else {
        modal.classList.add('hidden');
    }
}

function initStudentMap() {
    const targetLat = parseFloat(AppState.officeSettings.latitude);
    const targetLon = parseFloat(AppState.officeSettings.longitude);
    const maxRadius = parseInt(AppState.officeSettings.radius_meters);

    if (!AppState.studentMap) {
        AppState.studentMap = L.map('student-map').setView([targetLat, targetLon], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(AppState.studentMap);

        // Office Marker
        L.marker([targetLat, targetLon])
            .addTo(AppState.studentMap)
            .bindPopup(`<b>${AppState.officeSettings.office_name}</b><br>Radius: ${maxRadius}m`)
            .openPopup();

        // Office Radius Circle
        AppState.studentCircle = L.circle([targetLat, targetLon], {
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.2,
            radius: maxRadius
        }).addTo(AppState.studentMap);
    } else {
        AppState.studentMap.invalidateSize();
    }

    // Add user marker if GPS is active
    if (AppState.currentGPS) {
        const uLat = AppState.currentGPS.lat;
        const uLon = AppState.currentGPS.lon;

        if (AppState.studentMarker) {
            AppState.studentMarker.setLatLng([uLat, uLon]);
        } else {
            AppState.studentMarker = L.circleMarker([uLat, uLon], {
                radius: 8,
                fillColor: '#0284c7',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(AppState.studentMap).bindPopup('<b>Posisi Anda Saat Ini</b>');
        }

        const group = new L.featureGroup([
            L.marker([targetLat, targetLon]),
            AppState.studentMarker
        ]);
        AppState.studentMap.fitBounds(group.getBounds().pad(0.2));
    }
}

// Student Digital ID Card
function openDigitalCardModal() {
    const user = AppState.currentUser;
    if (!user) return;

    document.getElementById('card-full-name').textContent = user.full_name;
    document.getElementById('card-identifier-no').textContent = `NIS/NIM: ${user.identifier_no}`;
    document.getElementById('card-institution').textContent = user.institution || '-';
    document.getElementById('card-major').textContent = user.major || '-';
    document.getElementById('card-division').textContent = user.division || '-';
    
    const cardImg = document.getElementById('card-avatar-img');
    const cardText = document.getElementById('card-avatar-text');
    if (cardImg && cardText) {
        if (user.avatar) {
            cardImg.src = user.avatar;
            cardImg.classList.remove('hidden');
            cardText.classList.add('hidden');
        } else {
            cardImg.classList.add('hidden');
            cardText.classList.remove('hidden');
            cardText.textContent = user.full_name.substring(0, 2).toUpperCase();
        }
    }

    const pStart = user.period_start || '-';
    const pEnd = user.period_end || '-';
    document.getElementById('card-period').textContent = `${pStart} s/d ${pEnd}`;

    document.getElementById('modal-digital-card').classList.remove('hidden');
}

function closeDigitalCardModal() {
    document.getElementById('modal-digital-card').classList.add('hidden');
}

// ================= PROFILE SETTINGS & PASSWORD MODAL =================
function openProfileSettingsModal() {
    const user = AppState.currentUser;
    if (!user) return;

    document.getElementById('form-profile-settings').reset();
    document.getElementById('edit-info-name').textContent = user.full_name;
    document.getElementById('edit-info-nim').textContent = user.identifier_no;
    document.getElementById('edit-info-inst').textContent = `${user.institution || '-'} (${user.major || '-'})`;
    document.getElementById('edit-profile-phone').value = user.phone || '';
    document.getElementById('edit-profile-email').value = user.email || '';

    const previewImg = document.getElementById('edit-profile-avatar-preview');
    const fallbackText = document.getElementById('edit-profile-avatar-fallback');

    if (user.avatar) {
        previewImg.src = user.avatar;
        previewImg.classList.remove('hidden');
        fallbackText.classList.add('hidden');
    } else {
        previewImg.classList.add('hidden');
        fallbackText.classList.remove('hidden');
        fallbackText.textContent = user.full_name.substring(0, 2).toUpperCase();
    }

    document.getElementById('modal-profile-settings').classList.remove('hidden');
    lucide.createIcons();
}
window.openProfileSettingsModal = openProfileSettingsModal;

function closeProfileSettingsModal() {
    document.getElementById('modal-profile-settings').classList.add('hidden');
}
window.closeProfileSettingsModal = closeProfileSettingsModal;

function previewSelectedAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewImg = document.getElementById('edit-profile-avatar-preview');
            const fallbackText = document.getElementById('edit-profile-avatar-fallback');
            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');
            fallbackText.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
window.previewSelectedAvatar = previewSelectedAvatar;

async function saveProfileSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-profile');
    const formData = new FormData();

    formData.append('phone', document.getElementById('edit-profile-phone').value.trim());
    formData.append('email', document.getElementById('edit-profile-email').value.trim());
    
    const currentPass = document.getElementById('edit-current-password').value;
    const newPass = document.getElementById('edit-new-password').value;
    const confirmPass = document.getElementById('edit-confirm-password').value;

    if (newPass) {
        if (!currentPass) {
            showToast('Kata sandi saat ini wajib diisi jika ingin mengganti kata sandi.', 'warning');
            return;
        }
        if (newPass.length < 6) {
            showToast('Kata sandi baru minimal 6 karakter.', 'warning');
            return;
        }
        if (newPass !== confirmPass) {
            showToast('Konfirmasi kata sandi baru tidak cocok.', 'error');
            return;
        }
        formData.append('current_password', currentPass);
        formData.append('new_password', newPass);
        formData.append('confirm_password', confirmPass);
    }

    const avatarFile = document.getElementById('edit-profile-avatar-input').files[0];
    if (avatarFile) {
        formData.append('avatar', avatarFile);
    }

    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Menyimpan...`;

    try {
        const res = await fetch('api/auth.php?action=update_profile', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            if (data.user) {
                AppState.currentUser = data.user;
            }
            closeProfileSettingsModal();
            if (AppState.currentUser.role === 'pkl') {
                initPKLDashboard();
            }
        } else {
            showToast(data.message || 'Gagal memperbarui profil.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan koneksi server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="save"></i> Simpan Perubahan`;
        lucide.createIcons();
    }
}
window.saveProfileSettings = saveProfileSettings;

// Leave Modal Actions
function openLeaveModal() {
    document.getElementById('form-submit-leave').reset();
    document.getElementById('modal-leave').classList.remove('hidden');
}

function closeLeaveModal() {
    document.getElementById('modal-leave').classList.add('hidden');
}

async function submitLeaveRequest(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-leave');
    const form = document.getElementById('form-submit-leave');
    const formData = new FormData();

    formData.append('action', 'submit_leave');
    formData.append('leave_type', document.getElementById('leave-type').value);
    formData.append('start_date', document.getElementById('leave-start-date').value);
    formData.append('end_date', document.getElementById('leave-end-date').value);
    formData.append('reason', document.getElementById('leave-reason').value.trim());

    const fileInput = document.getElementById('leave-proof-file');
    if (fileInput.files.length > 0) {
        formData.append('proof_file', fileInput.files[0]);
    }

    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Mengirim...`;

    try {
        const res = await fetch('api/leave.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeLeaveModal();
        } else {
            showToast(data.message || 'Pengajuan gagal.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan pengiriman berkas.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="send"></i> Ajukan Permohonan`;
        lucide.createIcons();
    }
}

function openHistoryTab() {
    document.getElementById('section-pkl-history').scrollIntoView({ behavior: 'smooth' });
}

// ====================================================================
// ========================= ADMIN SDM & PEMBIMBING ===================
// ====================================================================

function initAdminDashboard() {
    // Default to current date for filter
    const todayStr = new Date().toISOString().split('T')[0];
    document.getElementById('admin-filter-date').value = todayStr;
    document.getElementById('report-month').value = new Date().getMonth() + 1;
    document.getElementById('report-year').value = new Date().getFullYear();

    switchAdminTab('monitoring');
}

function switchAdminTab(tabName) {
    const tabs = ['monitoring', 'users', 'location', 'leaves', 'report'];
    tabs.forEach(t => {
        const content = document.getElementById(`admin-tab-content-${t}`) || document.getElementById(`admin-tab-${t}`);
        const btn = document.getElementById(`tab-btn-${t}`);
        if (content) content.classList.add('hidden');
        if (btn) {
            btn.className = 'btn btn-sm btn-secondary tab-admin-btn';
        }
    });

    const activeContent = document.getElementById(`admin-tab-${tabName}`);
    const activeBtn = document.getElementById(`tab-btn-${tabName}`);
    if (activeContent) activeContent.classList.remove('hidden');
    if (activeBtn) activeBtn.className = 'btn btn-sm btn-primary tab-admin-btn';

    if (tabName === 'monitoring') loadAdminMonitoring();
    if (tabName === 'users') loadAdminUsers();
    if (tabName === 'location') initAdminLocationTab();
    if (tabName === 'leaves') loadAdminLeaves();
    if (tabName === 'report') loadAdminReport();

    lucide.createIcons();
}

// 1. Admin Monitoring Tab
async function loadAdminMonitoring() {
    const date = document.getElementById('admin-filter-date').value;
    const type = document.getElementById('admin-filter-type').value;
    const search = document.getElementById('admin-filter-search').value;
    const tbody = document.getElementById('tbody-admin-monitoring');

    try {
        const res = await fetch(`api/attendance.php?action=admin_monitoring&date=${date}&type=${type}&search=${encodeURIComponent(search)}`);
        const result = await res.json();

        if (result.success) {
            // Update Stat Summary Cards
            const sum = result.summary;
            document.getElementById('stat-total-pkl').textContent = sum.total_pkl;
            document.getElementById('stat-hadir-ontime').textContent = sum.hadir;
            document.getElementById('stat-terlambat').textContent = sum.terlambat;
            document.getElementById('stat-izin-sakit').textContent = sum.izin + sum.sakit;
            document.getElementById('stat-belum-absen').textContent = sum.belum_absen;

            if (result.data.length > 0) {
                tbody.innerHTML = result.data.map(row => {
                    let badgeClass = 'badge-gray';
                    let statusLabel = 'Belum Presensi';

                    if (row.status === 'hadir') {
                        badgeClass = 'badge-success';
                        statusLabel = 'Hadir Tepat Waktu';
                    } else if (row.status === 'terlambat') {
                        badgeClass = 'badge-warning';
                        statusLabel = 'Terlambat';
                    } else if (row.status === 'pulang_cepat') {
                        badgeClass = 'badge-purple';
                        statusLabel = 'Pulang Cepat';
                    } else if (row.status === 'izin' || row.leave_type) {
                        badgeClass = 'badge-info';
                        statusLabel = row.leave_type === 'sakit' ? 'Sakit' : 'Izin';
                    }

                    const timeIn = row.time_in ? row.time_in.substring(0, 5) + ' WIB' : '-';
                    const timeOut = row.time_out ? row.time_out.substring(0, 5) + ' WIB' : '-';
                    const dist = row.distance_in_meters ? `${row.distance_in_meters} m` : '-';
                    const photo = row.photo_in ? `<img src="${row.photo_in}" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover; cursor: pointer;" onclick="previewImage('${row.photo_in}')" alt="Selfie">` : '-';

                    return `
                        <tr>
                            <td>
                                <strong>${row.full_name}</strong>
                                <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">${row.identifier_no} (${row.user_type === 'siswa' ? 'SMK' : 'Mhs'})</span>
                            </td>
                            <td>
                                <div>${row.institution || '-'}</div>
                                <span style="font-size: 0.75rem; color: var(--theme-primary);">${row.division || '-'}</span>
                            </td>
                            <td>${timeIn}</td>
                            <td>${timeOut}</td>
                            <td>${dist}</td>
                            <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                            <td>${photo}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data presensi yang sesuai.</td></tr>`;
            }
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #ef4444; padding: 2rem;">Gagal memuat monitoring harian.</td></tr>`;
    }
}

// 2. Admin Users Management Tab
async function loadAdminUsers() {
    const search = document.getElementById('user-mgmt-search').value;
    const type = document.getElementById('user-mgmt-filter-type').value;
    const tbody = document.getElementById('tbody-admin-users');

    try {
        const res = await fetch(`api/users.php?search=${encodeURIComponent(search)}&type=${type}`);
        const result = await res.json();

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(u => {
                const statusBadge = u.is_active == 1 
                    ? `<span class="badge badge-success">Aktif</span>` 
                    : `<span class="badge badge-danger">Nonaktif</span>`;

                return `
                    <tr>
                        <td>
                            <strong>${u.full_name}</strong>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">NIS/NIM: ${u.identifier_no} | User: @${u.username}</span>
                        </td>
                        <td><span class="badge ${u.user_type === 'siswa' ? 'badge-info' : 'badge-gold'}">${u.user_type === 'siswa' ? 'Siswa SMK' : 'Mahasiswa'}</span></td>
                        <td>
                            <div>${u.institution || '-'}</div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">${u.major || '-'}</span>
                        </td>
                        <td><strong style="color: var(--theme-primary);">${u.division || '-'}</strong></td>
                        <td style="font-size: 0.8rem;">${u.period_start || '-'} s/d ${u.period_end || '-'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; gap: 0.35rem;">
                                <button onclick="openUserModal('edit', ${u.id})" class="btn btn-secondary btn-sm" title="Edit Data">
                                    <i data-lucide="edit-3"></i>
                                </button>
                                <button onclick="toggleUserStatus(${u.id}, ${u.is_active == 1 ? 0 : 1})" class="btn btn-secondary btn-sm" title="${u.is_active == 1 ? 'Nonaktifkan' : 'Aktifkan'}">
                                    <i data-lucide="${u.is_active == 1 ? 'user-x' : 'user-check'}"></i>
                                </button>
                                <button onclick="deleteUserAccount(${u.id}, '${u.full_name}')" class="btn btn-danger btn-sm" title="Hapus Akun">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada akun siswa/mahasiswa PKL.</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #ef4444; padding: 2rem;">Gagal memuat data akun peserta.</td></tr>`;
    }

    lucide.createIcons();
}

function openUserModal(mode, userId = null) {
    const modal = document.getElementById('modal-user-form');
    const title = document.getElementById('modal-user-title');
    const form = document.getElementById('form-user-crud');
    const passLabel = document.getElementById('crud-label-pass');
    form.reset();

    document.getElementById('crud-user-action').value = mode;
    document.getElementById('crud-user-id').value = userId || '';

    if (mode === 'create') {
        title.innerHTML = `<i data-lucide="user-plus" style="display: inline; width: 20px;"></i> Tambah Akun Siswa/Mahasiswa PKL`;
        passLabel.textContent = 'Kata Sandi (Wajib Diisi)';
        document.getElementById('crud-password').required = true;
        document.getElementById('crud-username').disabled = false;
        modal.classList.remove('hidden');
    } else {
        title.innerHTML = `<i data-lucide="edit-3" style="display: inline; width: 20px;"></i> Edit Akun Siswa/Mahasiswa PKL`;
        passLabel.textContent = 'Kata Sandi Baru (Kosongkan jika tidak diubah)';
        document.getElementById('crud-password').required = false;
        document.getElementById('crud-username').disabled = true;

        // Fetch user data
        fetch(`api/users.php?id=${userId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const u = res.data;
                    document.getElementById('crud-full-name').value = u.full_name;
                    document.getElementById('crud-identifier-no').value = u.identifier_no;
                    document.getElementById('crud-user-type').value = u.user_type;
                    document.getElementById('crud-division').value = u.division || '';
                    document.getElementById('crud-institution').value = u.institution || '';
                    document.getElementById('crud-major').value = u.major || '';
                    document.getElementById('crud-phone').value = u.phone || '';
                    document.getElementById('crud-email').value = u.email || '';
                    document.getElementById('crud-period-start').value = u.period_start || '';
                    document.getElementById('crud-period-end').value = u.period_end || '';
                    document.getElementById('crud-username').value = u.username;
                    modal.classList.remove('hidden');
                }
            });
    }

    lucide.createIcons();
}

function closeUserModal() {
    document.getElementById('modal-user-form').classList.add('hidden');
}

async function saveUserData(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-user');
    const mode = document.getElementById('crud-user-action').value;
    const userId = document.getElementById('crud-user-id').value;

    const payload = {
        action: mode,
        id: userId,
        full_name: document.getElementById('crud-full-name').value.trim(),
        identifier_no: document.getElementById('crud-identifier-no').value.trim(),
        user_type: document.getElementById('crud-user-type').value,
        division: document.getElementById('crud-division').value.trim(),
        institution: document.getElementById('crud-institution').value.trim(),
        major: document.getElementById('crud-major').value.trim(),
        phone: document.getElementById('crud-phone').value.trim(),
        email: document.getElementById('crud-email').value.trim(),
        period_start: document.getElementById('crud-period-start').value,
        period_end: document.getElementById('crud-period-end').value,
        username: document.getElementById('crud-username').value.trim(),
        password: document.getElementById('crud-password').value.trim()
    };

    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="spin"></i> Menyimpan...`;

    try {
        const res = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeUserModal();
            loadAdminUsers();
        } else {
            showToast(data.message || 'Gagal menyimpan data.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="save"></i> Simpan Data Akun`;
        lucide.createIcons();
    }
}

async function toggleUserStatus(id, newStatus) {
    try {
        const res = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_status', id, is_active: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            loadAdminUsers();
        }
    } catch (err) {
        showToast('Gagal mengubah status.', 'error');
    }
}

async function deleteUserAccount(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus akun PKL "${name}"? Seluruh data presensi peserta ini akan dihapus.`)) {
        return;
    }

    try {
        const res = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            loadAdminUsers();
        }
    } catch (err) {
        showToast('Gagal menghapus akun.', 'error');
    }
}

// 3. Admin Location & Radius Tab (Leaflet Interactive Geofence Editor)
async function initAdminLocationTab() {
    try {
        const res = await fetch('api/settings.php');
        const result = await res.json();
        if (result.success && result.data) {
            AppState.officeSettings = result.data;
        }
    } catch (err) {
        console.error(err);
    }

    const s = AppState.officeSettings;
    document.getElementById('set-office-name').value = s.office_name;
    document.getElementById('set-address').value = s.address || '';
    document.getElementById('set-latitude').value = s.latitude;
    document.getElementById('set-longitude').value = s.longitude;
    document.getElementById('set-radius-range').value = s.radius_meters;
    document.getElementById('label-radius-val').textContent = `${s.radius_meters} m`;
    document.getElementById('set-work-start').value = s.work_start_time;
    document.getElementById('set-work-late').value = s.work_late_tolerance;
    document.getElementById('set-work-end').value = s.work_end_time;

    setTimeout(renderAdminLeafletMap, 200);
}

function renderAdminLeafletMap() {
    const lat = parseFloat(document.getElementById('set-latitude').value) || 0.528383;
    const lon = parseFloat(document.getElementById('set-longitude').value) || 101.447781;
    const radius = parseInt(document.getElementById('set-radius-range').value) || 200;

    if (!AppState.adminMap) {
        AppState.adminMap = L.map('admin-map').setView([lat, lon], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(AppState.adminMap);

        // Draggable Pin Marker
        AppState.adminMarker = L.marker([lat, lon], { draggable: true }).addTo(AppState.adminMap);
        AppState.adminMarker.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            document.getElementById('set-latitude').value = pos.lat.toFixed(6);
            document.getElementById('set-longitude').value = pos.lng.toFixed(6);
            updateAdminMapCircle();
        });

        // Click on map to place marker
        AppState.adminMap.on('click', (e) => {
            AppState.adminMarker.setLatLng(e.latlng);
            document.getElementById('set-latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('set-longitude').value = e.latlng.lng.toFixed(6);
            updateAdminMapCircle();
        });

        // Radius Visual Circle
        AppState.adminCircle = L.circle([lat, lon], {
            color: '#0b6e4f',
            fillColor: '#10b981',
            fillOpacity: 0.25,
            radius: radius
        }).addTo(AppState.adminMap);
    } else {
        AppState.adminMap.invalidateSize();
        AppState.adminMarker.setLatLng([lat, lon]);
        AppState.adminCircle.setLatLng([lat, lon]);
        AppState.adminCircle.setRadius(radius);
        AppState.adminMap.setView([lat, lon], 16);
    }
}

function updateAdminMapCircle() {
    const lat = parseFloat(document.getElementById('set-latitude').value) || 0.528383;
    const lon = parseFloat(document.getElementById('set-longitude').value) || 101.447781;
    const radius = parseInt(document.getElementById('set-radius-range').value) || 200;

    if (AppState.adminMarker) AppState.adminMarker.setLatLng([lat, lon]);
    if (AppState.adminCircle) {
        AppState.adminCircle.setLatLng([lat, lon]);
        AppState.adminCircle.setRadius(radius);
    }
    if (AppState.adminMap) AppState.adminMap.panTo([lat, lon]);
}

function onRadiusSliderChange(val) {
    document.getElementById('label-radius-val').textContent = `${val} m`;
    updateAdminMapCircle();
}

async function setCurrentLocationAsOffice() {
    try {
        const pos = await GPSEngine.getCurrentPosition();
        document.getElementById('set-latitude').value = pos.lat.toFixed(6);
        document.getElementById('set-longitude').value = pos.lon.toFixed(6);
        updateAdminMapCircle();
        showToast('Koordinat GPS terkini berhasil disalin ke parameter kantor!', 'success');
    } catch (err) {
        showToast(err.message || 'Gagal mendeteksi GPS.', 'error');
    }
}

async function saveLocationSettings(e) {
    e.preventDefault();
    const payload = {
        office_name: document.getElementById('set-office-name').value.trim(),
        address: document.getElementById('set-address').value.trim(),
        latitude: parseFloat(document.getElementById('set-latitude').value),
        longitude: parseFloat(document.getElementById('set-longitude').value),
        radius_meters: parseInt(document.getElementById('set-radius-range').value),
        work_start_time: document.getElementById('set-work-start').value,
        work_late_tolerance: document.getElementById('set-work-late').value,
        work_end_time: document.getElementById('set-work-end').value
    };

    try {
        const res = await fetch('api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            AppState.officeSettings = data.data;
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Gagal menyimpan.', 'error');
        }
    } catch (err) {
        showToast('Kesalahan server saat menyimpan pengaturan.', 'error');
    }
}

// 4. Admin Leaves Management Tab
async function loadAdminLeaves() {
    const status = document.getElementById('admin-leave-filter-status').value;
    const tbody = document.getElementById('tbody-admin-leaves');

    try {
        const res = await fetch(`api/leave.php?status=${status}`);
        const result = await res.json();

        // Update badge count
        const badgePending = document.getElementById('badge-admin-leave-count');
        if (badgePending) {
            if (result.pending_count > 0) {
                badgePending.textContent = result.pending_count;
                badgePending.classList.remove('hidden');
            } else {
                badgePending.classList.add('hidden');
            }
        }

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(l => {
                let badgeClass = 'badge-warning';
                let statusLabel = 'Menunggu Verifikasi';

                if (l.status === 'approved') {
                    badgeClass = 'badge-success';
                    statusLabel = 'Disetujui';
                } else if (l.status === 'rejected') {
                    badgeClass = 'badge-danger';
                    statusLabel = 'Ditolak';
                }

                const typeNames = {
                    sakit: 'Sakit',
                    izin_akademik: 'Izin Kampus/Sekolah',
                    izin_pribadi: 'Izin Pribadi',
                    dispensasi: 'Dispensasi',
                    lainnya: 'Lainnya'
                };

                const proofBtn = l.proof_file 
                    ? `<a href="${l.proof_file}" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;"><i data-lucide="external-link"></i> Lihat Bukti</a>`
                    : '<span style="color: var(--text-muted); font-size: 0.75rem;">Tidak Ada</span>';

                const actionBtn = l.status === 'pending'
                    ? `<button onclick='openReviewLeaveModal(${JSON.stringify(l)})' class="btn btn-primary btn-sm"><i data-lucide="check-square"></i> Verifikasi</button>`
                    : `<span style="font-size: 0.75rem; color: var(--text-muted);">Selesai (${l.reviewer_name || 'Admin'})</span>`;

                return `
                    <tr>
                        <td>
                            <strong>${l.full_name}</strong>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">${l.identifier_no} • ${l.institution}</span>
                        </td>
                        <td><span class="badge badge-info">${typeNames[l.leave_type] || l.leave_type}</span></td>
                        <td><strong>${l.start_date}</strong> s/d <strong>${l.end_date}</strong></td>
                        <td style="max-width: 200px; font-size: 0.85rem;">${l.reason}</td>
                        <td>${proofBtn}</td>
                        <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data permohonan izin.</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #ef4444; padding: 2rem;">Gagal memuat daftar izin.</td></tr>`;
    }

    lucide.createIcons();
}

function openReviewLeaveModal(leave) {
    document.getElementById('review-leave-id').value = leave.id;
    document.getElementById('review-applicant-name').textContent = leave.full_name;
    document.getElementById('review-applicant-meta').textContent = `NIS/NIM: ${leave.identifier_no} • ${leave.institution} (${leave.division || '-'})`;
    document.getElementById('review-leave-type').textContent = leave.leave_type.toUpperCase();
    document.getElementById('review-leave-dates').textContent = `${leave.start_date} s/d ${leave.end_date}`;
    document.getElementById('review-leave-reason').textContent = `Alasan: "${leave.reason}"`;
    document.getElementById('review-admin-notes').value = '';

    const proofBox = document.getElementById('review-proof-container');
    const proofContent = document.getElementById('review-proof-content');

    if (leave.proof_file) {
        proofBox.classList.remove('hidden');
        if (leave.proof_file.endsWith('.pdf')) {
            proofContent.innerHTML = `<a href="${leave.proof_file}" target="_blank" class="btn btn-secondary btn-sm"><i data-lucide="file-text"></i> Buka Lampiran PDF</a>`;
        } else {
            proofContent.innerHTML = `<img src="${leave.proof_file}" style="max-width: 100%; max-height: 220px; border-radius: 8px; border: 1px solid var(--border-color);" alt="Lampiran">`;
        }
    } else {
        proofBox.classList.add('hidden');
    }

    document.getElementById('modal-review-leave').classList.remove('hidden');
    lucide.createIcons();
}

function closeReviewLeaveModal() {
    document.getElementById('modal-review-leave').classList.add('hidden');
}

async function submitReviewLeave(newStatus) {
    const leaveId = document.getElementById('review-leave-id').value;
    const adminNotes = document.getElementById('review-admin-notes').value.trim();

    try {
        const res = await fetch('api/leave.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'review_leave',
                leave_id: leaveId,
                status: newStatus,
                admin_notes: adminNotes
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeReviewLeaveModal();
            loadAdminLeaves();
        } else {
            showToast(data.message || 'Gagal memproses review.', 'error');
        }
    } catch (err) {
        showToast('Kesalahan server.', 'error');
    }
}

// 5. Admin Report & Recap Tab
async function loadAdminReport() {
    const month = document.getElementById('report-month').value;
    const year = document.getElementById('report-year').value;
    const inst = document.getElementById('report-filter-inst').value;
    const div = document.getElementById('report-filter-div').value;
    const tbody = document.getElementById('tbody-admin-report');

    try {
        const res = await fetch(`api/report.php?month=${month}&year=${year}&institution=${encodeURIComponent(inst)}&division=${encodeURIComponent(div)}`);
        const result = await res.json();

        if (result.success && result.data.length > 0) {
            let no = 1;
            tbody.innerHTML = result.data.map(r => {
                return `
                    <tr>
                        <td>${no++}</td>
                        <td><strong>${r.full_name}</strong></td>
                        <td>${r.identifier_no}</td>
                        <td>${r.institution || '-'}</td>
                        <td>${r.division || '-'}</td>
                        <td><span class="badge badge-success">${r.total_hadir || 0}</span></td>
                        <td><span class="badge badge-warning">${r.total_terlambat || 0}</span></td>
                        <td><span class="badge badge-info">${r.total_izin || 0}</span></td>
                        <td><span class="badge badge-danger">${r.total_sakit || 0}</span></td>
                        <td><span class="badge badge-gray">${r.total_alpha || 0}</span></td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data rekapitulasi di periode ini.</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: #ef4444; padding: 2rem;">Gagal memuat rekapitulasi.</td></tr>`;
    }
}

function exportReportCSV() {
    const month = document.getElementById('report-month').value;
    const year = document.getElementById('report-year').value;
    const inst = document.getElementById('report-filter-inst').value;
    const div = document.getElementById('report-filter-div').value;
    window.location.href = `api/report.php?format=csv&month=${month}&year=${year}&institution=${encodeURIComponent(inst)}&division=${encodeURIComponent(div)}`;
}

// Lightbox Photo preview
function previewImage(src) {
    const w = window.open('');
    w.document.write(`<title>Foto Presensi Selfie</title><body style="margin:0;background:#000;display:flex;align-items:center;justify-content:center;height:100vh;"><img src="${src}" style="max-width:95vw;max-height:95vh;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.8);"></body>`);
}
