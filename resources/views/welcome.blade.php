@php
    $loginUrl = Route::has('login') ? route('login') : '#';
    $registerUrl = Route::has('register') ? route('register') : $loginUrl;
    $docsUrl = route('docs.index');

    $features = [
        ['title' => 'Pendaftaran Terarah', 'description' => 'Mahasiswa memilih program periode aktif, mitra, dan alur validasi melalui satu pintu.', 'icon' => 'fa-clipboard-check', 'tone' => 'bg-blue-50 text-blue-700'],
        ['title' => 'Monitoring GPS', 'description' => 'Check-in, peta mitra, dan aktivitas lapangan membantu pembimbing melihat progres aktual.', 'icon' => 'fa-map-location-dot', 'tone' => 'bg-teal-50 text-teal-700'],
        ['title' => 'Laporan Terpadu', 'description' => 'Dokumentasi bimbingan, presensi, dan laporan akhir tersusun rapi untuk evaluasi program.', 'icon' => 'fa-file-lines', 'tone' => 'bg-amber-50 text-amber-700'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="SiLAT membantu pengelolaan MBKM dan Kerja Praktik mulai dari pendaftaran, monitoring, hingga laporan kegiatan.">

        <title>SiLAT - MBKM & Kerja Praktik</title>

        <link rel="preconnect" href="https://images.unsplash.com">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-slate-900 antialiased">
        <nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="#beranda" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-700 text-sm font-bold text-white">SL</span>
                    <span>
                        <span class="block text-lg font-bold leading-5 text-slate-950">SiLAT</span>
                        <span class="block text-xs font-medium text-slate-500">MBKM & Kerja Praktik</span>
                    </span>
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#beranda" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Beranda</a>
                    <a href="#program" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Program</a>
                    <a href="#tentang" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Tentang</a>
                    <a href="{{ $docsUrl }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Dokumentasi</a>
                    <a href="{{ $loginUrl }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Login</a>
                    <a href="{{ $registerUrl }}" class="inline-flex h-10 items-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Register</a>
                </div>

                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 md:hidden" x-on:click="open = ! open" aria-label="Buka menu">
                    <i class="fa-solid" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

            <div x-show="open" x-transition style="display: none;" class="border-t border-slate-100 bg-white px-4 py-4 shadow-lg md:hidden">
                <div class="grid gap-2">
                    <a href="#beranda" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Beranda</a>
                    <a href="#program" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Program</a>
                    <a href="#tentang" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tentang</a>
                    <a href="{{ $docsUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dokumentasi</a>
                    <a href="{{ $loginUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Login</a>
                    <a href="{{ $registerUrl }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Register</a>
                </div>
            </div>
        </nav>

        <main>
            <section id="beranda" class="relative overflow-hidden bg-slate-950 text-white">
                <img
                    src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1800&q=80"
                    alt="Mahasiswa berdiskusi menggunakan laptop"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-r from-blue-950/95 via-blue-900/82 to-slate-950/45"></div>

                <div class="relative mx-auto flex min-h-[74vh] max-w-7xl items-center px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
                    <div class="max-w-3xl">
                        <p class="mb-4 inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm font-semibold text-blue-50 backdrop-blur">
                            <i class="fa-solid fa-graduation-cap"></i>
                            Sistem Laporan Aktivitas Terpadu
                        </p>
                        <h1 class="text-5xl font-extrabold leading-tight text-white sm:text-6xl lg:text-7xl">SiLAT</h1>
                        <p class="mt-5 max-w-2xl text-xl font-semibold leading-8 text-blue-50 sm:text-2xl">
                            Kelola MBKM dan Kerja Praktik dari pendaftaran, monitoring lokasi, pembimbingan, sampai laporan akhir dalam satu sistem.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $registerUrl }}" class="inline-flex h-12 items-center justify-center rounded-lg bg-amber-400 px-6 text-sm font-bold text-slate-950 shadow-lg shadow-amber-950/20 hover:bg-amber-300">
                                Mulai Sekarang
                            </a>
                            <a href="#tentang" class="inline-flex h-12 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-6 text-sm font-bold text-white backdrop-blur hover:bg-white/20">
                                Pelajari Lebih Lanjut
                            </a>
                        </div>
                        <div class="mt-8 flex flex-wrap gap-3 text-sm font-medium text-blue-50">
                            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 backdrop-blur"><i class="fa-solid fa-shield-halved text-teal-300"></i> Role-based access</span>
                            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 backdrop-blur"><i class="fa-solid fa-map-pin text-amber-300"></i> Monitoring berbasis peta</span>
                            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 backdrop-blur"><i class="fa-solid fa-calendar-check text-blue-200"></i> Periode program aktif</span>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-label="Ringkasan SiLAT" class="relative z-10 -mt-10 px-4 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($stats as $stat)
                        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-lg shadow-slate-950/5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stat['value'], 0, ',', '.') }}</p>
                                </div>
                                <span class="flex h-12 w-12 items-center justify-center rounded-lg {{ $stat['tone'] }}">
                                    <i class="fa-solid {{ $stat['icon'] }}"></i>
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="program" class="bg-white py-18 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <p class="text-sm font-bold uppercase text-blue-700">Program kegiatan</p>
                        <h2 class="mt-3 text-3xl font-extrabold text-slate-950 sm:text-4xl">Satu fondasi untuk banyak skema MBKM dan Kerja Praktik.</h2>
                        <p class="mt-4 text-base leading-7 text-slate-600">Rule saat ini mengikuti alur Kerja Praktik, namun master program disiapkan agar setiap skema bisa memiliki definisi alur dan kebijakan tersendiri di masa datang.</p>
                    </div>

                    <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($programs as $program)
                            <article class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-700 text-white">
                                    <i class="fa-solid {{ $program['icon'] }}"></i>
                                </span>
                                <h3 class="mt-5 text-xl font-bold text-slate-950">{{ $program['name'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $program['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-slate-50 py-18 sm:py-24">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
                    <div>
                        <p class="text-sm font-bold uppercase text-teal-700">Periode aktif</p>
                        <h2 class="mt-3 text-3xl font-extrabold text-slate-950 sm:text-4xl">Pantau periode program yang sedang berjalan.</h2>
                        <p class="mt-4 text-base leading-7 text-slate-600">Mahasiswa, koordinator, dosen, dan admin bekerja pada konteks program periode yang sama agar data pendaftaran, peserta, dan monitoring tetap konsisten.</p>
                    </div>

                    <div class="grid gap-4">
                        @forelse ($periods as $period)
                            @php
                                $today = \App\Support\LocalClock::today()->toDateString();
                                $status = $period->is_active ? 'Berlangsung' : ($period->starts_at && $period->starts_at->toDateString() > $today ? 'Akan Datang' : 'Terjadwal');
                                $statusClass = $period->is_active ? 'bg-teal-100 text-teal-800' : 'bg-blue-100 text-blue-800';
                            @endphp
                            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">{{ $period->program?->name ?? 'Program' }}</p>
                                        <h3 class="mt-1 text-lg font-bold text-slate-950">{{ $period->name }}</h3>
                                        <p class="mt-2 text-sm text-slate-600">
                                            {{ $period->starts_at?->translatedFormat('d M Y') ?? '-' }}
                                            <span class="mx-1 text-slate-400">s.d.</span>
                                            {{ $period->ends_at?->translatedFormat('d M Y') ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="inline-flex w-fit items-center rounded-lg px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $status }}</span>
                                </div>
                            </article>
                        @empty
                            <article class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-slate-600">
                                Belum ada periode aktif yang ditampilkan. Admin dapat mengatur Program dan Periode Program melalui modul manajemen.
                            </article>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="tentang" class="bg-white py-18 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                        <div>
                            <p class="text-sm font-bold uppercase text-blue-700">Tentang SiLAT</p>
                            <h2 class="mt-3 text-3xl font-extrabold text-slate-950 sm:text-4xl">Dibangun untuk koordinasi akademik yang lebih rapi.</h2>
                            <p class="mt-4 text-base leading-7 text-slate-600">SiLAT membantu Program Studi dan FMIPA Universitas Lampung menghubungkan proses administrasi, pemantauan lapangan, dan pelaporan kegiatan mahasiswa dalam satu pengalaman terpadu.</p>
                            <a href="{{ $loginUrl }}" class="mt-7 inline-flex h-11 items-center justify-center rounded-lg bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800">Masuk ke Sistem</a>
                        </div>

                        <div class="grid gap-4">
                            @foreach ($features as $feature)
                                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex gap-4">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg {{ $feature['tone'] }}">
                                            <i class="fa-solid {{ $feature['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-950">{{ $feature['title'] }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-slate-950 py-8 text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <div>
                    <p class="font-bold">SiLAT</p>
                    <p class="mt-1 text-sm text-slate-300">Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik</p>
                </div>
                <div class="text-sm text-slate-300 md:text-right">
                    <p>FMIPA Universitas Lampung</p>
                    <p class="mt-1">Dikembangkan oleh @didikunila</p>
                </div>
            </div>
        </footer>
    </body>
</html>
