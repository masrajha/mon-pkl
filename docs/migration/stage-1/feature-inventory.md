# Inventaris Fitur Aktif dan Fitur Lama

## Status Rekomendasi

| Fitur | Status Saat Ini | Keputusan Migrasi |
| --- | --- | --- |
| Login Google Firebase | aktif saat ini | migrasikan ke Laravel Socialite |
| Input tempat PKL | aktif saat ini | migrasikan ke CRUD internship_places |
| Check-in dengan lokasi dan foto | aktif saat ini | migrasikan ke endpoint server dan Laravel Storage |
| Monitoring peta | aktif saat ini | migrasikan ke Leaflet/markercluster |
| Monitoring tabel | aktif saat ini | migrasikan ke query server |
| Laporan/final report | aktif saat ini | migrasikan ke service laporan server |
| Folder public/20191 | arsip periode lama | boleh dihentikan setelah data tervalidasi |
| File coba/test/backup | fitur lama/dev | boleh dihentikan setelah cutover |

## File Terdeteksi

### firebase

- `public/20191/finalreport.html`
- `public/20191/index.html`
- `public/20191/js/auth.js`
- `public/20191/js/coba.js`
- `public/20191/js/finalreport.js`
- `public/20191/js/firebasedata.js`
- `public/20191/js/index-checkin.js`
- `public/20191/js/index.js`
- `public/20191/js/laporan.js`
- `public/20191/js/main-list.js`
- `public/20191/js/main.js`
- `public/20191/js/monitoring.js`
- `public/20191/js/monitoringmap.js`
- `public/20191/laporan.html`
- `public/20191/list-data-kp.html`
- `public/20191/monitoring.html`
- `public/20191/monitoringmap.html`
- `public/404.html`
- `public/catatan-harian.html`
- `public/check-in-camera.html`
- `public/check-in.bak.html`
- `public/check-in.html`
- `public/coba.html`
- `public/finalreport.html`
- `public/index.html`
- `public/input.html`
- `public/js/auth.js`
- `public/js/camera_profile.js`
- `public/js/camera.js`
- `public/js/catatan-harian.js`
- `public/js/coba.js`
- `public/js/finalreport-json.js`
- `public/js/finalreport.js`
- `public/js/firebasedata.js`
- `public/js/index-checkin-cam.js`
- `public/js/index-checkin.js`
- `public/js/index.js`
- `public/js/input.js`
- `public/js/laporan-json.js`
- `public/js/laporan.backup.js`
- `public/js/laporan.js`
- `public/js/main-list.js`
- `public/js/main.js`
- `public/js/monitoring.js`
- `public/js/monitoringmap.js`
- `public/js/profile.js`
- `public/laporan.html`
- `public/list-data-kp.html`
- `public/monitoring-asli.html`
- `public/monitoring.html`
- `public/monitoringmap.html`
- `public/profile.html`
- `public/rule.txt`
- `public/tempat-pkl.html`
- `public/widemap.html`

### google_maps

- `public/20191/finalreport.html`
- `public/20191/index.html`
- `public/20191/js/custommarker.js`
- `public/20191/js/finalreport.js`
- `public/20191/js/index-checkin.js`
- `public/20191/js/index.js`
- `public/20191/js/laporan.js`
- `public/20191/js/main-list.js`
- `public/20191/js/main.js`
- `public/20191/js/markerclusterer.js`
- `public/20191/js/monitoring.js`
- `public/20191/js/monitoringmap.js`
- `public/20191/laporan.html`
- `public/20191/list-data-kp.html`
- `public/20191/monitoring.html`
- `public/20191/monitoringmap.html`
- `public/catatan-harian.html`
- `public/check-in-camera.html`
- `public/check-in.bak.html`
- `public/check-in.html`
- `public/finalreport.html`
- `public/index.html`
- `public/input.html`
- `public/js/catatan-harian.js`
- `public/js/custommarker.js`
- `public/js/finalreport-json.js`
- `public/js/finalreport.js`
- `public/js/index-checkin-cam.js`
- `public/js/index-checkin.js`
- `public/js/index.js`
- `public/js/laporan-json.js`
- `public/js/laporan.backup.js`
- `public/js/laporan.js`
- `public/js/main-list.js`
- `public/js/main.js`
- `public/js/markerclusterer (1).js`
- `public/js/markerclusterer.js`
- `public/js/monitoring.js`
- `public/js/monitoringmap.js`
- `public/laporan.html`
- `public/list-data-kp.html`
- `public/monitoring-asli.html`
- `public/monitoring.html`
- `public/monitoringmap.html`
- `public/tempat-pkl.html`
- `public/widemap.html`

### camera

- `public/20191/js/p5/addons/p5.dom.js`
- `public/20191/js/p5/addons/p5.dom.min.js`
- `public/20191/js/p5/addons/p5.sound.js`
- `public/20191/js/p5/addons/p5.sound.min.js`
- `public/20191/js/p5/p5.js`
- `public/20191/js/p5/p5.min.js`
- `public/20191/js/p5/p5.pre-min.js`
- `public/20191/js/sketch.js`
- `public/check-in-camera.html`
- `public/check-in.html`
- `public/js/camera_profile.js`
- `public/js/camera.js`
- `public/js/p5/addons/p5.dom.js`
- `public/js/p5/addons/p5.dom.min.js`
- `public/js/p5/addons/p5.sound.js`
- `public/js/p5/addons/p5.sound.min.js`
- `public/js/p5/p5.js`
- `public/js/p5/p5.min.js`
- `public/js/p5/p5.pre-min.js`
- `public/js/sketch.js`
- `public/profile.html`

### report

- `public/20191/finalreport.html`
- `public/20191/index.html`
- `public/20191/js/finalreport.js`
- `public/20191/js/index-checkin.js`
- `public/20191/js/laporan.js`
- `public/20191/js/p5/addons/p5.dom.js`
- `public/20191/js/p5/addons/p5.sound.js`
- `public/20191/js/p5/p5.js`
- `public/20191/js/p5/p5.min.js`
- `public/20191/js/p5/p5.pre-min.js`
- `public/20191/laporan.html`
- `public/20191/list-data-kp.html`
- `public/20191/monitoring.html`
- `public/20191/monitoringmap.html`
- `public/catatan-harian.html`
- `public/check-in-camera.html`
- `public/check-in.bak.html`
- `public/check-in.html`
- `public/finalreport.html`
- `public/index.html`
- `public/input.html`
- `public/js/catatan-harian.js`
- `public/js/finalreport-json.js`
- `public/js/finalreport.js`
- `public/js/laporan-json.js`
- `public/js/laporan.backup.js`
- `public/js/laporan.js`
- `public/js/main-list.js`
- `public/js/p5/addons/p5.dom.js`
- `public/js/p5/addons/p5.sound.js`
- `public/js/p5/p5.js`
- `public/js/p5/p5.min.js`
- `public/js/p5/p5.pre-min.js`
- `public/laporan.html`
- `public/list-data-kp.html`
- `public/monitoring-asli.html`
- `public/monitoring.html`
- `public/monitoringmap.html`
- `public/profile.html`
- `public/tempat-pkl.html`
- `public/widemap.html`

### check_in

- `public/20191/finalreport.html`
- `public/20191/index.html`
- `public/20191/js/coba.js`
- `public/20191/js/finalreport.js`
- `public/20191/js/gijgo.min.js`
- `public/20191/js/index-checkin.js`
- `public/20191/js/laporan.js`
- `public/20191/js/monitoring.js`
- `public/20191/js/monitoringmap.js`
- `public/20191/js/p5/addons/p5.dom.js`
- `public/20191/js/p5/p5.js`
- `public/20191/js/p5/p5.pre-min.js`
- `public/20191/laporan.html`
- `public/20191/list-data-kp.html`
- `public/20191/monitoring.html`
- `public/20191/monitoringmap.html`
- `public/catatan-harian.html`
- `public/check-in-camera.html`
- `public/check-in.bak.html`
- `public/check-in.html`
- `public/finalreport.html`
- `public/index.html`
- `public/input.html`
- `public/js/catatan-harian.js`
- `public/js/coba.js`
- `public/js/finalreport-json.js`
- `public/js/finalreport.js`
- `public/js/gijgo.min.js`
- `public/js/index-checkin-cam.js`
- `public/js/index-checkin.js`
- `public/js/laporan-json.js`
- `public/js/laporan.backup.js`
- `public/js/laporan.js`
- `public/js/monitoring.js`
- `public/js/monitoringmap.js`
- `public/js/p5/addons/p5.dom.js`
- `public/js/p5/p5.js`
- `public/js/p5/p5.pre-min.js`
- `public/laporan.html`
- `public/list-data-kp.html`
- `public/monitoring-asli.html`
- `public/monitoring.html`
- `public/monitoringmap.html`
- `public/profile.html`
- `public/rule.txt`
- `public/tempat-pkl.html`
- `public/widemap.html`

