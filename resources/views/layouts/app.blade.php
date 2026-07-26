<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeManager()"
      :class="{ 'dark': isDark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle ?? config('app.name', 'Feedevent') }}</title>
        <meta name="description" content="{{ $pageDescription ?? 'Découvre les meilleurs événements autour de toi avec Feedevent.' }}">
        <meta name="theme-color" content="#7c3aed">
        @stack('meta')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')

        <style>
            [x-cloak] { display: none !important; }
            .dark body { background-color: #0d0d14; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <a href="#main-content" class="skip-link">Aller au contenu</a>
        <div class="min-h-screen bg-[#f8f7fc] dark:bg-gray-950 transition-colors duration-300">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-gray-200/70 bg-white/75 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-950/75">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main-content" class="pb-[calc(5.5rem+env(safe-area-inset-bottom))] md:pb-0">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts-bottom')

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('themeManager', () => ({
                    isDark: false,

                    init() {
                        this.isDark = localStorage.getItem('theme') === 'dark' 
                            || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        this.applyTheme();
                    },

                    toggleTheme() {
                        this.isDark = !this.isDark;
                        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                        this.applyTheme();
                    },

                    applyTheme() {
                        document.documentElement.classList.toggle('dark', this.isDark);
                    }
                }));
            });
        </script>
    </body>
</html>
