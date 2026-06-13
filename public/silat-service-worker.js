self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = {};
    }

    const title = payload.title || 'SiLAT';
    const options = {
        body: payload.body || '',
        tag: payload.id ? `silat-${payload.id}` : undefined,
        renotify: true,
        data: {
            actionUrl: payload.action_url || '/dashboard',
            notificationId: payload.id || null,
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.actionUrl || '/dashboard';

    event.waitUntil((async () => {
        const clientsList = await clients.matchAll({ type: 'window', includeUncontrolled: true });

        for (const client of clientsList) {
            if ('focus' in client) {
                await client.focus();
                if ('navigate' in client) {
                    return client.navigate(targetUrl);
                }
                return;
            }
        }

        if (clients.openWindow) {
            return clients.openWindow(targetUrl);
        }
    })());
});
