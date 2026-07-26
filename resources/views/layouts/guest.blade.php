<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeManager()"
      :class="{ 'dark': isDark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle ?? 'Bienvenue sur Feedevent' }}</title>
        <meta name="description" content="Connecte-toi à Feedevent pour sauvegarder les meilleures sorties près de chez toi.">
        <meta name="theme-color" content="#7c3aed">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <main class="relative min-h-screen overflow-hidden bg-[#f8f7fc] px-4 py-8 transition-colors duration-300 dark:bg-gray-950 sm:grid sm:place-items-center">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-fuchsia-200/50 blur-3xl dark:bg-fuchsia-900/20"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-brand-200/60 blur-3xl dark:bg-brand-900/20"></div>
            <div class="relative mx-auto w-full max-w-md">
                <a href="/" class="mx-auto flex w-fit items-center justify-center" aria-label="Retour à l’accueil">
                    <x-brand-mark />
                </a>
                <p class="mt-4 text-center text-sm font-medium text-gray-500 dark:text-gray-400">Les meilleures sorties, juste autour de toi.</p>

                <div class="surface mt-6 px-5 py-6 sm:px-8 sm:py-8">
                    {{ $slot }}
                </div>

                <button @click="toggleTheme()" type="button"
                    class="icon-button mx-auto mt-5 flex"
                    :aria-label="isDark ? 'Activer le mode clair' : 'Activer le mode sombre'">
                <svg x-show="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                </button>
            </div>
        </main>

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
