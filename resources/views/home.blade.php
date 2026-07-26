<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                🎉 Événements à proximité
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar') }}" class="btn-ghost text-sm">
                    📅
                </a>
                <a href="{{ route('map') }}" class="btn-ghost text-sm">
                    🗺️
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8" x-data="eventFeed()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Barre de recherche -->
            <div class="relative mb-4">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                <input type="text" x-model="search" @input.debounce="loadEvents()"
                    placeholder="Rechercher un événement..."
                    class="search-input !pl-11">
            </div>

            <!-- Catégories - scroll horizontal mobile -->
            <div class="mb-5 overflow-x-auto scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex gap-2 w-max sm:w-full sm:flex-wrap sm:gap-2 pb-1">
                    <button @click="category = null; loadEvents()"
                        class="category-pill shrink-0"
                        :class="!category ? 'category-pill-active' : 'category-pill-inactive'">
                        Tous
                    </button>
                    @foreach($categories as $cat)
                    <button @click="category = {{ $cat->id }}; loadEvents()"
                        class="category-pill shrink-0"
                        :class="category === {{ $cat->id }} ? 'category-pill-active' : 'category-pill-inactive'">
                        <span>{{ $cat->icon }}</span>
                        <span class="hidden sm:inline">{{ $cat->name }}</span>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Skeleton loading -->
            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <template x-for="i in 6" :key="i">
                    <div class="event-card animate-pulse">
                        <div class="h-40 bg-gray-200 dark:bg-gray-800"></div>
                        <div class="p-4 space-y-3">
                            <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded-full w-1/3"></div>
                            <div class="h-5 bg-gray-200 dark:bg-gray-800 rounded-full w-3/4"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded-full w-full"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded-full w-1/2"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Grille d'événements -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <template x-for="event in events" :key="event.id">
                    <div class="event-card group">
                        <!-- Image -->
                        <div class="relative h-40 sm:h-48 overflow-hidden bg-gradient-to-br from-brand-100 to-purple-100 dark:from-brand-900/50 dark:to-purple-900/50">
                            <template x-if="event.image_url">
                                <img :src="event.image_url" :alt="event.title"
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </template>
                            <template x-if="!event.image_url">
                                <div class="h-full w-full flex items-center justify-center">
                                    <span class="text-5xl opacity-30" x-text="event.category?.icon || '📌'"></span>
                                </div>
                            </template>
                            <!-- Badge date -->
                            <div class="absolute top-3 left-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl px-3 py-1.5 text-xs font-bold shadow-sm">
                                <div x-text="formatDay(event.date_start)" class="text-lg leading-none text-brand-600 dark:text-brand-400"></div>
                                <div x-text="formatMonth(event.date_start)" class="text-gray-500 dark:text-gray-400 uppercase tracking-wider"></div>
                            </div>
                            <!-- Badge catégorie -->
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm shadow-sm"
                                      :style="{ color: event.category?.color || '#6366f1' }">
                                    <span x-text="event.category?.icon || '📌'"></span>
                                    <span x-text="event.category?.name || 'Autre'" class="hidden sm:inline"></span>
                                </span>
                            </div>
                            <!-- Prix -->
                            <div x-show="event.price" class="absolute bottom-3 left-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-green-500 text-white shadow-sm">
                                    <span x-text="event.price + '€'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- Titre -->
                            <h3 class="font-bold text-gray-900 dark:text-white mb-1.5 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-1" 
                                x-text="event.title"></h3>
                            
                            <!-- Description -->
                            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-3" 
                               x-text="event.description || 'Aucune description'"></p>

                            <!-- Infos -->
                            <div class="space-y-1.5 mb-4">
                                <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500" x-show="event.location">
                                    <span>📍</span>
                                    <span class="truncate" x-text="event.location"></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                                    <span>🕐</span>
                                    <span x-text="formatTime(event.date_start)"></span>
                                    <span x-show="event.date_end" x-text="'→ ' + formatDateTime(event.date_end)"></span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-1" x-show="$store.auth?.user">
                                    <button @click="like(event)" :disabled="prefLoading === event.id"
                                        class="like-btn"
                                        :class="event.user_preference === 'like' ? 'like-btn-active' : 'like-btn-inactive'">
                                        <span>👍</span>
                                    </button>
                                    <button @click="dislike(event)" :disabled="prefLoading === event.id"
                                        class="like-btn"
                                        :class="event.user_preference === 'dislike' ? 'dislike-btn-active' : 'like-btn-inactive'">
                                        <span>👎</span>
                                    </button>
                                </div>
                                <button @click="saveEvent(event)" 
                                    class="like-btn like-btn-inactive"
                                    :class="event.is_saved ? 'text-brand-500' : ''">
                                    <span x-text="event.is_saved ? '💜' : '🤍'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty state -->
            <div x-show="!loading && events.length === 0" 
                 class="text-center py-16 sm:py-24">
                <div class="text-7xl mb-6">📭</div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Aucun événement trouvé</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Essaie de modifier tes filtres</p>
                <button @click="category = null; search = ''; loadEvents()" class="btn-primary">
                    🔄 Réinitialiser les filtres
                </button>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && events.length > 0 && hasMore" class="mt-8 text-center">
                <button @click="loadMore()" :disabled="loadingMore"
                    class="btn-secondary">
                    <span x-show="!loadingMore">Afficher plus</span>
                    <span x-show="loadingMore">Chargement...</span>
                </button>
            </div>
        </div>

        <!-- FAB - Ajouter un événement (mobile) -->
        <button @click="showAddModal = true"
            class="fixed bottom-6 right-6 sm:bottom-8 sm:right-8 z-40 w-14 h-14 sm:w-16 sm:h-16 
                   rounded-2xl bg-brand-500 text-white shadow-xl hover:bg-brand-600 
                   hover:shadow-2xl hover:scale-105 active:scale-95 
                   transition-all duration-200 flex items-center justify-center">
            <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        <!-- Modal Ajout -->
        <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
             x-cloak @click.away="showAddModal = false">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative z-10 w-full sm:max-w-lg bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 sm:p-8 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ajouter un événement</h3>
                    <button @click="showAddModal = false" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mode manuel -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Titre</label>
                    <input type="text" x-model="newEvent.title" placeholder="Nom de l'événement" class="search-input">
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                        <input type="datetime-local" x-model="newEvent.date_start" class="search-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catégorie</label>
                        <select x-model="newEvent.category_id" class="search-input">
                            <option value="">Sélectionner</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lien URL ou image</label>
                    <input type="text" x-model="newEvent.source_url" placeholder="https://..." class="search-input">
                    <p class="mt-1 text-xs text-gray-400">Un LLM complétera automatiquement les infos à partir du lien</p>
                </div>

                <button @click="submitEvent()" :disabled="submitting"
                    class="btn-primary w-full justify-center text-base">
                    <span x-show="!submitting">✨ Ajouter l'événement</span>
                    <span x-show="submitting">⏳ Analyse en cours...</span>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            // Store auth global
            Alpine.store('auth', {
                user: {{ Auth::check() ? 'true' : 'false' }}
            });

            Alpine.data('eventFeed', () => ({
                events: [],
                loading: true,
                loadingMore: false,
                category: null,
                search: '',
                prefLoading: null,
                page: 1,
                hasMore: true,
                lastPage: 1,
                
                showAddModal: false,
                submitting: false,
                newEvent: {
                    title: '',
                    date_start: '',
                    category_id: '',
                    source_url: '',
                },

                async init() {
                    await this.loadEvents();
                    // Watch auth store
                    this.$watch('$store.auth.user', () => this.loadEvents());
                },

                async loadEvents() {
                    this.loading = true;
                    this.page = 1;
                    this.hasMore = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.category) params.set('category_id', this.category);
                        if (this.search) params.set('search', this.search);
                        params.set('per_page', '12');

                        const res = await fetch(`/api/events?${params}`);
                        const data = await res.json();
                        this.events = data.data || [];
                        this.lastPage = data.last_page || 1;
                    } catch (e) {
                        console.error('Erreur chargement events', e);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadMore() {
                    if (this.page >= this.lastPage) { this.hasMore = false; return; }
                    this.loadingMore = true;
                    this.page++;
                    try {
                        const params = new URLSearchParams();
                        if (this.category) params.set('category_id', this.category);
                        if (this.search) params.set('search', this.search);
                        params.set('page', this.page);
                        params.set('per_page', '12');

                        const res = await fetch(`/api/events?${params}`);
                        const data = await res.json();
                        this.events = [...this.events, ...(data.data || [])];
                        this.lastPage = data.last_page || 1;
                        if (this.page >= this.lastPage) this.hasMore = false;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loadingMore = false;
                    }
                },

                formatDay(date) {
                    return new Date(date).getDate();
                },
                formatMonth(date) {
                    return new Date(date).toLocaleDateString('fr-FR', { month: 'short' });
                },
                formatTime(date) {
                    return new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                },
                formatDateTime(date) {
                    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                },

                async like(event) {
                    this.prefLoading = event.id;
                    try {
                        const res = await fetch(`/api/events/${event.id}/like`, { method: 'POST' });
                        if (res.ok) event.user_preference = event.user_preference === 'like' ? null : 'like';
                    } catch (e) { console.error(e); }
                    finally { this.prefLoading = null; }
                },

                async dislike(event) {
                    this.prefLoading = event.id;
                    try {
                        const res = await fetch(`/api/events/${event.id}/dislike`, { method: 'POST' });
                        if (res.ok) event.user_preference = event.user_preference === 'dislike' ? null : 'dislike';
                    } catch (e) { console.error(e); }
                    finally { this.prefLoading = null; }
                },

                saveEvent(event) {
                    event.is_saved = !event.is_saved;
                },

                async submitEvent() {
                    this.submitting = true;
                    try {
                        const res = await fetch('/api/events', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                title: this.newEvent.title || 'Événement',
                                date_start: this.newEvent.date_start || new Date().toISOString().slice(0, 16),
                                category_id: this.newEvent.category_id || null,
                                source_url: this.newEvent.source_url || null,
                            })
                        });
                        if (res.ok) {
                            this.showAddModal = false;
                            this.newEvent = { title: '', date_start: '', category_id: '', source_url: '' };
                            await this.loadEvents();
                        }
                    } catch (e) { console.error(e); }
                    finally { this.submitting = false; }
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
