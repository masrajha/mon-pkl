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
    initLocationSuggestionInputs();

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
    const routeLayer = L.layerGroup().addTo(map);
    const data = await fetchJson(element.dataset.dataUrl);
    const markerByPlaceId = new Map();
    const featureByPlaceId = new Map();
    const tableBody = element.dataset.tableTarget ? document.getElementById(element.dataset.tableTarget) : null;
    const routeTableBody = element.dataset.routeTableTarget ? document.getElementById(element.dataset.routeTableTarget) : null;

    const selectPlace = (placeId, options = {}) => {
        const marker = markerByPlaceId.get(Number(placeId));
        const feature = featureByPlaceId.get(Number(placeId));

        if (!marker || !feature) {
            return;
        }

        highlightPlaceRow(tableBody, placeId);

        if (options.scroll !== false) {
            scrollPlaceRowIntoView(tableBody, placeId);
        }

        if (options.focusMap !== false) {
            const [lng, lat] = feature.geometry.coordinates;
            group.zoomToShowLayer(marker, () => {
                map.setView([lat, lng], Math.max(map.getZoom(), mapConfig(element).officeZoom));
                marker.openPopup();
            });
        }
    };

    data.features.forEach((feature) => {
        const [lng, lat] = feature.geometry.coordinates;
        const props = feature.properties;

        featureByPlaceId.set(Number(props.id), feature);

        const marker = L.marker([lat, lng], { icon: placeIcon(props.visited) })
            .bindPopup(placePopup(props))
            .on('click', () => selectPlace(props.id, { focusMap: false }))
            .addTo(group);

        markerByPlaceId.set(Number(props.id), marker);
    });

    group.addTo(map);
    fitLayer(map, group);

    renderPlacesTable(tableBody, data.features, selectPlace);
    initPlacesRouteControls(element, map, routeLayer, routeTableBody, selectPlace);
}

function initPlacesRouteControls(element, map, routeLayer, routeTableBody, selectPlace) {
    const form = element.dataset.routeForm ? document.getElementById(element.dataset.routeForm) : null;
    const status = element.dataset.routeStatusTarget ? document.getElementById(element.dataset.routeStatusTarget) : null;
    const summary = element.dataset.routeSummaryTarget ? document.getElementById(element.dataset.routeSummaryTarget) : null;
    const currentLocationButton = form?.querySelector('[data-route-current-location]');
    let startPreviewMarker = null;

    if (!form || !element.dataset.routeUrl) {
        return;
    }

    const setStartLocation = (lat, lng, message, focus = false) => {
        form.elements.start_lat.value = lat.toFixed(7);
        form.elements.start_lng.value = lng.toFixed(7);
        routeLayer.clearLayers();

        startPreviewMarker = L.marker([lat, lng], { icon: routeStartIcon() })
            .bindPopup('<strong>Titik awal</strong>')
            .addTo(routeLayer);

        if (focus) {
            map.setView([lat, lng], Math.max(map.getZoom(), mapConfig(element).officeZoom));
            startPreviewMarker.openPopup();
        }

        if (summary) {
            summary.textContent = 'Titik awal siap. Tekan Hitung Rute untuk menyusun kunjungan.';
        }

        setRouteStatus(status, message, 'success');
    };

    const initialLat = Number(form.elements.start_lat.value);
    const initialLng = Number(form.elements.start_lng.value);

    if (isValidLatLng(initialLat, initialLng)) {
        setStartLocation(initialLat, initialLng, 'Titik awal default: Universitas Lampung.');
    }

    map.on('click', (event) => {
        setStartLocation(event.latlng.lat, event.latlng.lng, 'Titik awal dipilih dari peta.');
    });

    currentLocationButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setRouteStatus(status, 'Peramban tidak mendukung geolokasi.', 'error');
            return;
        }

        setRouteStatus(status, 'Mengambil lokasi saat ini...', 'info');
        navigator.geolocation.getCurrentPosition((position) => {
            setStartLocation(position.coords.latitude, position.coords.longitude, 'Lokasi awal terisi.', true);
        }, () => {
            setRouteStatus(status, 'Lokasi saat ini belum dapat diambil.', 'error');
        }, {
            enableHighAccuracy: Boolean(mapConfig(element).geolocation?.enable_high_accuracy ?? true),
            timeout: Number(mapConfig(element).geolocation?.timeout_ms ?? 12000),
            maximumAge: Number(mapConfig(element).geolocation?.maximum_age_ms ?? 30000),
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const startLat = Number(form.elements.start_lat.value);
        const startLng = Number(form.elements.start_lng.value);

        if (!isValidLatLng(startLat, startLng)) {
            setRouteStatus(status, 'Koordinat titik awal belum valid.', 'error');
            return;
        }

        setRouteStatus(status, 'Menghitung rute...', 'info');

        try {
            const route = await fetchJson(routeUrl(element.dataset.routeUrl, startLat, startLng));
            renderPlacesRoute(map, routeLayer, route, startLat, startLng, routeTableBody, summary, selectPlace);
            setRouteStatus(status, route.message || 'Rute berhasil dihitung.', route.fallback ? 'warning' : 'success');
        } catch (error) {
            setRouteStatus(status, 'Rute belum dapat dihitung.', 'error');
        }
    });
}

function routeUrl(baseUrl, startLat, startLng) {
    const url = new URL(baseUrl, window.location.origin);

    url.searchParams.set('start_lat', startLat.toFixed(7));
    url.searchParams.set('start_lng', startLng.toFixed(7));

    return url.toString();
}

function renderPlacesRoute(map, routeLayer, route, startLat, startLng, tableBody, summary, selectPlace) {
    routeLayer.clearLayers();

    const start = L.latLng(startLat, startLng);
    const geometry = Array.isArray(route.geometry) ? route.geometry : [];
    const latLngs = geometry
        .map((coordinate) => Array.isArray(coordinate) && coordinate.length >= 2 ? [Number(coordinate[1]), Number(coordinate[0])] : null)
        .filter((coordinate) => coordinate && isValidLatLng(coordinate[0], coordinate[1]));

    L.marker(start, { icon: routeStartIcon() })
        .bindPopup('<strong>Titik awal</strong>')
        .addTo(routeLayer);

    if (latLngs.length > 1) {
        L.polyline(latLngs, {
            color: route.fallback ? '#d97706' : '#0f766e',
            weight: 4,
            opacity: 0.8,
        }).addTo(routeLayer);
    }

    (route.stops || []).forEach((stop) => {
        L.marker([stop.lat, stop.lng], { icon: routeStopIcon(stop.order) })
            .bindPopup(routeStopPopup(stop))
            .on('click', () => selectPlace(stop.id, { focusMap: false }))
            .addTo(routeLayer);
    });

    fitLayer(map, routeLayer);
    renderPlacesRouteTable(tableBody, route.stops || [], selectPlace);

    if (summary) {
        const distance = formatDistance(route.total_distance_meters);
        const duration = route.total_duration_seconds === null ? '' : ` &middot; ${formatDuration(route.total_duration_seconds)}`;
        const omitted = Number(route.omitted_count || 0) > 0 ? ` &middot; ${Number(route.omitted_count).toLocaleString('id-ID')} mitra tidak masuk batas rute` : '';
        summary.innerHTML = `${distance}${duration}${omitted}`;
    }
}

function renderPlacesRouteTable(tableBody, stops, selectPlace) {
    if (!tableBody) {
        return;
    }

    tableBody.replaceChildren();

    if (stops.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="3" class="silat-table-cell text-center text-gray-500">Tidak ada rute pada filter ini.</td>';
        tableBody.append(row);
        return;
    }

    stops.forEach((stop) => {
        const row = document.createElement('tr');
        const directionsUrl = googleMapsCoordinateUrl(stop.lat, stop.lng);

        row.className = 'cursor-pointer transition hover:bg-teal-50';
        row.innerHTML = `
            <td class="silat-table-cell text-gray-700">${Number(stop.order || 0).toLocaleString('id-ID')}</td>
            <td class="silat-table-cell">
                <div class="flex items-start gap-2">
                    <div class="min-w-0 font-medium text-gray-900">${escapeHtml(stop.name || '-')}</div>
                    <a href="${escapeHtml(directionsUrl)}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-blue-600 transition hover:text-blue-800" title="Buka di Google Maps" aria-label="Buka ${escapeHtml(stop.name || 'mitra')} di Google Maps" data-route-directions>
                        <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="line-clamp-2 text-xs text-gray-500">${escapeHtml(stop.city || stop.address || '-')}</div>
            </td>
            <td class="silat-table-cell text-gray-700">
                <div>${formatDistance(stop.segment_distance_meters)}</div>
                <div class="text-xs text-gray-500">${formatDistance(stop.cumulative_distance_meters)}</div>
            </td>
        `;
        row.querySelector('[data-route-directions]')?.addEventListener('click', (event) => event.stopPropagation());
        row.addEventListener('click', () => selectPlace(stop.id, { scroll: true }));
        tableBody.append(row);
    });
}

function setRouteStatus(element, message, type) {
    if (!element) {
        return;
    }

    element.textContent = message;
    element.className = `text-sm ${type === 'error' ? 'text-red-600' : type === 'warning' ? 'text-amber-700' : type === 'success' ? 'text-green-700' : 'text-gray-500'}`;
}

async function initMonitoringMap(element) {
    const map = baseMap(element);
    const studentGroup = L.markerClusterGroup();
    const officeGroup = L.layerGroup();
    const lineGroup = L.layerGroup();
    const data = await fetchJson(element.dataset.dataUrl);
    const tableBody = element.dataset.tableTarget ? document.getElementById(element.dataset.tableTarget) : null;
    const countTarget = element.dataset.countTarget ? document.getElementById(element.dataset.countTarget) : null;
    const markerByCheckInId = new Map();

    const selectCheckIn = (checkInId, options = {}) => {
        const marker = markerByCheckInId.get(Number(checkInId));

        if (!marker) {
            return;
        }

        highlightMonitoringRow(tableBody, checkInId);

        if (options.scroll !== false) {
            scrollMonitoringRowIntoView(tableBody, checkInId);
        }

        if (options.focusMap !== false) {
            studentGroup.zoomToShowLayer(marker, () => {
                map.setView(marker.getLatLng(), Math.max(map.getZoom(), mapConfig(element).currentLocationZoom));
                marker.openPopup();
            });
        }
    };

    data.check_ins.forEach((checkIn) => {
        const student = checkIn.student_location;
        const office = checkIn.office;

        if (student?.lat && student?.lng) {
            const marker = L.marker([student.lat, student.lng], { icon: checkInIcon(checkIn.type) })
                .bindPopup(checkInPopup(checkIn))
                .on('click', () => selectCheckIn(checkIn.id, { focusMap: false }))
                .addTo(studentGroup);

            markerByCheckInId.set(Number(checkIn.id), marker);
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
        'Lokasi mitra': officeGroup,
        'Garis jarak': lineGroup,
    }, { collapsed: false }).addTo(map);

    fitLayer(map, studentGroup);
    renderMonitoringTable(tableBody, data.check_ins, selectCheckIn);

    if (countTarget) {
        countTarget.textContent = Number(data.check_ins.length || 0).toLocaleString('id-ID');
    }
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
        }).bindPopup(`<strong>${escapeHtml(element.dataset.officeName || 'Mitra')}</strong>`).addTo(layer);
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
    element.addEventListener('monpkl:set-location', (event) => {
        const lat = Number(event.detail?.latitude);
        const lng = Number(event.detail?.longitude);

        if (!isValidLatLng(lat, lng)) {
            return;
        }

        const selected = L.latLng(lat, lng);
        setLocation(selected);
        map.setView(selected, config.officeZoom);
    });
}

function initLocationSuggestionInputs() {
    document.querySelectorAll('[data-location-suggest-url]').forEach((input) => {
        const list = document.createElement('div');
        let timer = null;
        let requestToken = 0;

        list.className = 'monpkl-location-suggestions hidden';
        input.parentElement?.classList.add('relative');
        input.insertAdjacentElement('afterend', list);

        const hide = () => {
            list.classList.add('hidden');
            list.replaceChildren();
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const query = input.value.trim();

            if (query.length < 3) {
                hide();
                return;
            }

            timer = setTimeout(async () => {
                const token = ++requestToken;
                renderLocationSuggestions(list, [{
                    name: 'Mencari lokasi...',
                    address: 'Riwayat internal diperiksa lebih dulu.',
                    source: 'Status',
                    disabled: true,
                }], () => {});

                const suggestions = await locationSuggestions(input, query);

                if (token !== requestToken) {
                    return;
                }

                renderLocationSuggestions(list, suggestions, (suggestion) => {
                    input.value = suggestion.name;
                    selectSuggestedLocation(input, suggestion);
                    hide();
                });
            }, 500);
        });

        input.addEventListener('blur', () => setTimeout(hide, 180));
    });
}

async function locationSuggestions(input, query) {
    const internal = await internalLocationSuggestions(input.dataset.locationSuggestUrl, query);

    if (internal.length > 0) {
        return internal;
    }

    return externalLocationSuggestions(input.dataset.externalLocationSuggestUrl, query);
}

async function internalLocationSuggestions(url, query) {
    if (!url) {
        return [];
    }

    try {
        const separator = url.includes('?') ? '&' : '?';
        const data = await fetchJson(`${url}${separator}q=${encodeURIComponent(query)}`);

        return Array.isArray(data.data) ? data.data : [];
    } catch (error) {
        return [];
    }
}

async function externalLocationSuggestions(urlTemplate, query) {
    if (!urlTemplate) {
        return [];
    }

    try {
        const url = urlTemplate.replace('{query}', encodeURIComponent(query));
        const data = await fetchJson(url);

        return (Array.isArray(data) ? data : []).map((item) => ({
            source: 'Eksternal',
            name: item.name || item.display_name,
            address: item.display_name,
            latitude: Number(item.lat),
            longitude: Number(item.lon),
        })).filter((item) => item.name && isValidLatLng(item.latitude, item.longitude));
    } catch (error) {
        return [];
    }
}

function renderLocationSuggestions(list, suggestions, onSelect) {
    list.replaceChildren();

    if (suggestions.length === 0) {
        suggestions = [{
            name: 'Tidak ada saran lokasi',
            address: 'Pilih titik secara manual dari peta.',
            source: 'Status',
            disabled: true,
        }];
    }

    suggestions.forEach((suggestion) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.disabled = Boolean(suggestion.disabled);
        button.className = 'block w-full px-3 py-2 text-left text-sm transition hover:bg-blue-50 disabled:cursor-default disabled:hover:bg-white';
        button.innerHTML = `
            <span class="block font-medium text-gray-900">${escapeHtml(suggestion.name || '-')}</span>
            <span class="block text-xs text-gray-500">${escapeHtml(suggestion.source || 'Lokasi')} &middot; ${escapeHtml(suggestion.address || '-')}</span>
        `;
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();

            if (!suggestion.disabled) {
                onSelect(suggestion);
            }
        });
        list.append(button);
    });

    list.classList.remove('hidden');
}

function selectSuggestedLocation(input, suggestion) {
    const map = input.dataset.mapTarget ? document.getElementById(input.dataset.mapTarget) : null;
    const address = input.dataset.addressTarget ? document.getElementById(input.dataset.addressTarget) : null;

    if (address && suggestion.address) {
        address.value = suggestion.address;
    }

    map?.dispatchEvent(new CustomEvent('monpkl:set-location', {
        detail: {
            latitude: suggestion.latitude,
            longitude: suggestion.longitude,
        },
    }));
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

function renderPlacesTable(tableBody, features, onSelect) {
    if (!tableBody) {
        return;
    }

    tableBody.replaceChildren();

    if (features.length === 0) {
        const row = document.createElement('tr');
        const colspan = Number(tableBody.dataset.colspan || 3);
        row.innerHTML = `<td colspan="${colspan}" class="silat-table-cell text-center text-gray-500">Tidak ada mitra pada filter ini.</td>`;
        tableBody.append(row);
        return;
    }

    features.forEach((feature) => {
        const props = feature.properties;
        const row = document.createElement('tr');

        row.dataset.placeId = props.id;
        row.className = 'cursor-pointer transition hover:bg-indigo-50';
        const actionCell = props.register_url
            ? `<td class="silat-table-cell text-right"><a href="${escapeHtml(props.register_url)}" class="silat-btn px-3 py-2 text-xs" data-place-action><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Daftar</a></td>`
            : '';

        row.innerHTML = `
            <td class="silat-table-cell">
                <div class="font-medium text-gray-900">${escapeHtml(props.name || '-')}</div>
                <div class="line-clamp-2 text-xs text-gray-500">${escapeHtml(props.address || '-')}</div>
            </td>
            <td class="silat-table-cell text-gray-700">${escapeHtml(props.city || '-')}</td>
            <td class="silat-table-cell text-gray-700">${Number(props.enrollments_count || 0).toLocaleString('id-ID')}</td>
            ${actionCell}
        `;
        row.querySelector('[data-place-action]')?.addEventListener('click', (event) => event.stopPropagation());
        row.addEventListener('click', () => onSelect(props.id, { scroll: false }));
        tableBody.append(row);
    });
}

function renderMonitoringTable(tableBody, checkIns, onSelect) {
    if (!tableBody) {
        return;
    }

    tableBody.replaceChildren();

    if (checkIns.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="3" class="silat-table-cell text-center text-gray-500">Tidak ada check-in pada filter ini.</td>`;
        tableBody.append(row);
        return;
    }

    checkIns.forEach((checkIn) => {
        const row = document.createElement('tr');
        const distance = checkIn.distance_meters === null ? '-' : `${Number(checkIn.distance_meters).toLocaleString('id-ID')} m`;

        row.dataset.checkInId = checkIn.id;
        row.className = 'cursor-pointer transition hover:bg-blue-50';
        row.innerHTML = `
            <td class="silat-table-cell">
                <div class="font-medium text-gray-900">${escapeHtml(checkIn.student?.name || '-')}</div>
                <div class="text-xs text-gray-500">${escapeHtml(checkIn.student?.npm || '-')} &middot; ${escapeHtml(checkIn.place?.name || '-')}</div>
                <div class="text-xs text-gray-500">${escapeHtml(checkIn.period || '-')}</div>
            </td>
            <td class="silat-table-cell">
                <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">${escapeHtml(checkIn.type || '-')}</span>
                <div class="mt-1 text-xs text-gray-500">${formatDate(checkIn.checked_at)}</div>
            </td>
            <td class="silat-table-cell text-gray-700">${distance}</td>
        `;
        row.addEventListener('click', () => onSelect(checkIn.id, { scroll: false }));
        tableBody.append(row);
    });
}

function highlightPlaceRow(tableBody, placeId) {
    if (!tableBody) {
        return;
    }

    tableBody.querySelectorAll('[data-place-id]').forEach((row) => {
        row.classList.toggle('bg-indigo-50', String(row.dataset.placeId) === String(placeId));
        row.classList.toggle('ring-1', String(row.dataset.placeId) === String(placeId));
        row.classList.toggle('ring-inset', String(row.dataset.placeId) === String(placeId));
        row.classList.toggle('ring-indigo-200', String(row.dataset.placeId) === String(placeId));
    });
}

function scrollPlaceRowIntoView(tableBody, placeId) {
    const row = tableBody?.querySelector(`[data-place-id="${CSS.escape(String(placeId))}"]`);
    row?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function highlightMonitoringRow(tableBody, checkInId) {
    if (!tableBody) {
        return;
    }

    tableBody.querySelectorAll('[data-check-in-id]').forEach((row) => {
        row.classList.toggle('bg-blue-50', String(row.dataset.checkInId) === String(checkInId));
        row.classList.toggle('ring-1', String(row.dataset.checkInId) === String(checkInId));
        row.classList.toggle('ring-inset', String(row.dataset.checkInId) === String(checkInId));
        row.classList.toggle('ring-blue-200', String(row.dataset.checkInId) === String(checkInId));
    });
}

function scrollMonitoringRowIntoView(tableBody, checkInId) {
    const row = tableBody?.querySelector(`[data-check-in-id="${CSS.escape(String(checkInId))}"]`);
    row?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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

function routeStartIcon() {
    return L.divIcon({
        className: 'monpkl-route-start-icon',
        html: '<span><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></span>',
        iconSize: [34, 34],
        iconAnchor: [17, 17],
    });
}

function routeStopIcon(order) {
    return L.divIcon({
        className: 'monpkl-route-stop-icon',
        html: `<span>${Number(order || 0).toLocaleString('id-ID')}</span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
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

function routeStopPopup(stop) {
    return `
        <div class="monpkl-popup">
            <strong>${Number(stop.order || 0).toLocaleString('id-ID')}. ${escapeHtml(stop.name || '-')}</strong>
            <span>${escapeHtml(stop.city || '-')}</span>
            <span>${escapeHtml(stop.address || '-')}</span>
            <span>Segmen: ${formatDistance(stop.segment_distance_meters)}</span>
            <span>Total: ${formatDistance(stop.cumulative_distance_meters)}</span>
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

function formatDistance(value) {
    const meters = Number(value || 0);

    if (meters >= 1000) {
        return `${(meters / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} km`;
    }

    return `${meters.toLocaleString('id-ID', { maximumFractionDigits: 0 })} m`;
}

function formatDuration(value) {
    const seconds = Number(value || 0);
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.round((seconds % 3600) / 60);

    if (hours > 0) {
        return `${hours} jam ${minutes} menit`;
    }

    return `${minutes} menit`;
}

function googleMapsCoordinateUrl(lat, lng) {
    return `https://www.google.com/maps/dir/?api=1&destination=${Number(lat).toFixed(7)},${Number(lng).toFixed(7)}`;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
