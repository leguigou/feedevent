<x-app-layout>
    <div class="py-7 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Espace organisateur</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-950 dark:text-white sm:text-4xl">
                        Mes événements
                    </h1>
                    <p class="mt-2 max-w-2xl text-gray-600 dark:text-gray-300">
                        Retrouve, modifie et suis tous les événements importés avec ton compte.
                    </p>
                </div>
                <a href="{{ route('connector.index') }}" class="btn-primary">Importer des événements</a>
            </header>

            @if (session('status'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300" role="status">
                    @if (session('status') === 'event-deleted')
                        L’événement a été supprimé.
                    @elseif (session('status') === 'event-updated')
                        Les modifications ont été enregistrées.
                    @else
                        Le statut de l’événement a été mis à jour.
                    @endif
                </div>
            @endif

            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a href="{{ route('my-events.index') }}" class="surface p-4 transition hover:-translate-y-0.5 {{ $status === null ? 'ring-2 ring-brand-500' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tous</p>
                    <p class="mt-1 text-2xl font-black text-gray-950 dark:text-white">{{ $statusCounts->sum() }}</p>
                </a>
                <a href="{{ route('my-events.index', ['status' => 'published']) }}" class="surface p-4 transition hover:-translate-y-0.5 {{ $status === 'published' ? 'ring-2 ring-green-500' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-wide text-green-600 dark:text-green-400">Publiés</p>
                    <p class="mt-1 text-2xl font-black text-gray-950 dark:text-white">{{ $statusCounts->get('published', 0) }}</p>
                </a>
                <a href="{{ route('my-events.index', ['status' => 'draft']) }}" class="surface p-4 transition hover:-translate-y-0.5 {{ $status === 'draft' ? 'ring-2 ring-amber-500' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">Brouillons</p>
                    <p class="mt-1 text-2xl font-black text-gray-950 dark:text-white">{{ $statusCounts->get('draft', 0) }}</p>
                </a>
                <a href="{{ route('my-events.index', ['status' => 'archived']) }}" class="surface p-4 transition hover:-translate-y-0.5 {{ $status === 'archived' ? 'ring-2 ring-gray-500' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Archivés</p>
                    <p class="mt-1 text-2xl font-black text-gray-950 dark:text-white">{{ $statusCounts->get('archived', 0) }}</p>
                </a>
            </section>

            <section>
                @if ($events->isEmpty())
                    <div class="surface px-5 py-14 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-xl font-black text-gray-950 dark:text-white">Aucun événement ici</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Importe un fichier ICS ou utilise l’extension Chrome pour commencer.</p>
                        <a href="{{ route('connector.index') }}" class="btn-primary mt-5">Accéder aux imports</a>
                    </div>
                @else
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($events as $event)
                            <article class="surface flex min-h-full flex-col overflow-hidden">
                                @if ($event->image_url)
                                    <img src="{{ $event->image_url }}" alt="" class="aspect-[16/8] w-full object-cover" loading="lazy" referrerpolicy="no-referrer">
                                @endif
                                <div class="flex flex-1 flex-col p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-extrabold
                                            {{ $event->status === 'published'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-300'
                                                : ($event->status === 'draft'
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }}">
                                            {{ $event->status === 'published' ? 'Publié' : ($event->status === 'draft' ? 'Brouillon' : 'Archivé') }}
                                        </span>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            {{ $event->llm_meta['connector'] ?? $event->source_type }}
                                        </span>
                                    </div>

                                    <h2 class="mt-3 text-lg font-black leading-snug text-gray-950 dark:text-white">{{ $event->title }}</h2>
                                    <p class="mt-2 text-sm font-semibold text-brand-700 dark:text-brand-300">
                                        {{ $event->date_start->translatedFormat('D j M Y · H:i') }}
                                    </p>
                                    @if ($event->location)
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $event->location }}</p>
                                    @endif

                                    <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                        <a href="{{ route('my-events.edit', $event) }}" class="btn-secondary !min-h-9 !px-3 !py-2 text-xs">Modifier</a>
                                        @if ($event->status === 'published')
                                            <a href="{{ route('events.show', $event) }}" class="btn-ghost !min-h-9 !px-3 !py-2 text-xs">Voir</a>
                                            <form method="POST" action="{{ route('my-events.status', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="archived">
                                                <button class="btn-ghost !min-h-9 !px-3 !py-2 text-xs" type="submit">Archiver</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('my-events.status', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="published">
                                                <button class="btn-primary !min-h-9 !px-3 !py-2 text-xs" type="submit">Publier</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('my-events.destroy', $event) }}" onsubmit="return confirm('Supprimer cet événement ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="min-h-9 rounded-xl px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">{{ $events->links() }}</div>
                @endif
            </section>

            <section class="surface overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-5 dark:border-gray-800 sm:px-7">
                    <h2 class="text-xl font-black text-gray-950 dark:text-white">Journal des imports</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Les 20 derniers imports réalisés avec ton compte.</p>
                </div>

                @if ($logs->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun import journalisé pour le moment.</p>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($logs as $log)
                            <div class="px-5 py-4 sm:px-7">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-brand-100 px-2.5 py-1 text-[11px] font-extrabold uppercase text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">{{ $log->source }}</span>
                                            <p class="truncate font-bold text-gray-950 dark:text-white">{{ $log->filename ?: ($log->details[0]['title'] ?? 'Import depuis Chrome') }}</p>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->translatedFormat('d M Y à H:i') }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs font-bold">
                                        @if ($log->imported)
                                            <span class="rounded-lg bg-green-100 px-2.5 py-1.5 text-green-700 dark:bg-green-950/60 dark:text-green-300">{{ $log->imported }} importé(s)</span>
                                        @endif
                                        @if ($log->skipped)
                                            <span class="rounded-lg bg-amber-100 px-2.5 py-1.5 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">{{ $log->skipped }} ignoré(s)</span>
                                        @endif
                                        @if ($log->failed)
                                            <span class="rounded-lg bg-red-100 px-2.5 py-1.5 text-red-700 dark:bg-red-950/60 dark:text-red-300">{{ $log->failed }} erreur(s)</span>
                                        @endif
                                    </div>
                                </div>
                                @if ($log->details)
                                    <details class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/70">
                                        <summary class="cursor-pointer font-bold text-gray-700 dark:text-gray-200">Voir le détail</summary>
                                        <ul class="mt-2 space-y-1.5 text-gray-600 dark:text-gray-300">
                                            @foreach ($log->details as $detail)
                                                <li class="flex items-start justify-between gap-3">
                                                    <span>{{ $detail['title'] ?? 'Événement sans titre' }}</span>
                                                    <span class="shrink-0 text-xs font-bold uppercase text-gray-400">
                                                        {{ ($detail['result'] ?? '') === 'imported' ? 'Importé' : (($detail['result'] ?? '') === 'skipped' ? 'Ignoré' : 'Erreur') }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
