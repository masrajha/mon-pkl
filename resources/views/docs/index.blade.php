<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi SiLAT</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #0f172a; }
        a { color: inherit; text-decoration: none; }
        .page { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 22px clamp(20px, 5vw, 72px); background: #fff; border-bottom: 1px solid #e5e7eb; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .mark { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 800; }
        .brand strong { display: block; font-size: 16px; }
        .brand span { display: block; margin-top: 2px; color: #64748b; font-size: 13px; }
        .home-link { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; color: #334155; font-size: 14px; font-weight: 700; background: #fff; }
        .hero { padding: 52px clamp(20px, 5vw, 72px) 28px; }
        .hero p:first-child { margin: 0 0 10px; color: #2563eb; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 860px; font-size: clamp(34px, 5vw, 58px); line-height: 1.02; letter-spacing: 0; }
        .lead { max-width: 760px; margin: 18px 0 0; color: #475569; font-size: 18px; line-height: 1.7; }
        .update-box { margin: 28px 0 0; max-width: 980px; border: 1px solid #bfdbfe; border-radius: 8px; background: #eff6ff; padding: 18px 20px; box-shadow: 0 12px 30px rgba(37, 99, 235, .08); }
        .update-head { display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: center; justify-content: space-between; }
        .update-title { margin: 0; color: #0f172a; font-size: 16px; font-weight: 900; }
        .update-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #1d4ed8; font-size: 12px; font-weight: 800; }
        .pill { border: 1px solid #bfdbfe; border-radius: 999px; background: #fff; padding: 5px 9px; }
        .update-status { margin: 10px 0 0; color: #334155; font-size: 14px; line-height: 1.6; }
        .update-list { margin: 12px 0 0; padding-left: 18px; color: #334155; line-height: 1.65; font-size: 14px; }
        .grid { display: grid; gap: 18px; grid-template-columns: repeat(4, minmax(0, 1fr)); padding: 22px clamp(20px, 5vw, 72px) 64px; }
        .card { min-height: 240px; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #dbe3ef; border-radius: 8px; background: #fff; padding: 22px; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .card:hover { transform: translateY(-3px); border-color: #2563eb; box-shadow: 0 18px 42px rgba(37, 99, 235, .16); }
        .badge { display: grid; place-items: center; width: 46px; height: 46px; border-radius: 8px; background: #eff6ff; color: #1d4ed8; font-size: 18px; font-weight: 900; }
        h2 { margin: 18px 0 10px; font-size: 22px; letter-spacing: 0; }
        .card p { margin: 0; color: #64748b; line-height: 1.65; }
        .cta { margin-top: 22px; color: #2563eb; font-weight: 800; font-size: 14px; }
        footer { margin-top: auto; padding: 20px clamp(20px, 5vw, 72px); color: #64748b; font-size: 13px; }
        @media (max-width: 980px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) {
            .topbar { align-items: flex-start; flex-direction: column; }
            .grid { grid-template-columns: 1fr; }
            .card { min-height: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="topbar">
            <a class="brand" href="{{ route('docs.index') }}">
                <span class="mark">SL</span>
                <span><strong>SiLAT</strong><span>Manual Penggunaan Sistem</span></span>
            </a>
            <a class="home-link" href="{{ route('landing') }}">Beranda</a>
        </header>

        <main>
            <section class="hero">
                <p>Dokumentasi Publik</p>
                <h1>Pilih manual berdasarkan role pengguna.</h1>
                <p class="lead">Manual ini menjelaskan cara kerja sistem, alur penggunaan, presensi, jarak, batasan, dan tanggung jawab setiap role.</p>
                @if (($metadata['version'] ?? null) || ($metadata['whats_new'] ?? []))
                    <div class="update-box">
                        <div class="update-head">
                            <h2 class="update-title">What's New</h2>
                            <div class="update-meta">
                                @if ($metadata['version'] ?? null)
                                    <span class="pill">Versi {{ $metadata['version'] }}</span>
                                @endif
                                @if ($metadata['updated_at'] ?? null)
                                    <span class="pill">{{ $metadata['updated_at'] }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($metadata['status'] ?? null)
                            <p class="update-status">{{ $metadata['status'] }}</p>
                        @endif
                        @if ($metadata['whats_new'] ?? [])
                            <ul class="update-list">
                                @foreach ($metadata['whats_new'] as $item)
                                    <li>{{ str_replace('**', '', $item) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </section>

            <section class="grid" aria-label="Pilihan role">
                @foreach ($roles as $key => $role)
                    <a class="card" href="{{ route('docs.show', $key) }}">
                        <div>
                            <span class="badge">{{ $role['icon'] }}</span>
                            <h2>{{ $role['label'] }}</h2>
                            <p>{{ $role['description'] }}</p>
                        </div>
                        <span class="cta">Buka manual</span>
                    </a>
                @endforeach
            </section>
        </main>

        <footer>Dokumentasi bersumber dari file <strong>manuals.md</strong>.</footer>
    </div>
</body>
</html>
