<x-app-layout>
    @push('meta')
        <meta property="og:type" content="event">
        <meta property="og:title" content="{{ $event->title }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ route('events.show', $event) }}">
        @if($event->image_url)<meta property="og:image" content="{{ $event->image_url }}">@endif
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $event->description,
            'startDate' => $event->date_start->toIso8601String(),
            'endDate' => $event->date_end?->toIso8601String(),
            'image' => $event->image_url ? [$event->image_url] : null,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => ['@type' => 'Place', 'name' => $event->location, 'address' => $event->address],
            'organizer' => $event->organizer ? ['@type' => 'Organization', 'name' => $event->organizer] : null,
            'offers' => ['@type' => 'Offer', 'price' => $event->price ?? 0, 'priceCurrency' => 'EUR', 'url' => $safeSourceUrl ?: route('events.show', $event)],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush

    <div x-data="eventDetail({{ $event->id }}, {{ $isSaved ? 'true' : 'false' }}, @js($userPreference))">
        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
            <a href="{{ route('home') }}" class="btn-ghost -ml-3 mb-4">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>
                Retour aux sorties
            </a>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(320px,.75fr)]">
                <div>
                    <div class="relative aspect-[16/10] overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-brand-100 to-fuchsia-100 shadow-xl shadow-gray-900/10 dark:from-brand-950 dark:to-fuchsia-950 sm:aspect-[16/9]">
                        @if($event->image_url)
                            <img src="{{ $event->image_url }}" alt="{{ $event->title }}" class="h-full w-full object-cover" fetchpriority="high" decoding="async">
                        @else
                            <div class="grid h-full place-items-center text-8xl opacity-40" aria-hidden="true">{{ $event->category?->icon ?: '✦' }}</div>
                        @endif
                        <div class="absolute left-4 top-4 rounded-2xl bg-white/95 px-3.5 py-2.5 text-center shadow-lg backdrop-blur dark:bg-gray-950/90">
                            <span class="block text-2xl font-black leading-none text-brand-600">{{ $event->date_start->format('d') }}</span>
                            <span class="mt-1 block text-[10px] font-extrabold uppercase tracking-widest text-gray-500">{{ $event->date_start->translatedFormat('M') }}</span>
                        </div>
                    </div>

                    <div class="pt-6 sm:pt-8">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-sm font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                <span aria-hidden="true">{{ $event->category?->icon ?: '✦' }}</span>
                                {{ $event->category?->name ?: 'Événement' }}
                            </span>
                            <span class="inline-flex min-h-9 items-center rounded-full bg-gray-100 px-3 py-1.5 text-sm font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ is_null($event->price) || (float) $event->price === 0.0 ? 'Gratuit' : number_format((float) $event->price, 2, ',', ' ').' €' }}
                            </span>
                        </div>

                        <h1 class="mt-4 max-w-4xl text-3xl font-black leading-[1.08] tracking-[-0.04em] text-gray-950 dark:text-white sm:text-5xl">{{ $event->title }}</h1>
                        @if($event->organizer)
                            <p class="mt-3 text-sm font-medium text-gray-500">Proposé par <span class="font-bold text-gray-800 dark:text-gray-200">{{ $event->organizer }}</span></p>
                        @endif

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="surface flex items-start gap-3 p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Quand</p>
                                    <p class="mt-1 font-bold">{{ $event->date_start->translatedFormat('l j F · H:i') }}</p>
                                    @if($event->date_end)<p class="mt-0.5 text-sm text-gray-500">Jusqu’au {{ $event->date_end->translatedFormat('j F · H:i') }}</p>@endif
                                </div>
                            </div>
                            <div class="surface flex items-start gap-3 p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-300">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Où</p>
                                    <p class="mt-1 font-bold">{{ $event->location ?: 'Lieu à confirmer' }}</p>
                                    @if($event->address)<p class="mt-0.5 text-sm text-gray-500">{{ $event->address }}</p>@endif
                                </div>
                            </div>
                        </div>

                        @if($event->description)
                            <section class="mt-8" aria-labelledby="about-title">
                                <h2 id="about-title" class="text-2xl font-black tracking-tight">À propos</h2>
                                <div class="mt-3 whitespace-pre-line text-base leading-7 text-gray-600 dark:text-gray-300">{{ $event->description }}</div>
                            </section>
                        @endif

                        @auth
                            <div class="mt-8 rounded-2xl border border-brand-100 bg-brand-50/70 p-4 dark:border-brand-900/50 dark:bg-brand-950/20">
                                <p class="text-sm font-black">Ce type de sortie te plaît ?</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ta réponse améliore progressivement tes recommandations.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" @click="setPreference('like')" :disabled="preferenceLoading"
                                            class="btn-secondary" :class="preference === 'like' ? '!border-brand-500 !bg-brand-600 !text-white' : ''"
                                            :aria-pressed="(preference === 'like').toString()">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10v11H4V10h3Zm4 11H9V10l3-7c2 0 3 1.4 2.5 3L14 8h5a2 2 0 0 1 2 2l-1 8a3 3 0 0 1-3 3h-6Z"/></svg>
                                        Ça me tente
                                    </button>
                                    <button type="button" @click="setPreference('dislike')" :disabled="preferenceLoading"
                                            class="btn-secondary" :class="preference === 'dislike' ? '!border-gray-700 !bg-gray-800 !text-white' : ''"
                                            :aria-pressed="(preference === 'dislike').toString()">
                                        Pas pour moi
                                    </button>
                                </div>
                            </div>
                        @endauth

                        <div class="mt-8 flex flex-wrap gap-2">
                            <a href="{{ route('events.calendar', $event) }}" class="btn-secondary">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                Ajouter au calendrier
                            </a>
                            @if($event->latitude && $event->longitude)
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $event->latitude }},{{ $event->longitude }}" target="_blank" rel="noopener" class="btn-secondary">Itinéraire</a>
                            @elseif($event->address || $event->location)
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($event->address ?: $event->location) }}" target="_blank" rel="noopener" class="btn-secondary">Itinéraire</a>
                            @endif
                            <button type="button" @click="share()" class="btn-secondary">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="18" cy="5" r="2.5" stroke-width="1.8"/><circle cx="6" cy="12" r="2.5" stroke-width="1.8"/><circle cx="18" cy="19" r="2.5" stroke-width="1.8"/><path stroke-width="1.8" d="m8.2 10.8 7.6-4.5m-7.6 6.9 7.6 4.5"/></svg>
                                Partager
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="hidden lg:block">
                    <div class="surface sticky top-24 p-6">
                        <p class="eyebrow">Prêt pour la sortie ?</p>
                        <p class="mt-2 text-2xl font-black">{{ is_null($event->price) || (float) $event->price === 0.0 ? 'Entrée gratuite' : number_format((float) $event->price, 2, ',', ' ').' €' }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">Vérifie les disponibilités et les informations auprès de l’organisateur.</p>
                        @if($safeSourceUrl)
                            <a href="{{ $safeSourceUrl }}" target="_blank" rel="noopener nofollow" class="btn-primary mt-5 w-full">Voir le site officiel</a>
                        @else
                            <a href="{{ route('events.calendar', $event) }}" class="btn-primary mt-5 w-full">Ajouter à mon agenda</a>
                        @endif
                        <button type="button" @click="save()" :disabled="saving" class="btn-secondary mt-3 w-full" :aria-pressed="saved.toString()">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
                            <span x-text="saved ? 'Dans mes favoris' : 'Ajouter aux favoris'"></span>
                        </button>
                    </div>
                </aside>
            </div>

            @if($relatedEvents->isNotEmpty())
                <section class="mt-14 border-t border-gray-200 pt-10 dark:border-gray-800" aria-labelledby="related-title">
                    <p class="eyebrow">Dans le même esprit</p>
                    <h2 id="related-title" class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Tu pourrais aussi aimer</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($relatedEvents as $related)
                            <a href="{{ route('events.show', $related) }}" class="event-card group">
                                <div class="aspect-[16/9] overflow-hidden bg-brand-100 dark:bg-brand-950">
                                    @if($related->image_url)
                                        <img src="{{ $related->image_url }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="grid h-full place-items-center text-5xl opacity-40" aria-hidden="true">{{ $related->category?->icon ?: '✦' }}</div>
                                    @endif
                                </div>
                                <div class="p-5">
                                    <p class="text-xs font-bold text-brand-700 dark:text-brand-300">{{ $related->date_start->translatedFormat('D j M · H:i') }}</p>
                                    <h3 class="mt-2 line-clamp-2 text-lg font-black">{{ $related->title }}</h3>
                                    <p class="mt-2 truncate text-sm text-gray-500">{{ $related->location ?: 'Lieu à confirmer' }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <div class="fixed inset-x-0 bottom-[calc(4.7rem+env(safe-area-inset-bottom))] z-40 border-t border-gray-200 bg-white/95 p-3 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-950/95 md:bottom-0 lg:hidden">
            <div class="mx-auto flex max-w-xl gap-2">
                <button type="button" @click="save()" :disabled="saving" class="icon-button !h-12 !w-12 shrink-0 border border-gray-200 dark:border-gray-700" :aria-label="saved ? 'Retirer des favoris' : 'Ajouter aux favoris'" :aria-pressed="saved.toString()">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
                </button>
                @if($safeSourceUrl)
                    <a href="{{ $safeSourceUrl }}" target="_blank" rel="noopener nofollow" class="btn-primary h-12 flex-1">Voir le site officiel</a>
                @else
                    <a href="{{ route('events.calendar', $event) }}" class="btn-primary h-12 flex-1">Ajouter à mon agenda</a>
                @endif
            </div>
        </div>

        <div x-show="message" x-transition x-cloak class="fixed bottom-40 left-1/2 z-[80] -translate-x-1/2 rounded-xl bg-gray-950 px-4 py-3 text-sm font-bold text-white shadow-xl lg:bottom-8" role="status" aria-live="polite" x-text="message"></div>
    </div>

    @push('scripts')
        <script>
            function eventDetail(eventId, initialSaved, initialPreference) {
                return {
                    saved: initialSaved,
                    saving: false,
                    preference: initialPreference,
                    preferenceLoading: false,
                    message: '',
                    async save() {
                        @guest
                            window.location.href = '{{ route('login') }}';
                            return;
                        @endguest
                        this.saving = true;
                        try {
                            const response = await fetch(`/api/events/${eventId}/save`, {
                                method: 'POST',
                                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            });
                            if (!response.ok) throw new Error();
                            const data = await response.json();
                            this.saved = data.is_saved;
                            this.notify(this.saved ? 'Ajouté à tes favoris' : 'Retiré de tes favoris');
                        } catch (_) {
                            this.notify('Impossible de modifier le favori.');
                        } finally {
                            this.saving = false;
                        }
                    },
                    async setPreference(type) {
                        this.preferenceLoading = true;
                        try {
                            const response = await fetch(`/api/events/${eventId}/${type}`, {
                                method: 'POST',
                                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            });
                            if (!response.ok) throw new Error();
                            const data = await response.json();
                            this.preference = data.preference;
                            this.notify(data.preference ? 'Préférence enregistrée' : 'Préférence retirée');
                        } catch (_) {
                            this.notify('Impossible d’enregistrer ta préférence.');
                        } finally {
                            this.preferenceLoading = false;
                        }
                    },
                    async share() {
                        const shareData = { title: @js($event->title), text: @js($pageDescription), url: window.location.href };
                        try {
                            if (navigator.share) await navigator.share(shareData);
                            else {
                                await navigator.clipboard.writeText(window.location.href);
                                this.notify('Lien copié');
                            }
                        } catch (_) {}
                    },
                    notify(text) {
                        this.message = text;
                        setTimeout(() => this.message = '', 3000);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
