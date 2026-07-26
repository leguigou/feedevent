<x-app-layout>
    <div x-data="eventFeed()" @keydown.escape.window="closeAddModal()" class="overflow-hidden">
        <section class="relative border-b border-gray-200/70 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -right-24 -top-32 h-80 w-80 rounded-full bg-fuchsia-200/40 blur-3xl dark:bg-fuchsia-900/15"></div>
                <div class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/20"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
                <div class="max-w-3xl">
                    <p class="eyebrow mb-3">Ton radar de sorties locales</p>
                    <h1 class="max-w-2xl text-3xl font-black leading-[1.08] tracking-[-0.045em] text-gray-950 dark:text-white sm:text-5xl">
                        Qu’est-ce qu’on fait <span class="text-brand-600 dark:text-brand-400">près de chez toi&nbsp;?</span>
                    </h1>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-gray-600 dark:text-gray-300 sm:text-lg">
                        Concerts, expos, ateliers et pépites locales sélectionnés selon tes envies.
                    </p>
                </div>

                <div class="mt-7 flex max-w-4xl flex-col gap-3 sm:flex-row">
                    <label class="relative block flex-1">
                        <span class="sr-only">Rechercher un événement</span>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16.5 16.5 4 4"/>
                        </svg>
                        <input type="search" x-model="search" @input.debounce.350ms="loadEvents()"
                               placeholder="Artiste, lieu, ambiance…" class="search-input !h-14 !rounded-2xl !pl-12 !shadow-lg !shadow-gray-900/5">
                    </label>
                    <button type="button" @click="useMyLocation()" :disabled="locating"
                            class="btn-secondary !h-14 !rounded-2xl !px-5" :aria-busy="locating.toString()">
                        <svg class="h-5 w-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3"/>
                        </svg>
                        <span x-text="locating ? 'Localisation…' : locationLabel"></span>
                    </button>
                    @auth
                        <button type="button" @click="openAddModal()" class="btn-primary !h-14 !rounded-2xl">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                            Proposer
                        </button>
                    @endauth
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <section aria-labelledby="quick-filters-title">
                <h2 id="quick-filters-title" class="sr-only">Filtres rapides</h2>
                <div class="-mx-4 overflow-x-auto px-4 pb-2 scrollbar-hide sm:mx-0 sm:px-0">
                    <div class="flex w-max gap-2">
                        <button type="button" @click="setDateFilter(null)" class="category-pill"
                                :class="!dateFilter && !freeOnly ? 'category-pill-active' : 'category-pill-inactive'"
                                :aria-pressed="(!dateFilter && !freeOnly).toString()">Tout</button>
                        <button type="button" @click="setDateFilter('today')" class="category-pill"
                                :class="dateFilter === 'today' ? 'category-pill-active' : 'category-pill-inactive'"
                                :aria-pressed="(dateFilter === 'today').toString()">Aujourd’hui</button>
                        <button type="button" @click="setDateFilter('tonight')" class="category-pill"
                                :class="dateFilter === 'tonight' ? 'category-pill-active' : 'category-pill-inactive'"
                                :aria-pressed="(dateFilter === 'tonight').toString()">Ce soir</button>
                        <button type="button" @click="setDateFilter('weekend')" class="category-pill"
                                :class="dateFilter === 'weekend' ? 'category-pill-active' : 'category-pill-inactive'"
                                :aria-pressed="(dateFilter === 'weekend').toString()">Ce week-end</button>
                        <button type="button" @click="freeOnly = !freeOnly; loadEvents()" class="category-pill"
                                :class="freeOnly ? 'category-pill-active' : 'category-pill-inactive'"
                                :aria-pressed="freeOnly.toString()">Gratuit</button>
                        <button x-show="coords" type="button" @click="radius = radius === 10 ? 25 : 10; loadEvents()" class="category-pill category-pill-inactive">
                            <span x-text="'À moins de ' + radius + ' km'"></span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="mt-3" aria-labelledby="categories-title">
                <h2 id="categories-title" class="sr-only">Catégories</h2>
                <div class="-mx-4 overflow-x-auto px-4 pb-2 scrollbar-hide sm:mx-0 sm:px-0">
                    <div class="flex w-max gap-2 sm:flex-wrap">
                        @foreach($categories as $cat)
                            <button type="button" @click="setCategory({{ $cat->id }})"
                                    class="category-pill shrink-0"
                                    :class="category === {{ $cat->id }} ? 'category-pill-active' : 'category-pill-inactive'"
                                    :aria-pressed="(category === {{ $cat->id }}).toString()">
                                <span aria-hidden="true">{{ $cat->icon }}</span>
                                <span>{{ $cat->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

            <div x-show="loading" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite" aria-label="Chargement des événements">
                <template x-for="i in 6" :key="i">
                    <div class="event-card animate-pulse">
                        <div class="aspect-[16/10] bg-gray-200 dark:bg-gray-800"></div>
                        <div class="space-y-3 p-5">
                            <div class="h-3 w-1/3 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                            <div class="h-6 w-4/5 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                            <div class="h-4 w-2/3 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!loading && error" x-cloak class="surface mt-8 p-8 text-center" role="alert">
                <svg class="mx-auto h-10 w-10 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v5m0 3.5v.01M10.3 4.5 2.7 18a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 4.5a2 2 0 0 0-3.4 0Z"/></svg>
                <h2 class="mt-3 text-lg font-bold">Impossible de charger les sorties</h2>
                <p class="mt-1 text-sm text-gray-500" x-text="error"></p>
                <button type="button" @click="loadEvents()" class="btn-primary mt-5">Réessayer</button>
            </div>

            <section x-show="!loading && !error && featuredEvents.length" x-cloak class="mt-8" aria-labelledby="featured-title">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Sélection du moment</p>
                        <h2 id="featured-title" class="mt-1 text-2xl font-black tracking-tight text-gray-950 dark:text-white sm:text-3xl">À ne pas manquer</h2>
                    </div>
                    <span class="hidden text-sm font-medium text-gray-500 sm:block">Mis à jour en continu</span>
                </div>

                <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 scrollbar-hide sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 lg:grid-cols-3">
                    <template x-for="event in featuredEvents" :key="'featured-' + event.id">
                        <article class="event-card group relative min-w-[84vw] snap-center sm:min-w-0">
                            <a :href="eventUrl(event)" class="absolute inset-0 z-10 rounded-[1.4rem]" :aria-label="'Voir ' + event.title"></a>
                            <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-brand-100 to-fuchsia-100 dark:from-brand-950 dark:to-fuchsia-950">
                                <template x-if="event.image_url">
                                    <img :src="event.image_url" :alt="event.title" loading="lazy" decoding="async" x-on:error="event.image_url = null"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </template>
                                <template x-if="!event.image_url">
                                    <div class="grid h-full place-items-center text-6xl opacity-40" aria-hidden="true" x-text="event.category?.icon || '✦'"></div>
                                </template>
                                <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-950/80 to-transparent"></div>
                                <div class="absolute left-4 top-4 rounded-2xl bg-white/95 px-3 py-2 text-center shadow-lg backdrop-blur dark:bg-gray-950/90">
                                    <span class="block text-xl font-black leading-none text-brand-600" x-text="formatDay(event.date_start)"></span>
                                    <span class="mt-1 block text-[10px] font-extrabold uppercase tracking-widest text-gray-500" x-text="formatMonth(event.date_start)"></span>
                                </div>
                                <button type="button" @click.prevent.stop="saveEvent(event)" class="icon-button absolute right-3 top-3 z-20 !bg-white/90 !text-gray-800 shadow-lg backdrop-blur dark:!bg-gray-950/85 dark:!text-white"
                                        :aria-label="event.is_saved ? 'Retirer des favoris' : 'Ajouter aux favoris'" :aria-pressed="Boolean(event.is_saved).toString()">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="event.is_saved ? 'currentColor' : 'none'" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
                                </button>
                                <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                                    <div class="mb-2 flex items-center gap-2 text-xs font-bold text-white/80">
                                        <span x-text="event.category?.name || 'Événement'"></span>
                                        <span aria-hidden="true">•</span>
                                        <span x-text="formatPrice(event.price)"></span>
                                    </div>
                                    <h3 class="line-clamp-2 text-xl font-black leading-tight" x-text="event.title"></h3>
                                    <p class="mt-2 flex items-center gap-1.5 truncate text-sm text-white/80">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                                        <span x-text="locationText(event)"></span>
                                    </p>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </section>

            <section x-show="!loading && !error && feedEvents.length" x-cloak class="mt-8 sm:mt-12" aria-labelledby="feed-title">
                <div class="mb-5">
                    <p class="eyebrow">À explorer</p>
                    <h2 id="feed-title" class="mt-1 text-2xl font-black tracking-tight text-gray-950 dark:text-white sm:text-3xl">Toutes les sorties</h2>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="event in feedEvents" :key="event.id">
                        <article class="event-card group relative">
                            <a :href="eventUrl(event)" class="absolute inset-0 z-10 rounded-[1.4rem]" :aria-label="'Voir ' + event.title"></a>
                            <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-brand-100 to-fuchsia-100 dark:from-brand-950 dark:to-fuchsia-950">
                                <template x-if="event.image_url">
                                    <img :src="event.image_url" :alt="event.title" loading="lazy" decoding="async" x-on:error="event.image_url = null"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </template>
                                <template x-if="!event.image_url">
                                    <div class="grid h-full place-items-center text-5xl opacity-40" aria-hidden="true" x-text="event.category?.icon || '✦'"></div>
                                </template>
                                <div class="absolute left-3 top-3 rounded-xl bg-white/95 px-2.5 py-2 text-center shadow backdrop-blur dark:bg-gray-950/90">
                                    <span class="block text-lg font-black leading-none text-brand-600" x-text="formatDay(event.date_start)"></span>
                                    <span class="mt-0.5 block text-[9px] font-extrabold uppercase tracking-wider text-gray-500" x-text="formatMonth(event.date_start)"></span>
                                </div>
                                <button type="button" @click.prevent.stop="saveEvent(event)" class="icon-button absolute right-3 top-3 z-20 !bg-white/90 !text-gray-800 shadow backdrop-blur dark:!bg-gray-950/85 dark:!text-white"
                                        :aria-label="event.is_saved ? 'Retirer des favoris' : 'Ajouter aux favoris'" :aria-pressed="Boolean(event.is_saved).toString()">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="event.is_saved ? 'currentColor' : 'none'" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
                                </button>
                            </div>
                            <div class="p-5">
                                <div class="mb-2 flex items-center justify-between gap-3 text-xs font-bold">
                                    <span class="text-brand-700 dark:text-brand-300" x-text="event.category?.name || 'Événement'"></span>
                                    <span class="text-gray-500" x-text="formatPrice(event.price)"></span>
                                </div>
                                <h3 class="line-clamp-2 text-lg font-black leading-snug text-gray-950 transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300" x-text="event.title"></h3>
                                <div class="mt-3 space-y-2 text-sm text-gray-500 dark:text-gray-400">
                                    <p class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                        <span x-text="formatDate(event.date_start)"></span>
                                    </p>
                                    <p class="flex items-center gap-2 truncate">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                                        <span class="truncate" x-text="locationText(event)"></span>
                                    </p>
                                </div>
                                <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-700 dark:text-brand-300">
                                    Découvrir
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                                </span>
                            </div>
                        </article>
                    </template>
                </div>
            </section>

            <div x-show="!loading && !error && events.length === 0" x-cloak class="py-20 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300" aria-hidden="true">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16v13H4V6Zm4-3v3m8-3v3M8 11h8m-8 4h5"/></svg>
                </div>
                <h2 class="mt-5 text-xl font-black">Aucune sortie trouvée</h2>
                <p class="mt-2 text-gray-500">Élargis la zone ou essaie d’autres filtres.</p>
                <button type="button" @click="resetFilters()" class="btn-primary mt-6">Réinitialiser les filtres</button>
            </div>

            <div x-show="!loading && !error && hasMore" class="mt-10 text-center">
                <button type="button" @click="loadMore()" :disabled="loadingMore" class="btn-secondary min-w-44" :aria-busy="loadingMore.toString()">
                    <span x-text="loadingMore ? 'Chargement…' : 'Afficher plus'"></span>
                </button>
            </div>
        </div>

        @auth
            <div x-show="showAddModal" x-trap.noscroll="showAddModal" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="add-event-title">
                <button type="button" class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm" @click="closeAddModal()" aria-label="Fermer la fenêtre"></button>
                <div class="relative z-10 max-h-[92vh] w-full overflow-y-auto rounded-t-[2rem] bg-white p-5 shadow-2xl dark:bg-gray-900 sm:max-w-xl sm:rounded-[2rem] sm:p-8">
                    <div class="mx-auto mb-5 h-1.5 w-12 rounded-full bg-gray-200 sm:hidden dark:bg-gray-700" aria-hidden="true"></div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="eyebrow">Contribution locale</p>
                            <h2 id="add-event-title" class="mt-1 text-2xl font-black">Proposer un événement</h2>
                            <p class="mt-2 text-sm text-gray-500">Notre équipe le vérifie avant publication.</p>
                        </div>
                        <button type="button" @click="closeAddModal()" class="icon-button shrink-0" aria-label="Fermer">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submitEvent()">
                        <div>
                            <label for="event-title" class="mb-1.5 block text-sm font-bold">Titre</label>
                            <input id="event-title" x-ref="eventTitle" type="text" x-model="newEvent.title" required maxlength="255" class="search-input" placeholder="Nom de l’événement">
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="event-date" class="mb-1.5 block text-sm font-bold">Date et heure</label>
                                <input id="event-date" type="datetime-local" x-model="newEvent.date_start" required class="search-input">
                            </div>
                            <div>
                                <label for="event-category" class="mb-1.5 block text-sm font-bold">Catégorie</label>
                                <select id="event-category" x-model="newEvent.category_id" class="search-input">
                                    <option value="">À déterminer</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="event-location" class="mb-1.5 block text-sm font-bold">Lieu</label>
                            <input id="event-location" type="text" x-model="newEvent.location" maxlength="255" class="search-input" placeholder="Salle, ville ou adresse">
                        </div>
                        <div>
                            <label for="event-source" class="mb-1.5 block text-sm font-bold">Lien officiel</label>
                            <input id="event-source" type="url" x-model="newEvent.source_url" class="search-input" placeholder="https://…">
                        </div>
                        <p x-show="formError" x-text="formError" class="rounded-xl bg-red-50 p-3 text-sm font-medium text-red-700 dark:bg-red-950/30 dark:text-red-300" role="alert"></p>
                        <button type="submit" :disabled="submitting" class="btn-primary w-full" :aria-busy="submitting.toString()">
                            <span x-text="submitting ? 'Envoi en cours…' : 'Envoyer pour validation'"></span>
                        </button>
                    </form>
                </div>
            </div>
        @endauth

        <div x-show="toast" x-transition x-cloak class="fixed bottom-24 left-1/2 z-[80] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-2xl bg-gray-950 px-5 py-3.5 text-center text-sm font-bold text-white shadow-2xl md:bottom-8" role="status" aria-live="polite" x-text="toast"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('auth', { user: {{ Auth::check() ? 'true' : 'false' }} });

                Alpine.data('eventFeed', () => ({
                    events: [],
                    loading: true,
                    loadingMore: false,
                    error: '',
                    search: '',
                    category: null,
                    dateFilter: null,
                    freeOnly: false,
                    coords: null,
                    radius: 25,
                    locationLabel: 'Autour de moi',
                    locating: false,
                    page: 1,
                    lastPage: 1,
                    requestController: null,
                    showAddModal: false,
                    submitting: false,
                    formError: '',
                    toast: '',
                    toastTimer: null,
                    newEvent: { title: '', date_start: '', category_id: '', location: '', source_url: '' },

                    get featuredEvents() { return this.events.slice(0, 3); },
                    get feedEvents() { return this.events.slice(3); },
                    get hasMore() { return this.page < this.lastPage; },

                    init() {
                        try {
                            const saved = JSON.parse(localStorage.getItem('feedevent-location'));
                            if (saved?.latitude && saved?.longitude) {
                                this.coords = saved;
                                this.locationLabel = 'À proximité';
                            }
                        } catch (_) {}
                        this.loadEvents();
                    },

                    params(page = 1) {
                        const params = new URLSearchParams({ page, per_page: 18 });
                        if (this.category) params.set('category_id', this.category);
                        if (this.search.trim()) params.set('search', this.search.trim());
                        if (this.dateFilter) params.set('date_filter', this.dateFilter);
                        if (this.freeOnly) params.set('free', '1');
                        if (this.coords) {
                            params.set('lat', this.coords.latitude);
                            params.set('lng', this.coords.longitude);
                            params.set('radius', this.radius);
                        }
                        return params;
                    },

                    async loadEvents() {
                        this.requestController?.abort();
                        this.requestController = new AbortController();
                        this.loading = true;
                        this.error = '';
                        this.page = 1;
                        try {
                            const response = await fetch(`/api/events?${this.params()}`, {
                                signal: this.requestController.signal,
                                headers: { Accept: 'application/json' },
                            });
                            if (!response.ok) throw new Error('Le service est momentanément indisponible.');
                            const data = await response.json();
                            this.events = data.data || [];
                            this.lastPage = data.last_page || 1;
                        } catch (error) {
                            if (error.name !== 'AbortError') this.error = error.message;
                        } finally {
                            this.loading = false;
                        }
                    },

                    async loadMore() {
                        if (!this.hasMore) return;
                        this.loadingMore = true;
                        try {
                            const nextPage = this.page + 1;
                            const response = await fetch(`/api/events?${this.params(nextPage)}`, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error();
                            const data = await response.json();
                            this.events = [...this.events, ...(data.data || [])];
                            this.page = nextPage;
                            this.lastPage = data.last_page || this.lastPage;
                        } catch (_) {
                            this.showToast('Impossible de charger plus d’événements.');
                        } finally {
                            this.loadingMore = false;
                        }
                    },

                    setDateFilter(value) {
                        this.dateFilter = this.dateFilter === value ? null : value;
                        if (!value) this.freeOnly = false;
                        this.loadEvents();
                    },

                    setCategory(id) {
                        this.category = this.category === id ? null : id;
                        this.loadEvents();
                    },

                    resetFilters() {
                        Object.assign(this, { search: '', category: null, dateFilter: null, freeOnly: false, coords: null, radius: 25, locationLabel: 'Autour de moi' });
                        localStorage.removeItem('feedevent-location');
                        this.loadEvents();
                    },

                    useMyLocation() {
                        if (!navigator.geolocation) {
                            this.showToast('La géolocalisation n’est pas disponible sur cet appareil.');
                            return;
                        }
                        this.locating = true;
                        navigator.geolocation.getCurrentPosition(
                            position => {
                                this.coords = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                                localStorage.setItem('feedevent-location', JSON.stringify(this.coords));
                                this.locationLabel = 'À proximité';
                                this.locating = false;
                                this.loadEvents();
                            },
                            () => {
                                this.locating = false;
                                this.showToast('Autorise la localisation pour voir les sorties proches.');
                            },
                            { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
                        );
                    },

                    async request(url, options = {}) {
                        const response = await fetch(url, {
                            ...options,
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                ...(options.headers || {}),
                            },
                        });
                        if (response.status === 401 || response.status === 419) {
                            window.location.href = '{{ route('login') }}';
                            throw new Error('Authentification requise');
                        }
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'Une erreur est survenue.');
                        return data;
                    },

                    async saveEvent(event) {
                        if (!this.$store.auth.user) {
                            window.location.href = '{{ route('login') }}';
                            return;
                        }
                        const previous = Boolean(event.is_saved);
                        event.is_saved = !previous;
                        try {
                            const data = await this.request(`/api/events/${event.id}/save`, { method: 'POST' });
                            event.is_saved = data.is_saved;
                            this.showToast(data.is_saved ? 'Ajouté à tes favoris' : 'Retiré de tes favoris');
                        } catch (error) {
                            event.is_saved = previous;
                            if (error.message !== 'Authentification requise') this.showToast(error.message);
                        }
                    },

                    openAddModal() {
                        this.showAddModal = true;
                        this.$nextTick(() => this.$refs.eventTitle?.focus());
                    },

                    closeAddModal() {
                        if (!this.showAddModal) return;
                        this.showAddModal = false;
                    },

                    async submitEvent() {
                        this.submitting = true;
                        this.formError = '';
                        try {
                            const data = await this.request('/api/events', {
                                method: 'POST',
                                body: JSON.stringify(this.newEvent),
                            });
                            this.closeAddModal();
                            this.newEvent = { title: '', date_start: '', category_id: '', location: '', source_url: '' };
                            this.showToast(data.message);
                        } catch (error) {
                            if (error.message !== 'Authentification requise') this.formError = error.message;
                        } finally {
                            this.submitting = false;
                        }
                    },

                    showToast(message) {
                        this.toast = message;
                        clearTimeout(this.toastTimer);
                        this.toastTimer = setTimeout(() => this.toast = '', 3200);
                    },

                    eventUrl(event) { return `/events/${event.id}`; },
                    formatDay(date) { return new Date(date).getDate(); },
                    formatMonth(date) { return new Intl.DateTimeFormat('fr-FR', { month: 'short' }).format(new Date(date)).replace('.', ''); },
                    formatDate(date) { return new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(date)); },
                    formatPrice(price) { return price === null || Number(price) === 0 ? 'Gratuit' : `${Number(price).toLocaleString('fr-FR')} €`; },
                    locationText(event) {
                        const distance = event.distance_km ? `${Number(event.distance_km).toFixed(1)} km · ` : '';
                        return distance + (event.location || event.address || 'Lieu à confirmer');
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
