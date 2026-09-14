// assets/js/gps.js - Geolocation and Leaflet Map Engine

const GPSEngine = {
    currentPos: null,
    watchId: null,

    // Calculate Haversine distance between two coordinates in meters
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Radius of the earth in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    },

    // Get current GPS position with high accuracy
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Perangkat Anda tidak mendukung fitur Geolocation GPS.'));
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            };

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.currentPos = {
                        lat: pos.coords.latitude,
                        lon: pos.coords.longitude,
                        accuracy: Math.round(pos.coords.accuracy)
                    };
                    resolve(this.currentPos);
                },
                (err) => {
                    let errMsg = 'Gagal mendeteksi lokasi GPS.';
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                            errMsg = 'Izin akses lokasi GPS ditolak oleh pengguna. Mohon izinkan lokasi di pengaturan browser.';
                            break;
                        case err.POSITION_UNAVAILABLE:
                            errMsg = 'Informasi lokasi tidak tersedia. Pastikan GPS perangkat aktif.';
                            break;
                        case err.TIMEOUT:
                            errMsg = 'Waktu permintaan lokasi GPS habis. Silakan coba lagi di tempat terbuka.';
                            break;
                    }
                    reject(new Error(errMsg));
                },
                options
            );
        });
    },

    // Check radius validity against target office
    checkRadius(targetLat, targetLon, maxRadiusMeters) {
        if (!this.currentPos) {
            return { error: 'Lokasi belum terdeteksi' };
        }
        const dist = this.calculateDistance(
            this.currentPos.lat, 
            this.currentPos.lon, 
            targetLat, 
            targetLon
        );
        return {
            distance: dist,
            maxRadius: maxRadiusMeters,
            isInside: dist <= maxRadiusMeters,
            lat: this.currentPos.lat,
            lon: this.currentPos.lon,
            accuracy: this.currentPos.accuracy
        };
    }
};

window.GPSEngine = GPSEngine;
