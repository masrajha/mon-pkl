import './bootstrap';
import './maps/leafletMaps';
import './checkInCamera';
import './browserPermissions';
import './browserNotifications';

import Alpine from 'alpinejs';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-table-search-input]').forEach((input) => {
        let timer = null;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                input.form?.requestSubmit();
            }, 450);
        });
    });
});

window.Alpine = Alpine;

Alpine.start();
