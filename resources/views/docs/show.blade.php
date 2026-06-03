<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manual {{ $role['label'] }} - SiLAT</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: #f5f7fb; color: #0f172a; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .docs-shell { min-height: 100vh; display: grid; grid-template-columns: 320px minmax(0, 1fr); }
        .sidebar { position: sticky; top: 0; height: 100vh; overflow: auto; background: #fff; border-right: 1px solid #e5e7eb; padding: 20px; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
        .mark { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 900; }
        .brand strong { display: block; font-size: 15px; }
        .brand span span { display: block; margin-top: 2px; color: #64748b; font-size: 12px; }
        .role-switch { display: grid; gap: 8px; margin-bottom: 24px; }
        .role-link { display: flex; align-items: center; gap: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; color: #334155; font-size: 13px; font-weight: 750; }
        .role-link[aria-current="page"] { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        .role-dot { display: grid; place-items: center; flex: 0 0 auto; width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9; font-size: 12px; font-weight: 900; }
        .role-link[aria-current="page"] .role-dot { background: #2563eb; color: #fff; }
        .outline-title { margin: 0 0 10px; color: #64748b; font-size: 12px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .outline { display: grid; gap: 6px; padding: 0 0 24px; margin: 0; list-style: none; }
        .outline button { width: 100%; border: 0; border-radius: 8px; background: transparent; padding: 10px 12px; color: #475569; text-align: left; cursor: pointer; font-size: 13px; font-weight: 700; line-height: 1.35; }
        .outline button:hover, .outline button.is-active { background: #eef4ff; color: #1d4ed8; }
        .main { min-width: 0; display: flex; flex-direction: column; }
        .topbar { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 74px; padding: 16px clamp(18px, 4vw, 42px); background: rgba(245, 247, 251, .92); backdrop-filter: blur(12px); border-bottom: 1px solid #e5e7eb; }
        .mobile-menu { display: none; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; padding: 9px 12px; color: #334155; font-weight: 800; cursor: pointer; }
        .crumb { color: #64748b; font-size: 13px; font-weight: 700; }
        .crumb a { color: #2563eb; }
        .controls { display: flex; align-items: center; gap: 8px; }
        .control-btn { border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; padding: 9px 12px; color: #334155; font-size: 13px; font-weight: 800; cursor: pointer; }
        .control-btn.primary { border-color: #2563eb; background: #2563eb; color: #fff; }
        .counter { min-width: 74px; color: #475569; text-align: center; font-size: 13px; font-weight: 800; }
        .deck { flex: 1; display: grid; padding: clamp(18px, 4vw, 42px); }
        .slide { display: none; min-height: calc(100vh - 158px); border: 1px solid #dbe3ef; border-radius: 8px; background: #fff; padding: clamp(22px, 5vw, 54px); box-shadow: 0 18px 48px rgba(15, 23, 42, .08); overflow: auto; }
        .slide.is-active { display: block; animation: fade-in .18s ease-out; }
        @keyframes fade-in { from { opacity: .3; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .content { max-width: 980px; }
        .content h3 { margin: 0 0 22px; color: #0f172a; font-size: clamp(30px, 4vw, 48px); line-height: 1.08; letter-spacing: 0; }
        .content h4 { margin: 28px 0 12px; font-size: 22px; letter-spacing: 0; }
        .content p, .content li { color: #334155; font-size: 17px; line-height: 1.75; }
        .content p { margin: 0 0 16px; }
        .content ul, .content ol { padding-left: 24px; }
        .content table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 15px; }
        .content th { background: #f1f5f9; color: #0f172a; text-align: left; }
        .content th, .content td { border: 1px solid #dbe3ef; padding: 10px 12px; vertical-align: top; }
        .content code { border-radius: 6px; background: #eef2f7; padding: 2px 6px; color: #0f172a; font-size: .92em; }
        .content pre { overflow: auto; border-radius: 8px; background: #0f172a; padding: 16px; color: #e2e8f0; }
        .content pre code { background: transparent; color: inherit; padding: 0; }
        .progress { height: 4px; background: #dbeafe; }
        .progress-bar { height: 100%; width: 0; background: #2563eb; transition: width .18s ease; }
        .drawer-backdrop { display: none; }
        @media (max-width: 920px) {
            .docs-shell { grid-template-columns: 1fr; }
            .sidebar { position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(340px, 88vw); transform: translateX(-104%); transition: transform .2s ease; box-shadow: 18px 0 44px rgba(15, 23, 42, .18); }
            .sidebar.is-open { transform: translateX(0); }
            .drawer-backdrop { position: fixed; inset: 0; z-index: 40; background: rgba(15, 23, 42, .35); }
            .drawer-backdrop.is-open { display: block; }
            .mobile-menu { display: inline-flex; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .controls { width: 100%; justify-content: space-between; }
            .slide { min-height: calc(100vh - 206px); }
        }
        @media print {
            .sidebar, .topbar, .progress { display: none; }
            .docs-shell { display: block; }
            .deck { padding: 0; display: block; }
            .slide { display: block; min-height: 0; box-shadow: none; border: 0; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="docs-shell" data-docs-deck>
        <aside class="sidebar" data-docs-sidebar>
            <a class="brand" href="{{ route('docs.index') }}">
                <span class="mark">SL</span>
                <span><strong>SiLAT Docs</strong><span>Manual penggunaan</span></span>
            </a>

            <nav class="role-switch" aria-label="Pilih role">
                @foreach ($roles as $key => $item)
                    <a class="role-link" href="{{ route('docs.show', $key) }}" @if ($key === $roleKey) aria-current="page" @endif>
                        <span class="role-dot">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <p class="outline-title">Outline {{ $role['label'] }}</p>
            <ol class="outline">
                @foreach ($slides as $index => $slide)
                    <li><button type="button" data-slide-target="{{ $index }}">{{ $slide['title'] }}</button></li>
                @endforeach
            </ol>
        </aside>
        <div class="drawer-backdrop" data-docs-backdrop></div>

        <main class="main">
            <header class="topbar">
                <div>
                    <button class="mobile-menu" type="button" data-docs-menu>Outline</button>
                    <div class="crumb"><a href="{{ route('docs.index') }}">Dokumentasi</a> / {{ $role['label'] }}</div>
                </div>
                <div class="controls">
                    <button class="control-btn" type="button" data-slide-prev>Sebelumnya</button>
                    <span class="counter" data-slide-counter>1 / {{ count($slides) }}</span>
                    <button class="control-btn primary" type="button" data-slide-next>Berikutnya</button>
                </div>
            </header>

            <div class="progress" aria-hidden="true"><div class="progress-bar" data-slide-progress></div></div>

            <section class="deck" aria-label="Manual {{ $role['label'] }}">
                @foreach ($slides as $index => $slide)
                    <article class="slide" id="{{ $slide['id'] }}" data-slide="{{ $index }}">
                        <div class="content">{!! $slide['html'] !!}</div>
                    </article>
                @endforeach
            </section>
        </main>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-docs-deck]');
            const slides = Array.from(root.querySelectorAll('[data-slide]'));
            const buttons = Array.from(root.querySelectorAll('[data-slide-target]'));
            const previous = root.querySelector('[data-slide-prev]');
            const next = root.querySelector('[data-slide-next]');
            const counter = root.querySelector('[data-slide-counter]');
            const progress = root.querySelector('[data-slide-progress]');
            const sidebar = root.querySelector('[data-docs-sidebar]');
            const backdrop = root.querySelector('[data-docs-backdrop]');
            const menu = root.querySelector('[data-docs-menu]');
            let active = 0;

            const clamp = (value) => Math.min(Math.max(value, 0), slides.length - 1);

            const closeSidebar = () => {
                sidebar.classList.remove('is-open');
                backdrop.classList.remove('is-open');
            };

            const openSidebar = () => {
                sidebar.classList.add('is-open');
                backdrop.classList.add('is-open');
            };

            const show = (index, updateHash = true) => {
                active = clamp(index);
                slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === active));
                buttons.forEach((button, buttonIndex) => button.classList.toggle('is-active', buttonIndex === active));
                previous.disabled = active === 0;
                next.disabled = active === slides.length - 1;
                counter.textContent = `${active + 1} / ${slides.length}`;
                progress.style.width = `${((active + 1) / slides.length) * 100}%`;

                if (updateHash) {
                    history.replaceState(null, '', `#${slides[active].id}`);
                }

                closeSidebar();
            };

            buttons.forEach((button) => button.addEventListener('click', () => show(Number(button.dataset.slideTarget))));
            previous.addEventListener('click', () => show(active - 1));
            next.addEventListener('click', () => show(active + 1));
            menu.addEventListener('click', openSidebar);
            backdrop.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowRight' || event.key === 'PageDown') show(active + 1);
                if (event.key === 'ArrowLeft' || event.key === 'PageUp') show(active - 1);
                if (event.key === 'Escape') closeSidebar();
            });

            const initialIndex = slides.findIndex((slide) => slide.id === window.location.hash.slice(1));
            show(initialIndex >= 0 ? initialIndex : 0, false);
        })();
    </script>
</body>
</html>
