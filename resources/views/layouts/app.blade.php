<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeManager()"
      :class="{ 'dark': isDark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Feedevent') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')

        <style>
            [x-cloak] { display: none !important; }
            .dark body { background-color: #0d0d14; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-950 transition-colors duration-300">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
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
