document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-browser-permissions]').forEach((element) => {
        initBrowserPermissions(element);
    });
});

function initBrowserPermissions(element) {
    const locationStatus = element.querySelector('[data-permission-status="geolocation"]');
    const cameraStatus = element.querySelector('[data-permission-status="camera"]');
    const locationButton = element.querySelector('[data-permission-request="geolocation"]');
    const cameraButton = element.querySelector('[data-permission-request="camera"]');

    refreshPermission('geolocation', locationStatus, locationButton);
    refreshPermission('camera', cameraStatus, cameraButton);

    locationButton?.addEventListener('click', () => requestLocation(locationStatus, locationButton));
    cameraButton?.addEventListener('click', () => requestCamera(cameraStatus, cameraButton));
}

async function refreshPermission(name, statusElement, button) {
    if (! statusElement) {
        return;
    }

    if (! navigator.permissions?.query) {
        setPermissionState(statusElement, button, 'prompt');
        return;
    }

    try {
        const permission = await navigator.permissions.query({ name });
        setPermissionState(statusElement, button, permission.state);
        permission.addEventListener('change', () => setPermissionState(statusElement, button, permission.state));
    } catch (error) {
        setPermissionState(statusElement, button, 'prompt');
    }
}

function setPermissionState(statusElement, button, state) {
    const labels = {
        granted: 'Diizinkan',
        denied: 'Ditolak',
        prompt: 'Belum diizinkan',
    };

    statusElement.textContent = labels[state] || 'Belum diketahui';
    statusElement.dataset.state = state;

    if (button) {
        button.hidden = state === 'granted';
    }
}

function requestLocation(statusElement, button) {
    if (! navigator.geolocation) {
        statusElement.textContent = 'Tidak didukung browser';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        () => setPermissionState(statusElement, button, 'granted'),
        () => setPermissionState(statusElement, button, 'denied'),
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
    );
}

async function requestCamera(statusElement, button) {
    if (! navigator.mediaDevices?.getUserMedia) {
        statusElement.textContent = 'Tidak didukung browser';
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        stream.getTracks().forEach((track) => track.stop());
        setPermissionState(statusElement, button, 'granted');
    } catch (error) {
        setPermissionState(statusElement, button, 'denied');
    }
}
