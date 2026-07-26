<x-app-layout>
    <div x-data="savedEvents()" class="mx-auto max-w-7xl px-4 py-7 sm:px-6 sm:py-10 lg:px-8">
        <div>
            <p class="eyebrow">Ta sélection personnelle</p>
            <h1 class="mt-1 text-3xl font-black tracking-[-0.035em] text-gray-950 dark:text-white sm:text-4xl">Mes favoris</h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Retrouve ici toutes les sorties que tu veux garder sous la main.</p>
        </div>

        <div x-show="loading" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-label="Chargement des favoris">
            <template x-for="i in 3" :key="i">
                <div class="event-card animate-pulse"><div class="aspect-[16/9] bg-gray-200 dark:bg-gray-800"></div><div class="space-y-3 p-5"><div class="h-5 w-4/5 rounded bg-gray-200 dark:bg-gray-800"></div><div class="h-4 w-1/2 rounded bg-gray-200 dark:bg-gray-800"></div></div></div>
            </template>
        </div>

        <div x-show="!loading && events.length" x-cloak class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="event in events" :key="event.id">
                <article class="event-card group relative">
                    <a :href="`/events/${event.id}`" class="absolute inset-0 z-10 rounded-[1.4rem]" :aria-label="'Voir ' + event.title"></a>
                    <div class="relative aspect-[16/9] overflow-hidden bg-brand-100 dark:bg-brand-950">
                        <template x-if="event.image_url"><img :src="event.image_url" :alt="event.title" loading="lazy" decoding="async" x-on:error="event.image_url = null" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></template>
                        <template x-if="!event.image_url"><div class="grid h-full place-items-center text-5xl opacity-40" x-text="event.category?.icon || '✦'" aria-hidden="true"></div></template>
                        <button type="button" @click.prevent.stop="remove(event)" class="icon-button absolute right-3 top-3 z-20 !bg-white/90 !text-brand-700 shadow backdrop-blur dark:!bg-gray-950/85 dark:!text-brand-300" aria-label="Retirer des favoris">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
                        </button>
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-bold text-brand-700 dark:text-brand-300" x-text="formatDate(event.date_start)"></p>
                        <h2 class="mt-2 line-clamp-2 text-lg font-black" x-text="event.title"></h2>
                        <p class="mt-2 truncate text-sm text-gray-500" x-text="event.location || 'Lieu à confirmer'"></p>
                    </div>
                </article>
            </template>
        </div>

        <div x-show="!loading && !events.length" x-cloak class="py-20 text-center">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
            </div>
            <h2 class="mt-5 text-xl font-black">Ta sélection est encore vide</h2>
            <p class="mt-2 text-gray-500">Ajoute un cœur aux événements qui te tentent.</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Découvrir les sorties</a>
        </div>
        <p x-show="error" x-text="error" class="mt-8 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-700 dark:bg-red-950/30 dark:text-red-300" role="alert"></p>
    </div>

    @push('scripts')
        <script>
            function savedEvents() {
                return {
                    events: [],
                    loading: true,
                    error: '',
                    async init() {
                        try {
                            const response = await fetch('/api/saved-events?per_page=100', { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error();
                            const data = await response.json();
                            this.events = data.data || [];
                        } catch (_) {
                            this.error = 'Impossible de charger tes favoris.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    async remove(event) {
                        const previous = [...this.events];
                        this.events = this.events.filter(item => item.id !== event.id);
                        try {
                            const response = await fetch(`/api/events/${event.id}/save`, {
                                method: 'POST',
                                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            });
                            if (!response.ok) throw new Error();
                        } catch (_) {
                            this.events = previous;
                            this.error = 'Impossible de retirer ce favori.';
                        }
                    },
                    formatDate(date) {
                        return new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(date));
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
