<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" id="htmlElement" style="font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <script>
            // Initialize dark mode for guests IMMEDIATELY before any rendering
            (function initDarkMode() {
                const htmlElement = document.documentElement;
                const darkModeFromStorage = localStorage.getItem('darkMode');
                
                if (darkModeFromStorage === 'true') {
                    htmlElement.classList.add('dark');
                } else {
                    htmlElement.classList.remove('dark');
                }
            })();
        </script>
    </head>
    <body class="font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_18%),radial-gradient(circle_at_bottom_right,_rgba(168,85,247,0.16),_transparent_16%)]"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-white/80 to-transparent dark:from-slate-900/80"></div>

            <div class="z-10 mb-8">
                <a href="/" class="inline-flex items-center gap-3 rounded-full bg-white/90 px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200/70 transition hover:bg-white dark:bg-slate-900/90 dark:text-white dark:ring-slate-700/70">
                    <x-application-logo class="w-10 h-10 text-slate-900 dark:text-white" />
                    {{ config('app.name', 'CryptoPay') }}
                </a>
            </div>

            <div class="w-full sm:max-w-xl lg:max-w-2xl px-4 z-10">
                <div class="relative overflow-hidden rounded-[28px] border border-slate-200/70 bg-white/90 shadow-2xl shadow-slate-900/10 dark:border-slate-700/70 dark:bg-slate-900/90 dark:shadow-black/20">
                    <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-br from-sky-400/20 via-transparent to-indigo-300/10"></div>
                    <div class="relative px-6 py-8 sm:p-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
