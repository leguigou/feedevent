<x-app-layout>
    <div class="relative" x-data="{ listOpen: false }">
        <h1 class="sr-only">Carte des événements à proximité</h1>
        <div class="absolute inset-x-0 top-0 z-[1000] p-3 sm:p-5">
            <div class="mx-auto flex max-w-4xl gap-2 rounded-2xl border border-white/70 bg-white/95 p-2 shadow-xl shadow-gray-900/10 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-900/95">
                <label class="min-w-0 flex-1">
                    <span class="sr-only">Filtrer par catégorie</span>
                    <select id="map-category" class="search-input !min-h-11 !border-0 !py-2 focus:!ring-0">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button id="map-locate" type="button" class="icon-button shrink-0" aria-label="Me localiser">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3"/></svg>
                </button>
                <button type="button" @click="listOpen = !listOpen" class="btn-secondary shrink-0 !px-3 sm:!px-4" :aria-expanded="listOpen.toString()" aria-controls="map-results" aria-label="Afficher ou masquer la liste des événements">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.8" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>
                    <span class="hidden sm:inline">Liste</span>
                </button>
            </div>
        </div>

        <div id="map" class="h-[calc(100dvh-4rem)] min-h-[500px] w-full md:h-[calc(100vh-4rem)]" aria-label="Carte des événements"></div>

        <button id="map-search-area" type="button" class="btn-primary absolute left-1/2 top-24 z-[1000] hidden -translate-x-1/2 !min-h-10 whitespace-nowrap !rounded-full !px-4 !py-2 text-sm">
            Rechercher dans cette zone
        </button>

        <aside id="map-results" x-show="listOpen" x-transition x-cloak
               class="fixed inset-x-0 bottom-[calc(4.65rem+env(safe-area-inset-bottom))] z-[1100] max-h-[54dvh] overflow-y-auto rounded-t-[2rem] border-t border-gray-200 bg-white p-4 shadow-2xl dark:border-gray-800 dark:bg-gray-900 md:absolute md:bottom-5 md:left-5 md:right-auto md:top-24 md:max-h-none md:w-[360px] md:rounded-[1.5rem] md:border">
            <div class="sticky top-0 z-10 flex items-center justify-between bg-white pb-3 dark:bg-gray-900">
                <div>
                    <p class="eyebrow">Autour de la carte</p>
                    <h1 class="mt-1 text-xl font-black"><span id="map-count">0</span> sorties</h1>
                </div>
                <button type="button" @click="listOpen = false" class="icon-button md:hidden" aria-label="Fermer la liste">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div id="map-event-list" class="space-y-3"></div>
            <p id="map-empty" class="hidden py-10 text-center text-sm text-gray-500">Aucun événement dans cette zone.</p>
        </aside>

        <div id="map-status" class="pointer-events-none absolute bottom-24 left-1/2 z-[1000] -translate-x-1/2 rounded-xl bg-gray-950 px-4 py-3 text-sm font-bold text-white shadow-xl" role="status" aria-live="polite">Chargement de la carte…</div>
    </div>

    @push('scripts')
        @vite(['resources/js/map-init.js'])
    @endpush
</x-app-layout>
