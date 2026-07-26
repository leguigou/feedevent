<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeManager()"
      :class="{ 'dark': isDark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Feedevent') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-950 transition-colors duration-300">
            <div class="flex items-center gap-3 mb-2">
                <a href="/" class="flex items-center gap-2">
                    <span class="text-3xl">🎉</span>
                    <span class="text-xl font-extrabold bg-gradient-to-r from-brand-500 to-purple-600 bg-clip-text text-transparent">Feedevent</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 sm:py-6 bg-white dark:bg-gray-900 shadow-md dark:shadow-gray-900/50 overflow-hidden sm:rounded-2xl border border-gray-100 dark:border-gray-800">
                {{ $slot }}
            </div>

            <!-- Dark mode toggle minimal -->
            <button @click="toggleTheme()" 
                    class="mt-6 p-2.5 rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                    :title="isDark ? 'Mode clair' : 'Mode sombre'">
                <svg x-show="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </div>

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
