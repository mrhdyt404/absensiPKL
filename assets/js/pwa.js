// assets/js/pwa.js - Progressive Web App Handler for Android, iOS & Desktop

let deferredPrompt = null;

// 1. Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./sw.js')
            .then((reg) => {
                console.log('[PWA] Service Worker registered successfully:', reg.scope);
            })
            .catch((err) => {
                console.warn('[PWA] Service Worker registration failed:', err);
            });
    });
}

// 2. Check if app is running in Standalone (Installed) Mode
function isStandalone() {
    return (window.matchMedia('(display-mode: standalone)').matches) || 
           (window.navigator.standalone === true) ||
           document.referrer.includes('android-app://');
}

// 3. Detect device Operating System
function getDeviceOS() {
    const userAgent = (window.navigator.userAgent || window.navigator.vendor || window.opera || '').toLowerCase();
    const isIOS = /iphone|ipad|ipod/.test(userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isAndroid = /android/.test(userAgent);
    return { isIOS, isAndroid };
}

// 4. Listen for Chrome / Android beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show header install button and top banner if available
    const pwaBanner = document.getElementById('pwa-install-banner');
    const btnInstallHeader = document.getElementById('btn-pwa-install-header');
    if (pwaBanner && !isStandalone()) pwaBanner.classList.remove('hidden');
    if (btnInstallHeader && !isStandalone()) btnInstallHeader.classList.remove('hidden');

    // Trigger auto popup on first visit if not dismissed
    checkFirstTimePWA();
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    localStorage.setItem('pwa_prompt_dismissed', '1');
    const pwaBanner = document.getElementById('pwa-install-banner');
    const btnInstall = document.getElementById('btn-pwa-install-header');
    if (pwaBanner) pwaBanner.classList.add('hidden');
    if (btnInstall) btnInstall.classList.add('hidden');
    closePWAGuide(true);
    if (window.showToast) {
        window.showToast('Aplikasi Absensi PKL PTPN IV berhasil diinstal di perangkat Anda!', 'success');
    }
});

// 5. Trigger Native Install Prompt or Show Guide
async function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            console.log('[PWA] User accepted the install prompt');
            localStorage.setItem('pwa_prompt_dismissed', '1');
            closePWAGuide(true);
        }
        deferredPrompt = null;
        const pwaBanner = document.getElementById('pwa-install-banner');
        if (pwaBanner) pwaBanner.classList.add('hidden');
    } else {
        const { isIOS } = getDeviceOS();
        if (isIOS) {
            switchPWATab('ios');
        } else {
            switchPWATab('android');
            const note = document.getElementById('pwa-android-manual-note');
            if (note) {
                note.classList.remove('hidden');
                note.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }
}

// 6. Switch Tab in Modal (Android vs iOS)
function switchPWATab(platform) {
    const iosSection = document.getElementById('pwa-guide-ios');
    const androidSection = document.getElementById('pwa-guide-android');
    const tabAndroid = document.getElementById('pwa-tab-android');
    const tabIOS = document.getElementById('pwa-tab-ios');

    if (platform === 'ios') {
        if (iosSection) iosSection.classList.remove('hidden');
        if (androidSection) androidSection.classList.add('hidden');
        if (tabIOS) tabIOS.classList.add('active');
        if (tabAndroid) tabAndroid.classList.remove('active');
    } else {
        if (iosSection) iosSection.classList.add('hidden');
        if (androidSection) androidSection.classList.remove('hidden');
        if (tabIOS) tabIOS.classList.remove('active');
        if (tabAndroid) tabAndroid.classList.add('active');
    }

    if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
}

// 7. Open Install Popup Modal
function showPWAInstructions(preferredPlatform = null) {
    const modal = document.getElementById('pwa-modal-guide');
    if (!modal) return;

    const { isIOS } = getDeviceOS();
    const targetPlatform = preferredPlatform || (isIOS ? 'ios' : 'android');
    switchPWATab(targetPlatform);

    modal.classList.remove('hidden');

    if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
}

// 8. Close Install Popup Modal
function closePWAGuide(dontShowAgain = false) {
    const modal = document.getElementById('pwa-modal-guide');
    if (modal) modal.classList.add('hidden');
    
    // Check if user chose "Don't show again" or dismissed
    const dontShowCheck = document.getElementById('pwa-dont-show-again');
    if (dontShowAgain || (dontShowCheck && dontShowCheck.checked)) {
        localStorage.setItem('pwa_prompt_dismissed', '1');
    } else {
        // Also remember for the session so it doesn't pop up on every page change
        sessionStorage.setItem('pwa_session_prompt_shown', '1');
    }
}

// 9. Check First Time Access and Show Popup automatically
function checkFirstTimePWA() {
    if (isStandalone()) return; // Already installed, do not show popup
    
    // Check if permanently dismissed or shown in this session
    const isDismissed = localStorage.getItem('pwa_prompt_dismissed');
    const isSessionShown = sessionStorage.getItem('pwa_session_prompt_shown');

    if (!isDismissed && !isSessionShown) {
        sessionStorage.setItem('pwa_session_prompt_shown', '1');
        setTimeout(() => {
            showPWAInstructions();
        }, 1200);
    }
}

// Check on initial page load
document.addEventListener('DOMContentLoaded', () => {
    // Show header install button if not standalone
    const btnInstallHeader = document.getElementById('btn-pwa-install-header');
    if (btnInstallHeader && !isStandalone()) {
        btnInstallHeader.classList.remove('hidden');
    }

    checkFirstTimePWA();
});

window.installPWA = installPWA;
window.showPWAInstructions = showPWAInstructions;
window.closePWAGuide = closePWAGuide;
window.switchPWATab = switchPWATab;
window.getDeviceOS = getDeviceOS;
window.isStandalone = isStandalone;
window.checkFirstTimePWA = checkFirstTimePWA;
