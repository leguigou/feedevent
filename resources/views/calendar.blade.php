<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        <div class="mb-6">
            <p class="eyebrow">Planifie tes prochaines sorties</p>
            <h1 class="mt-1 text-3xl font-black tracking-[-0.035em] text-gray-950 dark:text-white sm:text-4xl">Ton agenda culturel</h1>
            <p class="mt-2 max-w-2xl text-gray-500 dark:text-gray-400">Une vue claire des événements à venir. Sur mobile, la liste est privilégiée pour aller à l’essentiel.</p>
        </div>
        <div class="surface p-3 sm:p-5">
            <div id="calendar" class="min-h-[500px]" aria-label="Calendrier des événements"></div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/calendar-init.js'])
    @endpush
</x-app-layout>
