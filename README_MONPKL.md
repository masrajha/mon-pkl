# Mon PKL Backend

## Database

Default `.env` memakai PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=monpkl
DB_USERNAME=monpkl
DB_PASSWORD=123
```

## PostGIS

Migration spatial berada di:

```text
database/migrations/2026_05_30_000400_add_spatial_location_to_internship_places.php
```

Untuk PostgreSQL, migration tersebut menambahkan:

```sql
location geography(Point, 4326)
```

Extension PostGIS harus dibuat sekali oleh superuser/DBA sebelum migration dijalankan:

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

Setelah itu jalankan:

```bash
php artisan migrate
```

Status lokal saat ini: PostGIS sudah aktif di database `monpkl`, migration spatial sudah berjalan, dan kolom `internship_places.location` bertipe `geography`.

Kolom `latitude` dan `longitude` tetap tersedia sebagai fallback portabel.

## Mail

Environment lokal memakai SMTP Gmail melalui konfigurasi Laravel standar:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME=Family Tree
```

Simpan kredensial asli hanya di `.env`. `.env.example` berisi placeholder.

## MariaDB

Migration inti tidak bergantung pada PostGIS. Jika `DB_CONNECTION=mysql` dipakai untuk MariaDB/MySQL, migration spatial akan menambahkan:

```sql
location POINT NULL
```

Dengan begitu schema dasar tetap dapat diterapkan di MariaDB. Query geospasial yang spesifik PostGIS harus diganti dengan fungsi spatial MariaDB atau memakai `latitude`/`longitude` dan perhitungan Haversine di service aplikasi.
