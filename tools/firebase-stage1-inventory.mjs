import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

const DEFAULT_SOURCE = 'ilkomunila-export (20220619-Periode Jan 2022).json';
const DEFAULT_MASTER = 'ilkomunila-master-export.json';
const DEFAULT_DATE = new Date().toISOString().slice(0, 10);

const args = parseArgs(process.argv.slice(2));
const root = process.cwd();
const sourceFile = args.source ?? DEFAULT_SOURCE;
const masterFile = args.master ?? DEFAULT_MASTER;
const outDir = path.resolve(root, args.out ?? 'docs/migration/stage-1');
const backupDir = path.resolve(root, args.backupDir ?? `firebase-freeze/${DEFAULT_DATE}`);

const sourcePath = path.resolve(root, sourceFile);
const masterPath = path.resolve(root, masterFile);

if (!fs.existsSync(sourcePath)) {
  fail(`Source export not found: ${sourceFile}`);
}

const source = readJson(sourcePath);
const master = fs.existsSync(masterPath) ? readJson(masterPath) : {};
const exportFiles = listExportJsonFiles(root);

fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(backupDir, { recursive: true });

const backupManifest = copyExports(exportFiles, backupDir);
const inventory = buildInventory(source, master, sourceFile, masterFile, exportFiles, backupManifest);
const featureInventory = scanFeatures(path.resolve(root, 'public'));

writeJson(path.join(outDir, 'inventory-summary.json'), inventory);
writeJson(path.join(outDir, 'backup-manifest.json'), backupManifest);
writeText(path.join(outDir, 'field-mapping.md'), renderFieldMapping(inventory));
writeText(path.join(outDir, 'freeze-runbook.md'), renderFreezeRunbook(inventory, backupDir));
writeText(path.join(outDir, 'feature-inventory.md'), renderFeatureInventory(featureInventory));
writeText(path.join(outDir, 'README.md'), renderReadme(inventory, featureInventory, backupDir));

console.log(`Stage 1 inventory generated in ${relative(outDir)}`);
console.log(`Firebase export backup snapshot copied to ${relative(backupDir)}`);
console.log(`Primary source: ${sourceFile}`);
console.log(`Backup files: ${backupManifest.files.length}`);

function parseArgs(argv) {
  const parsed = {};
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (arg === '--source') parsed.source = argv[++i];
    else if (arg === '--master') parsed.master = argv[++i];
    else if (arg === '--out') parsed.out = argv[++i];
    else if (arg === '--backup-dir') parsed.backupDir = argv[++i];
    else fail(`Unknown argument: ${arg}`);
  }
  return parsed;
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function writeJson(filePath, value) {
  fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`);
}

function writeText(filePath, value) {
  fs.writeFileSync(filePath, value);
}

function listExportJsonFiles(baseDir) {
  return fs.readdirSync(baseDir)
    .filter((file) => file.toLowerCase().endsWith('.json'))
    .filter((file) => file.startsWith('ilkomunila-') || file === 'didik.json' || file === 'kosong.json')
    .sort((a, b) => a.localeCompare(b));
}

function copyExports(files, targetDir) {
  const copiedAt = new Date().toISOString();
  const manifest = {
    generated_at: copiedAt,
    backup_dir: relative(targetDir),
    files: [],
  };

  for (const file of files) {
    const sourcePath = path.resolve(root, file);
    const targetPath = path.join(targetDir, file);
    const stat = fs.statSync(sourcePath);
    fs.copyFileSync(sourcePath, targetPath);
    manifest.files.push({
      file,
      backup_path: relative(targetPath),
      size_bytes: stat.size,
      modified_at: stat.mtime.toISOString(),
      sha256: sha256File(sourcePath),
    });
  }

  return manifest;
}

function buildInventory(data, masterData, sourceName, masterName, files, backupManifest) {
  const pkl = asRecord(data.pkl);
  const monPkl = asRecord(data.mon_pkl);
  const monUser = asRecord(data.mon_user);
  const users = asRecord(data.users);
  const masterKota = normalizeCities(masterData.kota ?? data.master?.kota ?? data.kota);
  const monUserStats = summarizeMonUser(monUser);

  return {
    generated_at: new Date().toISOString(),
    selected_source: {
      database_export: sourceName,
      master_export: fs.existsSync(masterPath) ? masterName : null,
      rationale: 'Export produksi historis paling baru yang berisi path utama pkl, mon_pkl, mon_user, dan users. Export latihan 2025 dicatat sebagai kandidat terpisah, tetapi tidak dipilih sebagai sumber utama karena namanya menunjukkan data latihan.',
    },
    export_files: files.map((file) => ({
      file,
      size_bytes: fs.statSync(path.resolve(root, file)).size,
      sha256: backupManifest.files.find((item) => item.file === file)?.sha256,
    })),
    paths: {
      pkl: {
        records: Object.keys(pkl).length,
        fields: collectFieldStats(normalizeFirebaseRecords(Object.values(pkl))),
        target_table: 'internship_places',
      },
      mon_pkl: {
        records: Object.keys(monPkl).length,
        fields: collectFieldStats(normalizeFirebaseRecords(Object.values(monPkl))),
        target_table: 'check_ins',
        source_role: 'primary check-in source',
      },
      mon_user: {
        npm_records: Object.keys(monUser).length,
        check_in_records: monUserStats.totalCheckIns,
        check_ins_by_npm_min: monUserStats.min,
        check_ins_by_npm_max: monUserStats.max,
        fields: collectFieldStats(normalizeFirebaseRecords(monUserStats.records)),
        target_table: null,
        source_role: 'validation/cross-check only',
      },
      users: {
        records: Object.keys(users).length,
        fields: collectFieldStats(normalizeFirebaseRecords(Object.values(users))),
        target_table: 'users, students',
      },
      'master/kota': {
        records: masterKota.length,
        fields: collectFieldStats(masterKota),
        target_table: 'cities',
      },
    },
  };
}

function normalizeFirebaseRecords(records) {
  return records.map((record) => flattenRecord(record));
}

function normalizeCities(value) {
  if (Array.isArray(value)) {
    return value
      .map((name, index) => ({ legacy_index: index, name }))
      .filter((city) => city.name);
  }

  return Object.entries(asRecord(value)).map(([key, city]) => {
    if (typeof city === 'string') return { legacy_key: key, name: city };
    return { legacy_key: key, ...asRecord(city) };
  });
}

function flattenRecord(record, prefix = '') {
  const flattened = {};
  if (!record || typeof record !== 'object' || Array.isArray(record)) return flattened;

  for (const [key, value] of Object.entries(record)) {
    const field = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      Object.assign(flattened, flattenRecord(value, field));
    } else {
      flattened[field] = value;
    }
  }

  return flattened;
}

function asRecord(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return {};
  return value;
}

function summarizeMonUser(monUser) {
  const counts = [];
  const records = [];

  for (const [npm, checkIns] of Object.entries(monUser)) {
    const values = Object.values(asRecord(checkIns));
    counts.push(values.length);
    for (const record of values) {
      records.push({ npm, ...asRecord(record) });
    }
  }

  return {
    records,
    totalCheckIns: records.length,
    min: counts.length ? Math.min(...counts) : 0,
    max: counts.length ? Math.max(...counts) : 0,
  };
}

function collectFieldStats(records) {
  const stats = new Map();

  for (const record of records) {
    if (!record || typeof record !== 'object' || Array.isArray(record)) continue;
    for (const [field, value] of Object.entries(record)) {
      if (!stats.has(field)) {
        stats.set(field, { count: 0, types: new Set(), examples: [] });
      }
      const item = stats.get(field);
      item.count += 1;
      item.types.add(typeOf(value));
      if (item.examples.length < 3 && value !== null && value !== undefined && value !== '') {
        item.examples.push(shortValue(value));
      }
    }
  }

  return Object.fromEntries([...stats.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([field, item]) => [field, {
      count: item.count,
      types: [...item.types].sort(),
      examples: item.examples,
    }]));
}

function typeOf(value) {
  if (value === null) return 'null';
  if (Array.isArray(value)) return 'array';
  return typeof value;
}

function shortValue(value) {
  const text = typeof value === 'string' ? value : JSON.stringify(value);
  return text.length > 120 ? `${text.slice(0, 117)}...` : text;
}

function scanFeatures(publicDir) {
  const files = walk(publicDir)
    .filter((file) => /\.(html|js|txt)$/i.test(file))
    .sort((a, b) => a.localeCompare(b));
  const patterns = {
    firebase: /firebase|database\(|auth\(|storage\(/i,
    google_maps: /google\.maps|maps\.googleapis|MarkerClusterer/i,
    camera: /getUserMedia|camera|canvas/i,
    report: /laporan|finalreport|print/i,
    check_in: /check[-_ ]?in|mon_pkl|mon_user/i,
  };
  const matches = {};

  for (const [name, pattern] of Object.entries(patterns)) {
    matches[name] = [];
    for (const file of files) {
      const content = fs.readFileSync(file, 'utf8');
      if (pattern.test(content)) matches[name].push(relative(file));
    }
  }

  return {
    generated_at: new Date().toISOString(),
    active_pages: files.filter((file) => file.endsWith('.html')).map(relative),
    pattern_matches: matches,
    recommended_status: [
      ['Login Google Firebase', 'aktif saat ini', 'migrasikan ke Laravel Socialite'],
      ['Input tempat PKL', 'aktif saat ini', 'migrasikan ke CRUD internship_places'],
      ['Check-in dengan lokasi dan foto', 'aktif saat ini', 'migrasikan ke endpoint server dan Laravel Storage'],
      ['Monitoring peta', 'aktif saat ini', 'migrasikan ke Leaflet/markercluster'],
      ['Monitoring tabel', 'aktif saat ini', 'migrasikan ke query server'],
      ['Laporan/final report', 'aktif saat ini', 'migrasikan ke service laporan server'],
      ['Folder public/20191', 'arsip periode lama', 'boleh dihentikan setelah data tervalidasi'],
      ['File coba/test/backup', 'fitur lama/dev', 'boleh dihentikan setelah cutover'],
    ],
  };
}

function renderFieldMapping(inventory) {
  const sections = [
    '# Mapping Field Firebase Tahap 1',
    '',
    `Sumber utama: \`${inventory.selected_source.database_export}\`.`,
    `Sumber master kota: \`${inventory.selected_source.master_export ?? 'tidak ditemukan'}\`.`,
    '',
    'Catatan: `mon_pkl` ditetapkan sebagai sumber utama check-in. `mon_user/{npm}` hanya dipakai untuk validasi silang jumlah dan isi data.',
    '',
  ];

  for (const [pathName, pathInfo] of Object.entries(inventory.paths)) {
    sections.push(`## ${pathName}`);
    sections.push('');
    sections.push(`Target tabel: ${pathInfo.target_table ? `\`${pathInfo.target_table}\`` : 'tidak dimigrasikan langsung'}.`);
    sections.push(`Jumlah record: ${pathInfo.records ?? pathInfo.check_in_records ?? 0}.`);
    if (pathInfo.npm_records !== undefined) sections.push(`Jumlah NPM pada \`mon_user\`: ${pathInfo.npm_records}.`);
    sections.push('');
    sections.push('| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |');
    sections.push('| --- | --- | --- | --- |');
    for (const [field, stat] of Object.entries(pathInfo.fields)) {
      sections.push(`| \`${field}\` | ${stat.types.join(', ')} | ${targetFor(pathName, field)} | ${noteFor(pathName, field)} |`);
    }
    sections.push('');
  }

  return `${sections.join('\n')}\n`;
}

function targetFor(pathName, field) {
  const map = {
    pkl: {
      'geometry.coordinates': 'internship_places.longitude, internship_places.latitude',
      'geometry.type': 'internship_places.location type validation',
      'properties.alamat': 'internship_places.address',
      'properties.hp_mhs': 'internship_places.contact_student_phone',
      'properties.instansi': 'internship_places.name',
      'properties.kota': 'internship_places.city_id/name',
      'properties.mhs': 'students placement relation',
      'properties.pemb_hp': 'internship_places.field_supervisor_phone',
      'properties.pemb_lap': 'internship_places.field_supervisor_name',
      'properties.time': 'internship_places.legacy_created_at',
      'properties.visited': 'internship_places.visited',
      type: 'legacy_geojson_type',
    },
    mon_pkl: {
      'geometry.coordinates': 'check_ins.office/student coordinates',
      'geometry.type': 'check_ins.legacy_geometry_type',
      'properties.catatan': 'check_ins.note',
      'properties.device.appVersion': 'check_ins.device_info.appVersion',
      'properties.device.browserName': 'check_ins.device_info.browserName',
      'properties.device.browserVersion': 'check_ins.device_info.browserVersion',
      'properties.device.osName': 'check_ins.device_info.osName',
      'properties.device.osVersion': 'check_ins.device_info.osVersion',
      'properties.device.platform': 'check_ins.device_info.platform',
      'properties.device.userAgent': 'check_ins.device_info.userAgent',
      'properties.device.vendor': 'check_ins.device_info.vendor',
      'properties.imgURL': 'check_ins.photo_path/source_photo_url',
      'properties.instansi': 'internship_places.name',
      'properties.keterangan': 'check_ins.type',
      'properties.nama': 'students.full_name snapshot',
      'properties.npm': 'students.npm',
      'properties.time': 'check_ins.checked_at',
      'properties.url': 'check_ins.source_url',
      'properties.user.displayName': 'users.name snapshot',
      'properties.user.email': 'users.email snapshot',
      'properties.user.photoURL': 'users.avatar_url snapshot',
      'properties.user.uid': 'users.google_id/firebase_uid_legacy',
      type: 'legacy_geojson_type',
    },
    mon_user: {
      npm: 'students.npm legacy path key',
    },
    users: {
      email: 'users.email',
      'instansi.lat': 'internship_places.latitude fallback',
      'instansi.lng': 'internship_places.longitude fallback',
      'instansi.nama': 'internship_places.name',
      nama: 'users.name/students.full_name',
      npm: 'students.npm',
      pembimbing: 'students.field_supervisor',
      photoURL: 'users.avatar_url',
      uid: 'users.google_id/firebase_uid_legacy',
    },
    'master/kota': {
      legacy_index: 'cities.legacy_index',
      legacy_key: 'cities.legacy_key',
      name: 'cities.name',
    },
  };

  return map[pathName]?.[field] ?? 'perlu konfirmasi saat import';
}

function noteFor(pathName, field) {
  if (pathName === 'mon_user') return 'Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama.';
  if (field.toLowerCase().includes('img')) return 'Foto lama masih berupa URL Firebase Storage; migrasi file dilakukan terpisah.';
  if (/(^|\.)(lat|lng|latitude|longitude)$/.test(field.toLowerCase())) return 'Validasi numeric dan range koordinat saat import.';
  if (field === 'geometry.coordinates') return 'Validasi urutan koordinat GeoJSON [longitude, latitude] saat import.';
  if (field === 'jarak') return 'Hitung ulang dengan metode resmi saat import jika memungkinkan.';
  return '';
}

function renderFreezeRunbook(inventory, targetDir) {
  return `# Runbook Freeze Data Firebase

Tanggal snapshot lokal: ${new Date().toISOString()}

## Keputusan Sumber Data

- Sumber utama migrasi: \`${inventory.selected_source.database_export}\`
- Sumber master kota: \`${inventory.selected_source.master_export ?? 'tidak ditemukan'}\`
- Alasan: ${inventory.selected_source.rationale}

## Prosedur Freeze Final

1. Umumkan waktu freeze kepada admin, dosen, dan mahasiswa.
2. Nonaktifkan sementara fitur tulis pada aplikasi lama: input tempat PKL, check-in, update profil, dan upload foto.
3. Export Firebase Realtime Database dari console Firebase setelah freeze aktif.
4. Simpan file export final dengan pola nama \`ilkomunila-export (YYYYMMDD-final).json\`.
5. Jalankan:

\`\`\`powershell
node tools/firebase-stage1-inventory.mjs --source "ilkomunila-export (YYYYMMDD-final).json" --backup-dir "${relative(targetDir)}"
\`\`\`

6. Cocokkan SHA-256 di \`backup-manifest.json\` dengan file backup.
7. Jika data tidak bisa dibekukan total, lakukan export delta sejak waktu freeze dan catat nama file delta di dokumen ini.

## Snapshot Lokal Saat Ini

Folder backup: \`${relative(targetDir)}\`

File manifest:

- \`docs/migration/stage-1/backup-manifest.json\`
- \`docs/migration/stage-1/inventory-summary.json\`

## Mekanisme Delta

Jika masih ada transaksi setelah export utama, ambil export baru dan jadikan:

- export utama lama sebagai baseline.
- export baru sebagai final.
- selisih \`mon_pkl\`, \`pkl\`, \`users\`, dan \`master/kota\` dipakai sebagai delta validasi sebelum import Tahap 3.
`;
}

function renderFeatureInventory(featureInventory) {
  const lines = [
    '# Inventaris Fitur Aktif dan Fitur Lama',
    '',
    '## Status Rekomendasi',
    '',
    '| Fitur | Status Saat Ini | Keputusan Migrasi |',
    '| --- | --- | --- |',
  ];

  for (const [feature, status, decision] of featureInventory.recommended_status) {
    lines.push(`| ${feature} | ${status} | ${decision} |`);
  }

  lines.push('');
  lines.push('## File Terdeteksi');
  lines.push('');
  for (const [name, files] of Object.entries(featureInventory.pattern_matches)) {
    lines.push(`### ${name}`);
    lines.push('');
    for (const file of files) {
      lines.push(`- \`${file}\``);
    }
    if (files.length === 0) lines.push('- Tidak ada file terdeteksi.');
    lines.push('');
  }

  return `${lines.join('\n')}\n`;
}

function renderReadme(inventory, featureInventory, targetDir) {
  return `# Tahap 1 - Inventarisasi dan Freeze Data

Artefak ini dihasilkan oleh \`tools/firebase-stage1-inventory.mjs\`.

## Output

- \`field-mapping.md\`: mapping field Firebase ke target tabel awal.
- \`inventory-summary.json\`: ringkasan struktur path dan jumlah record.
- \`backup-manifest.json\`: daftar backup JSON export beserta SHA-256.
- \`feature-inventory.md\`: daftar fitur aktif dan fitur lama/dev yang boleh dihentikan.
- \`freeze-runbook.md\`: langkah freeze final dan opsi delta export.

## Ringkasan Data

| Path | Jumlah |
| --- | ---: |
| \`pkl\` | ${inventory.paths.pkl.records} |
| \`mon_pkl\` | ${inventory.paths.mon_pkl.records} |
| \`mon_user/{npm}\` | ${inventory.paths.mon_user.npm_records} NPM / ${inventory.paths.mon_user.check_in_records} check-in |
| \`users\` | ${inventory.paths.users.records} |
| \`master/kota\` | ${inventory.paths['master/kota'].records} |

Backup lokal saat ini berada di \`${relative(targetDir)}\`.

## Sumber Utama

\`${inventory.selected_source.database_export}\`

${inventory.selected_source.rationale}

## Fitur Aktif Terdeteksi

Jumlah halaman HTML yang terdeteksi: ${featureInventory.active_pages.length}.
Detail ada di \`feature-inventory.md\`.
`;
}

function sha256File(filePath) {
  const hash = crypto.createHash('sha256');
  hash.update(fs.readFileSync(filePath));
  return hash.digest('hex');
}

function walk(dir) {
  if (!fs.existsSync(dir)) return [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) files.push(...walk(fullPath));
    else files.push(fullPath);
  }
  return files;
}

function relative(filePath) {
  return path.relative(root, filePath).replaceAll(path.sep, '/');
}

function fail(message) {
  console.error(message);
  process.exit(1);
}
