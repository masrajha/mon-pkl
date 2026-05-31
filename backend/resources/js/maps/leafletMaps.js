import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const MAP_CONFIG = window.MonPklConfig?.map || {};
const REGION_CONFIG = window.MonPklConfig?.region || {};
const DEFAULT_CENTER = [
    Number(MAP_CONFIG.center?.lat ?? -5.3971),
    Number(MAP_CONFIG.center?.lng ?? 105.2668),
];
const DEFAULT_ZOOM = Number(MAP_CONFIG.zoom ?? 11);

L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

document.addEventListener('DOMContentLoaded', () => {
    initRegionPickers();

    document.querySelectorAll('[data-map-type]').forEach((element) => {
        if (element.dataset.mapType === 'places') {
            initPlacesMap(element);
        }

        if (element.dataset.mapType === 'monitoring') {
            initMonitoringMap(element);
        }

        if (element.dataset.mapType === 'check-in') {
            initCheckInMap(element);
        }

        if (element.dataset.mapType === 'place-picker') {
            initPlacePickerMap(element);
        }
    });
});

function baseMap(element) {
    const config = mapConfig(element);
    const map = L.map(element, {
        preferCanvas: true,
        zoomControl: true,
    }).setView(config.center, config.zoom);

    L.tileLayer(config.tileUrl, {
        maxZoom: config.maxZoom,
        attribution: config.tileAttribution,
    }).addTo(map);

    return map;
}

function mapConfig(element) {
    const local = element.dataset.mapConfig ? JSON.parse(element.dataset.mapConfig) : {};
    const config = { ...MAP_CONFIG, ...local };
    const center = local.center || MAP_CONFIG.center || {};

    return {
        ...config,
        center: [
            Number(center.lat ?? DEFAULT_CENTER[0]),
            Number(center.lng ?? DEFAULT_CENTER[1]),
        ],
        zoom: Number(config.zoom ?? DEFAULT_ZOOM),
        maxZoom: Number(config.maxZoom ?? 19),
        fitMaxZoom: Number(config.fitMaxZoom ?? 15),
        officeZoom: Number(config.officeZoom ?? 15),
        currentLocationZoom: Number(config.currentLocationZoom ?? 16),
        tileUrl: config.tileUrl,
        tileAttribution: config.tileAttribution,
        geolocation: config.geolocation || {},
    };
}

async function initPlacesMap(element) {
    const map = baseMap(element);
    const group = L.markerClusterGroup();
    const data = await fetchJson(element.dataset.dataUrl);

    data.features.forEach((feature) => {
        const [lng, lat] = feature.geometry.coordinates;
        const props = feature.properties;

        L.marker([lat, lng], { icon: placeIcon(props.visited) })
            .bindPopup(placePopup(props))
            .addTo(group);
    });

    group.addTo(map);
    fitLayer(map, group);
}

async function initMonitoringMap(element) {
    const map = baseMap(element);
    const studentGroup = L.markerClusterGroup();
    const officeGroup = L.layerGroup();
    const lineGroup = L.layerGroup();
    const data = await fetchJson(element.dataset.dataUrl);

    data.check_ins.forEach((checkIn) => {
        const student = checkIn.student_location;
        const office = checkIn.office;

        if (student?.lat && student?.lng) {
            L.marker([student.lat, student.lng], { icon: checkInIcon(checkIn.type) })
                .bindPopup(checkInPopup(checkIn))
                .addTo(studentGroup);
        }

        if (office?.lat && office?.lng) {
            L.circleMarker([office.lat, office.lng], {
                radius: 5,
                weight: 2,
                color: '#2563eb',
                fillColor: '#ffffff',
                fillOpacity: 1,
            }).bindPopup(officePopup(checkIn)).addTo(officeGroup);
        }

        if (student?.lat && student?.lng && office?.lat && office?.lng) {
            L.polyline([[office.lat, office.lng], [student.lat, student.lng]], {
                color: '#64748b',
                weight: 1,
                opacity: 0.35,
            }).addTo(lineGroup);
        }
    });

    lineGroup.addTo(map);
    officeGroup.addTo(map);
    studentGroup.addTo(map);

    L.control.layers(null, {
        'Lokasi mahasiswa': studentGroup,
        'Lokasi instansi': officeGroup,
        'Garis jarak': lineGroup,
    }, { collapsed: false }).addTo(map);

    fitLayer(map, studentGroup);
}

function initCheckInMap(element) {
    const map = baseMap(element);
    const config = mapConfig(element);
    const layer = L.layerGroup().addTo(map);
    const latInput = document.getElementById(element.dataset.latInput);
    const lngInput = document.getElementById(element.dataset.lngInput);
    const officeLat = Number(element.dataset.officeLat);
    const officeLng = Number(element.dataset.officeLng);
    let studentMarker = null;

    if (Number.isFinite(officeLat) && Number.isFinite(officeLng)) {
        L.circleMarker([officeLat, officeLng], {
            radius: 7,
            weight: 2,
            color: '#2563eb',
            fillColor: '#ffffff',
            fillOpacity: 1,
        }).bindPopup(`<strong>${escapeHtml(element.dataset.officeName || 'Tempat PKL')}</strong>`).addTo(layer);
        map.setView([officeLat, officeLng], config.officeZoom);
    }

    if (!navigator.geolocation) {
        return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);

        studentMarker = L.marker([lat, lng], { icon: checkInIcon('Masuk') })
            .bindPopup('Lokasi Anda')
            .addTo(layer);

        if (Number.isFinite(officeLat) && Number.isFinite(officeLng)) {
            L.polyline([[officeLat, officeLng], [lat, lng]], {
                color: '#64748b',
                weight: 2,
                opacity: 0.5,
            }).addTo(layer);
            fitLayer(map, layer);
        } else {
            map.setView([lat, lng], config.currentLocationZoom);
        }
    }, () => {
        if (Number.isFinite(officeLat) && Number.isFinite(officeLng)) {
            map.setView([officeLat, officeLng], config.officeZoom);
        }
    }, {
        enableHighAccuracy: Boolean(config.geolocation?.enable_high_accuracy ?? true),
        timeout: Number(config.geolocation?.timeout_ms ?? 12000),
        maximumAge: Number(config.geolocation?.maximum_age_ms ?? 30000),
    });

    map.on('click', (event) => {
        const { lat, lng } = event.latlng;

        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);

        if (studentMarker) {
            studentMarker.setLatLng(event.latlng);
        } else {
            studentMarker = L.marker(event.latlng, { icon: checkInIcon('Masuk') }).addTo(layer);
        }
    });
}

function initPlacePickerMap(element) {
    const map = baseMap(element);
    const config = mapConfig(element);
    const latInput = document.getElementById(element.dataset.latInput);
    const lngInput = document.getElementById(element.dataset.lngInput);
    const initialLat = parseCoordinate(element.dataset.initialLat);
    const initialLng = parseCoordinate(element.dataset.initialLng);
    const regionPicker = document.querySelector('[data-region-picker]');
    let marker = null;
    let reverseTimer = null;

    const setLocation = (latlng) => {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);

        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', () => setLocation(marker.getLatLng()));
        }

        if (regionPicker) {
            clearTimeout(reverseTimer);
            reverseTimer = setTimeout(() => detectRegionFromCoordinates(regionPicker, latlng), 400);
        }
    };

    if (isValidLatLng(initialLat, initialLng)) {
        const initial = L.latLng(initialLat, initialLng);
        setLocation(initial);
        map.setView(initial, config.officeZoom);
    } else if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const current = L.latLng(position.coords.latitude, position.coords.longitude);
            setLocation(current);
            map.setView(current, config.officeZoom);
        }, () => {
            map.setView(config.center, config.zoom);
        }, {
            enableHighAccuracy: Boolean(config.geolocation?.enable_high_accuracy ?? true),
            timeout: Number(config.geolocation?.timeout_ms ?? 12000),
            maximumAge: Number(config.geolocation?.maximum_age_ms ?? 30000),
        });
    } else {
        map.setView(config.center, config.zoom);
    }

    map.on('click', (event) => setLocation(event.latlng));
}

function initRegionPickers() {
    document.querySelectorAll('[data-region-picker]').forEach(async (element) => {
        const provinceSelect = document.getElementById(element.dataset.provinceSelect);
        const regencySelect = document.getElementById(element.dataset.regencySelect);
        const cityNameInput = document.getElementById(element.dataset.cityNameInput);
        const status = document.getElementById(element.dataset.statusTarget);

        if (!provinceSelect || !regencySelect || !cityNameInput) {
            return;
        }

        try {
            const provinces = await fetchJson(REGION_CONFIG.provinces_url);
            element._provinces = provinces;
            fillSelect(provinceSelect, provinces, 'Pilih provinsi');

            provinceSelect.addEventListener('change', async () => {
                cityNameInput.value = '';
                regencySelect.disabled = true;
                fillSelect(regencySelect, [], 'Memuat kab/kota...');

                if (!provinceSelect.value) {
                    fillSelect(regencySelect, [], 'Pilih provinsi terlebih dahulu');
                    return;
                }

                const regencies = await fetchJson(REGION_CONFIG.regencies_url.replace('{province_id}', provinceSelect.value));
                element._regencies = regencies;
                fillSelect(regencySelect, regencies, 'Pilih kab/kota');
                regencySelect.disabled = false;
            });

            regencySelect.addEventListener('change', () => {
                const selected = regencySelect.selectedOptions[0];
                cityNameInput.value = selected?.dataset.name || '';
                setRegionStatus(status, cityNameInput.value ? `Kab/Kota dipilih: ${cityNameInput.value}` : '');
            });
        } catch (error) {
            setRegionStatus(status, 'Gagal memuat data wilayah. Nama kota masih dapat tersimpan dari data lama.');
        }
    });
}

async function detectRegionFromCoordinates(element, latlng) {
    if (!REGION_CONFIG.reverse_geocode_url) {
        return;
    }

    const provinceSelect = document.getElementById(element.dataset.provinceSelect);
    const regencySelect = document.getElementById(element.dataset.regencySelect);
    const cityNameInput = document.getElementById(element.dataset.cityNameInput);
    const status = document.getElementById(element.dataset.statusTarget);

    setRegionStatus(status, 'Mendeteksi kab/kota dari koordinat...');

    try {
        const url = REGION_CONFIG.reverse_geocode_url
            .replace('{lat}', encodeURIComponent(latlng.lat.toFixed(7)))
            .replace('{lng}', encodeURIComponent(latlng.lng.toFixed(7)))
            .replace('{lon}', encodeURIComponent(latlng.lng.toFixed(7)));
        const data = await fetchJson(url);
        const address = data.address || {};
        const provinceName = address.state || address.region || '';
        const cityName = address.city || address.town || address.municipality || address.county || address.state_district || '';

        if (!cityName) {
            setRegionStatus(status, 'Kab/Kota tidak terdeteksi dari koordinat ini.');
            return;
        }

        cityNameInput.value = normalizeRegionName(cityName);
        setRegionStatus(status, `Terdeteksi: ${cityNameInput.value}`);

        const province = findRegionMatch(element._provinces || [], provinceName);
        if (province && provinceSelect.value !== province.id) {
            provinceSelect.value = province.id;
            provinceSelect.dispatchEvent(new Event('change'));
            await waitFor(() => Array.isArray(element._regencies));
        }

        const regency = findRegionMatch(element._regencies || [], cityNameInput.value);
        if (regency) {
            regencySelect.value = regency.id;
            cityNameInput.value = regency.name;
            setRegionStatus(status, `Terdeteksi: ${regency.name}`);
        }
    } catch (error) {
        setRegionStatus(status, 'Deteksi kab/kota belum berhasil. Pilih manual dari dropdown.');
    }
}

function fillSelect(select, items, placeholder) {
    select.replaceChildren(new Option(placeholder, ''));

    items.forEach((item) => {
        const option = new Option(item.name, item.id);
        option.dataset.name = item.name;
        select.appendChild(option);
    });
}

function findRegionMatch(items, value) {
    const normalized = normalizeRegionName(value);

    if (!normalized) {
        return null;
    }

    return items.find((item) => normalizeRegionName(item.name) === normalized)
        || items.find((item) => normalizeRegionName(item.name).includes(normalized) || normalized.includes(normalizeRegionName(item.name)))
        || null;
}

function normalizeRegionName(value) {
    return String(value || '')
        .toUpperCase()
        .replace(/^KABUPATEN\s+/, 'KAB. ')
        .replace(/^KOTA ADMINISTRASI\s+/, 'KOTA ')
        .replace(/\s+/g, ' ')
        .trim();
}

function setRegionStatus(element, message) {
    if (element && message) {
        element.textContent = message;
    }
}

function waitFor(predicate, timeout = 3000) {
    const startedAt = Date.now();

    return new Promise((resolve) => {
        const tick = () => {
            if (predicate() || Date.now() - startedAt > timeout) {
                resolve();
                return;
            }

            setTimeout(tick, 50);
        };

        tick();
    });
}

function parseCoordinate(value) {
    if (value === undefined || value === null || String(value).trim() === '') {
        return null;
    }

    const coordinate = Number(value);

    return Number.isFinite(coordinate) ? coordinate : null;
}

function isValidLatLng(lat, lng) {
    return Number.isFinite(lat)
        && Number.isFinite(lng)
        && lat >= -90
        && lat <= 90
        && lng >= -180
        && lng <= 180;
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`Map data request failed: ${response.status}`);
    }

    return response.json();
}

function fitLayer(map, layer) {
    const bounds = layer.getBounds?.();
    const config = mapConfig(map.getContainer());

    if (bounds?.isValid()) {
        map.fitBounds(bounds.pad(0.12), { maxZoom: config.fitMaxZoom });
    }
}

function placeIcon(visited) {
    return L.divIcon({
        className: 'monpkl-place-icon',
        html: `<span class="${visited ? 'visited' : ''}"></span>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
    });
}

function checkInIcon(type) {
    const late = String(type).toLowerCase().includes('terlambat') || String(type).toLowerCase().includes('cepat');

    return L.divIcon({
        className: 'monpkl-checkin-icon',
        html: `<span class="${late ? 'warning' : ''}"></span>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });
}

function placePopup(props) {
    return `
        <div class="monpkl-popup">
            <strong>${escapeHtml(props.name)}</strong>
            <span>${escapeHtml(props.city || '-')}</span>
            <span>${escapeHtml(props.address || '-')}</span>
            <span>Peserta: ${Number(props.enrollments_count || 0)}</span>
            ${props.field_supervisor_name ? `<span>Pembimbing: ${escapeHtml(props.field_supervisor_name)}</span>` : ''}
        </div>
    `;
}

function checkInPopup(checkIn) {
    const distance = checkIn.distance_meters === null ? '-' : `${Number(checkIn.distance_meters).toLocaleString('id-ID')} m`;

    return `
        <div class="monpkl-popup">
            <strong>${escapeHtml(checkIn.student?.name || '-')}</strong>
            <span>${escapeHtml(checkIn.student?.npm || '-')}</span>
            <span>${escapeHtml(checkIn.type || '-')} - ${formatDate(checkIn.checked_at)}</span>
            <span>${escapeHtml(checkIn.place?.name || '-')}</span>
            <span>Jarak: ${distance}</span>
            ${checkIn.note ? `<span>Catatan: ${escapeHtml(checkIn.note)}</span>` : ''}
        </div>
    `;
}

function officePopup(checkIn) {
    return `
        <div class="monpkl-popup">
            <strong>${escapeHtml(checkIn.place?.name || '-')}</strong>
            <span>${escapeHtml(checkIn.place?.city || '-')}</span>
            <span>${escapeHtml(checkIn.student?.name || '-')}</span>
        </div>
    `;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
