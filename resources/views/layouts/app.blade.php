<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- Scripts -->
        @php
            $periodConfigurationService = app(\App\Services\PeriodConfigurationService::class);
            $browserNotificationsEnabled = auth()->check()
                && auth()->user()?->hasRole('mahasiswa')
                && (bool) config('monpkl.web_notifications.enabled', true);
            $monPklFrontendConfig = [
                'map' => $periodConfigurationService->frontendMapConfig(null),
                'region' => config('monpkl.region'),
                'browserNotifications' => [
                    'enabled' => $browserNotificationsEnabled,
                    'pollUrl' => auth()->check() ? route('browser-notifications.unread') : null,
                    'markShownUrl' => auth()->check() ? route('browser-notifications.mark-shown', ['browserNotification' => '__ID__']) : null,
                    'markReadUrl' => auth()->check() ? route('browser-notifications.mark-read', ['browserNotification' => '__ID__']) : null,
                    'subscribeUrl' => auth()->check() ? route('browser-notifications.subscribe') : null,
                    'unsubscribeUrl' => auth()->check() ? route('browser-notifications.unsubscribe') : null,
                    'serviceWorkerUrl' => asset('silat-service-worker.js'),
                    'vapidPublicKey' => config('monpkl.web_notifications.vapid_public_key'),
                    'pollSeconds' => (int) config('monpkl.web_notifications.poll_seconds', 60),
                ],
            ];
        @endphp
        <script>
            window.MonPklConfig = @json($monPklFrontendConfig);
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <div class="min-h-screen pt-16 lg:pl-64">
                <!-- Page Heading -->
                @isset($header)
                    <header class="silat-page-header">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>

                <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                    Dikembangkan oleh @didikunila
                </footer>
            </div>
        </div>
    </body>
</html>
