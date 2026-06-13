const config = window.MonPklConfig?.browserNotifications;

const requestJson = async (url, options = {}) => {
    const response = await window.axios({
        url,
        method: options.method || 'get',
        data: options.data || undefined,
    });

    return response.data;
};

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
};

const subscribeForPush = async () => {
    if (!config?.enabled || !config.subscribeUrl || !config.serviceWorkerUrl || !config.vapidPublicKey) {
        return false;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return false;
    }

    if (Notification.permission !== 'granted') {
        return false;
    }

    const registration = await navigator.serviceWorker.register(config.serviceWorkerUrl);
    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey),
        });
    }

    await requestJson(config.subscribeUrl, {
        method: 'post',
        data: {
            ...subscription.toJSON(),
            contentEncoding: 'aes128gcm',
        },
    });

    return true;
};

const showNotification = async (item) => {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    const notification = new Notification(item.title, {
        body: item.body || '',
        tag: `silat-${item.id}`,
        data: { actionUrl: item.action_url },
    });

    notification.onclick = () => {
        window.focus();
        requestJson(config.markReadUrl.replace('__ID__', item.id), { method: 'post' })
            .finally(() => {
                if (item.action_url) {
                    window.location.href = item.action_url;
                }
            });
        notification.close();
    };

    await requestJson(config.markShownUrl.replace('__ID__', item.id), { method: 'post' });
};

const pollNotifications = async () => {
    if (!config?.enabled || !config.pollUrl) {
        return;
    }

    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    try {
        const payload = await requestJson(config.pollUrl);
        for (const item of payload.notifications || []) {
            await showNotification(item);
        }
    } catch (error) {
        // Keep polling quiet; notification failures should not disturb normal workflows.
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (!config?.enabled) {
        return;
    }

    window.SilatBrowserNotifications = {
        subscribeForPush,
    };

    subscribeForPush().catch(() => {});
    pollNotifications();
    window.setInterval(pollNotifications, Math.max(15, config.pollSeconds || 60) * 1000);
});
