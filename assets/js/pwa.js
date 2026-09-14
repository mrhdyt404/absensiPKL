// assets/js/pwa.js - Progressive Web App Handler for Android and iOS

let deferredPrompt = null;

// Register Service Worker
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

// Android / Desktop Install Prompt
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const pwaBanner = document.getElementById('pwa-install-banner');
    const btnInstall = document.getElementById('btn-pwa-install-header');
    if (pwaBanner) pwaBanner.classList.remove('hidden');
    if (btnInstall) btnInstall.classList.remove('hidden');
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    const pwaBanner = document.getElementById('pwa-install-banner');
    const btnInstall = document.getElementById('btn-pwa-install-header');
    if (pwaBanner) pwaBanner.classList.add('hidden');
    if (btnInstall) btnInstall.classList.add('hidden');
    if (window.showToast) {
        window.showToast('Aplikasi Absensi PKL PTPN IV berhasil diinstal di perangkat Anda!', 'success');
    }
});

// Trigger install prompt for Android
async function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            console.log('[PWA] User accepted the install prompt');
        }
        deferredPrompt = null;
        const pwaBanner = document.getElementById('pwa-install-banner');
        if (pwaBanner) pwaBanner.classList.add('hidden');
    } else {
        // If on iOS or standalone check
        showPWAInstructions();
    }
}

// Detect device OS
function getDeviceOS() {
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIOS = /iphone|ipad|ipod/.test(userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isAndroid = /android/.test(userAgent);
    return { isIOS, isAndroid };
}

// Check if already in standalone mode
function isStandalone() {
    return (window.matchMedia('(display-mode: standalone)').matches) || 
           (window.navigator.standalone === true);
}

// Show installation modal for iOS / others
function showPWAInstructions() {
    const { isIOS } = getDeviceOS();
    const modal = document.getElementById('pwa-modal-guide');
    if (!modal) return;

    const iosSection = document.getElementById('pwa-guide-ios');
    const androidSection = document.getElementById('pwa-guide-android');

    if (isIOS) {
        if (iosSection) iosSection.classList.remove('hidden');
        if (androidSection) androidSection.classList.add('hidden');
    } else {
        if (iosSection) iosSection.classList.add('hidden');
        if (androidSection) androidSection.classList.remove('hidden');
    }

    modal.classList.remove('hidden');
}

function closePWAGuide() {
    const modal = document.getElementById('pwa-modal-guide');
    if (modal) modal.classList.add('hidden');
}

window.installPWA = installPWA;
window.showPWAInstructions = showPWAInstructions;
window.closePWAGuide = closePWAGuide;
window.getDeviceOS = getDeviceOS;
window.isStandalone = isStandalone;
