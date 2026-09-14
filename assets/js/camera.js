// assets/js/camera.js - Live Camera Selfie Capture Engine

const CameraEngine = {
    stream: null,
    facingMode: 'user', // 'user' (front) or 'environment' (back)

    // Start camera feed into video element
    async startCamera(videoElement, preferredFacing = 'user') {
        this.facingMode = preferredFacing;
        this.stopCamera();

        const constraints = {
            audio: false,
            video: {
                facingMode: this.facingMode,
                width: { ideal: 640 },
                height: { ideal: 640 }
            }
        };

        try {
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            if (videoElement) {
                videoElement.srcObject = this.stream;
                await videoElement.play();
            }
            return true;
        } catch (err) {
            console.error('Camera access error:', err);
            throw new Error('Gagal mengakses kamera: ' + (err.message || 'Izin kamera ditolak.'));
        }
    },

    // Switch between front and back camera
    async toggleCamera(videoElement) {
        this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
        return await this.startCamera(videoElement, this.facingMode);
    },

    // Capture frame to canvas and return base64 data URL
    captureSnapshot(videoElement) {
        if (!videoElement || !this.stream) {
            throw new Error('Kamera tidak aktif');
        }

        const canvas = document.createElement('canvas');
        const width = videoElement.videoWidth || 480;
        const height = videoElement.videoHeight || 480;
        
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // If front camera, mirror horizontally for natural selfie preview
        if (this.facingMode === 'user') {
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
        }

        ctx.drawImage(videoElement, 0, 0, width, height);

        // Add small timestamp watermark at bottom right
        ctx.setTransform(1, 0, 0, 1, 0, 0); // reset transform
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(10, height - 35, width - 20, 25);
        ctx.font = 'bold 12px sans-serif';
        ctx.fillStyle = '#10b981';
        const now = new Date();
        const stamp = `PTPN IV REG 3 | ${now.toLocaleDateString('id-ID')} ${now.toLocaleTimeString('id-ID')}`;
        ctx.fillText(stamp, 20, height - 18);

        return canvas.toDataURL('image/jpeg', 0.85);
    },

    // Stop camera stream and release hardware
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    }
};

window.CameraEngine = CameraEngine;
