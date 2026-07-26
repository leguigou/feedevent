<x-app-layout>
    <div class="py-7 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="eyebrow">Centralise tes agendas</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-950 dark:text-white sm:text-4xl">
                    Importer des événements
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-gray-600 dark:text-gray-300">
                    Glisse un calendrier ICS ou récupère les informations de la page ouverte avec le connecteur Chrome. Chaque événement arrive comme brouillon à vérifier.
                </p>
            </header>

            @if (session('status') === 'connector-revoked')
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300" role="status">
                    L’accès du connecteur a été révoqué.
                </div>
            @endif

            @if (session('ics-import'))
                @php($result = session('ics-import'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300" role="status">
                    {{ $result['imported'] }} événement(s) importé(s) comme brouillons.
                    @if ($result['skipped'])
                        {{ $result['skipped'] }} doublon(s) ignoré(s).
                    @endif
                    @if ($result['failed'])
                        {{ $result['failed'] }} import(s) en erreur.
                    @endif
                </div>
            @endif

            <section class="surface p-5 sm:p-7">
                <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
                    <div>
                        <p class="eyebrow">Calendriers externes</p>
                        <h2 class="mt-1 text-2xl font-black text-gray-950 dark:text-white">Importer un fichier ICS</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Dépose un export Google Calendar, Apple Calendar, Outlook ou Facebook. Les événements sont créés comme brouillons et les doublons sont ignorés.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('connector.ics.store') }}"
                        enctype="multipart/form-data"
                        x-data="{ dragging: false, fileName: '' }"
                        class="space-y-3"
                    >
                        @csrf
                        <label
                            class="group flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed px-5 py-7 text-center transition"
                            :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/30' : 'border-gray-300 bg-gray-50 hover:border-brand-400 hover:bg-brand-50/50 dark:border-gray-700 dark:bg-gray-900/60 dark:hover:border-brand-600'"
                            @dragenter.prevent="dragging = true"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            @drop.prevent="
                                dragging = false;
                                if ($event.dataTransfer.files.length) {
                                    $refs.calendar.files = $event.dataTransfer.files;
                                    fileName = $event.dataTransfer.files[0].name;
                                }
                            "
                        >
                            <svg class="h-9 w-9 text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0 4 4m-4-4L8 8M5 14v5h14v-5"/>
                            </svg>
                            <span class="mt-3 font-extrabold text-gray-950 dark:text-white" x-text="fileName || 'Glisse ton fichier .ics ici'"></span>
                            <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">ou clique pour le choisir · 5 Mo maximum</span>
                            <input
                                x-ref="calendar"
                                class="sr-only"
                                type="file"
                                name="calendar"
                                accept=".ics,text/calendar"
                                required
                                @change="fileName = $event.target.files[0]?.name || ''"
                            >
                        </label>

                        @error('calendar')
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="!fileName"
                            :class="{ 'cursor-not-allowed opacity-50': !fileName }"
                        >
                            Importer comme brouillons
                        </button>
                    </form>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-600 via-violet-700 to-fuchsia-700 p-6 text-white shadow-xl shadow-brand-900/20 sm:p-9">
                <div class="grid gap-7 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold">Manifest V3 · accès à la demande</span>
                        <h2 class="mt-4 text-2xl font-black sm:text-3xl">Installer FeedEvent dans Chrome</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-violet-100">
                            Le téléchargement crée un jeton personnel limité aux imports. Il expire après 180 jours et peut être révoqué ci-dessous.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('connector.download') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-extrabold text-brand-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-violet-50 focus:outline-none focus:ring-4 focus:ring-white/30 lg:w-auto">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14"/>
                            </svg>
                            Télécharger le connecteur
                        </button>
                    </form>
                </div>
            </section>

            <section class="surface p-5 sm:p-7">
                <h2 class="text-xl font-black text-gray-950 dark:text-white">Installation en trois étapes</h2>
                <ol class="mt-5 grid gap-4 md:grid-cols-3">
                    <li class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-brand-100 text-sm font-black text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">1</span>
                        <h3 class="mt-3 font-bold text-gray-950 dark:text-white">Décompresse le ZIP</h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">Conserve le dossier obtenu dans un emplacement privé.</p>
                    </li>
                    <li class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-brand-100 text-sm font-black text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">2</span>
                        <h3 class="mt-3 font-bold text-gray-950 dark:text-white">Ouvre les extensions</h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">Va sur <code>chrome://extensions</code> et active le mode développeur.</p>
                    </li>
                    <li class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-brand-100 text-sm font-black text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">3</span>
                        <h3 class="mt-3 font-bold text-gray-950 dark:text-white">Charge le dossier</h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">Clique sur « Charger l’extension non empaquetée » et sélectionne le dossier.</p>
                    </li>
                </ol>
            </section>

            <section class="surface overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-5 dark:border-gray-800 sm:px-7">
                    <h2 class="text-xl font-black text-gray-950 dark:text-white">Accès autorisés</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Révoque immédiatement les téléchargements que tu n’utilises plus.</p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($tokens as $token)
                        @php
                            $active = $token->revoked_at === null && ($token->expires_at === null || $token->expires_at->isFuture());
                        @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-bold text-gray-950 dark:text-white">{{ $token->name }}</p>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $active ? 'bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                        {{ $active ? 'Actif' : 'Révoqué ou expiré' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Dernière utilisation : {{ $token->last_used_at?->diffForHumans() ?? 'jamais' }}
                                    · Expiration : {{ $token->expires_at?->format('d/m/Y') ?? 'aucune' }}
                                </p>
                            </div>

                            @if ($active)
                                <form method="POST" action="{{ route('connector.tokens.revoke', $token) }}" onsubmit="return confirm('Révoquer cet accès ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                        Révoquer
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucun connecteur téléchargé pour le moment.
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
                <strong>Bon usage :</strong> le connecteur analyse seulement la page ouverte après ton clic. Vérifie les droits et les conditions du site source avant d’importer un contenu.
            </aside>
        </div>
    </div>
</x-app-layout>
