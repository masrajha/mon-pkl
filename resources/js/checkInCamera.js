document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-check-in-camera]').forEach((element) => {
        initCheckInCamera(element);
    });
});

function initCheckInCamera(element) {
    const video = element.querySelector('[data-camera-video]');
    const canvas = element.querySelector('[data-camera-canvas]');
    const captureInput = document.getElementById(element.dataset.captureInput);
    const startButton = element.querySelector('[data-camera-start]');
    const captureButton = element.querySelector('[data-camera-capture]');
    const retakeButton = element.querySelector('[data-camera-retake]');
    const status = element.querySelector('[data-camera-status]');
    const submitButton = document.querySelector(element.dataset.submitTarget);
    let stream = null;

    const setStatus = (message, type = 'muted') => {
        if (! status) {
            return;
        }

        status.textContent = message;
        status.dataset.state = type;
    };

    const stopCamera = () => {
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const startCamera = async () => {
        if (! navigator.mediaDevices?.getUserMedia) {
            setStatus('Browser belum mendukung akses kamera.', 'error');
            return;
        }

        try {
            stopCamera();
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 960 },
                    height: { ideal: 720 },
                },
                audio: false,
            });
            video.srcObject = stream;
            video.hidden = false;
            canvas.hidden = true;
            captureButton.disabled = false;
            retakeButton.hidden = true;
            captureInput.value = '';
            submitButton?.setAttribute('disabled', 'disabled');
            setStatus('Kamera aktif. Ambil foto saat wajah dan lokasi sudah siap.', 'ready');
        } catch (error) {
            setStatus('Izin kamera belum aktif atau kamera tidak tersedia.', 'error');
        }
    };

    startButton?.addEventListener('click', startCamera);

    captureButton?.addEventListener('click', () => {
        if (! stream) {
            setStatus('Aktifkan kamera terlebih dahulu.', 'error');
            return;
        }

        const settings = stream.getVideoTracks()[0]?.getSettings?.() || {};
        const width = settings.width || video.videoWidth || 640;
        const height = settings.height || video.videoHeight || 480;
        const maxWidth = Number(element.dataset.cameraMaxWidth || 640);
        const ratio = Math.min(1, maxWidth / width);

        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        captureInput.value = canvas.toDataURL('image/jpeg', 0.75);

        video.hidden = true;
        canvas.hidden = false;
        captureButton.disabled = true;
        retakeButton.hidden = false;
        submitButton?.removeAttribute('disabled');
        stopCamera();
        setStatus('Foto realtime sudah diambil dan siap dikirim.', 'ok');
    });

    retakeButton?.addEventListener('click', startCamera);

    window.addEventListener('pagehide', stopCamera);
}
