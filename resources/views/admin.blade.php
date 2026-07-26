<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                ⚙️ Administration
            </h1>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8" x-data="adminPanel()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Sous-menu en pills -->
            <div class="mb-6 overflow-x-auto scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex gap-2 w-max sm:w-auto sm:flex-wrap">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button @click="switchTab(tab.id)"
                            class="category-pill shrink-0 cursor-pointer"
                            :class="activeTab === tab.id ? 'category-pill-active' : 'category-pill-inactive'">
                            <span x-text="tab.icon"></span>
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- ═══════════ DASHBOARD ═══════════ -->
            <div x-show="activeTab === 'dashboard'" x-cloak>
                <div x-show="statsLoading" class="text-center py-12 text-gray-400">
                    <div class="text-3xl mb-3 animate-pulse">📊</div>
                    <p>Chargement des statistiques...</p>
                </div>

                <template x-if="!statsLoading">
                    <div>
                        <!-- Cartes stats -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-8">
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">📅</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.total_events"></div>
                                <div class="text-xs text-gray-500">Total événements</div>
                            </div>
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">✅</div>
                                <div class="text-2xl font-bold text-green-600" x-text="stats.published_events"></div>
                                <div class="text-xs text-gray-500">Publiés</div>
                            </div>
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">📦</div>
                                <div class="text-2xl font-bold text-yellow-600" x-text="stats.archived_events"></div>
                                <div class="text-xs text-gray-500">Archivés</div>
                            </div>
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">✏️</div>
                                <div class="text-2xl font-bold text-orange-600" x-text="stats.draft_events"></div>
                                <div class="text-xs text-gray-500">Brouillons</div>
                            </div>
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">👥</div>
                                <div class="text-2xl font-bold text-indigo-600" x-text="stats.total_users"></div>
                                <div class="text-xs text-gray-500">Utilisateurs</div>
                            </div>
                            <div class="event-card p-4 text-center">
                                <div class="text-2xl mb-1">🏷️</div>
                                <div class="text-2xl font-bold text-brand-600 dark:text-brand-300" x-text="stats.total_categories"></div>
                                <div class="text-xs text-gray-500">Catégories</div>
                            </div>
                        </div>

                        <!-- Graphique : Événements par mois (barres simples) -->
                        <div class="event-card p-4 sm:p-6 mb-6">
                            <h2 class="font-bold text-gray-900 dark:text-white mb-4">Événements par mois</h2>
                            <div class="flex items-end gap-1.5 sm:gap-2 h-32 sm:h-40 overflow-x-auto pb-2">
                                <template x-for="(count, month) in stats.events_by_month" :key="month">
                                    <div class="flex flex-col items-center gap-1 min-w-[32px] sm:min-w-[40px]">
                                        <div class="w-full rounded-t-md bg-brand-400 dark:bg-brand-600 transition-all duration-300"
                                            :style="`height: ${Math.max(4, (count / maxMonthCount) * 120)}px`"
                                            :title="`${month}: ${count} événements`">
                                        </div>
                                        <span class="text-[10px] text-gray-600 dark:text-gray-400 truncate w-full text-center" x-text="month.slice(5)"></span>
                                    </div>
                                </template>
                                <div x-show="Object.keys(stats.events_by_month || {}).length === 0" class="text-sm text-gray-600 dark:text-gray-400 py-8 w-full text-center">
                                    Aucune donnée
                                </div>
                            </div>
                        </div>

                        <!-- Deux colonnes : Catégories + Likes -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="event-card p-4 sm:p-6">
                                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Événements par catégorie</h2>
                                <div class="space-y-3">
                                    <template x-for="cat in stats.events_by_category" :key="cat.name">
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg" x-text="cat.icon"></span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between text-sm mb-1">
                                                    <span class="font-medium text-gray-700 dark:text-gray-300 truncate" x-text="cat.name"></span>
                                                    <span class="text-gray-500" x-text="cat.total"></span>
                                                </div>
                                                <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500"
                                                        :style="`width: ${catPct(cat.total)}%; background-color: ${cat.color}`">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="event-card p-4 sm:p-6">
                                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Interactions</h2>
                                <div class="flex gap-6 items-center justify-center py-8">
                                    <div class="text-center">
                                        <div class="text-5xl mb-2">👍</div>
                                        <div class="text-3xl font-bold text-green-600" x-text="stats.total_likes"></div>
                                        <div class="text-sm text-gray-500">Likes</div>
                                    </div>
                                    <div class="text-3xl text-gray-500 dark:text-gray-400">vs</div>
                                    <div class="text-center">
                                        <div class="text-5xl mb-2">👎</div>
                                        <div class="text-3xl font-bold text-red-600" x-text="stats.total_dislikes"></div>
                                        <div class="text-sm text-gray-500">Dislikes</div>
                                    </div>
                                </div>
                                <template x-if="stats.total_likes + stats.total_dislikes > 0">
                                    <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 rounded-full transition-all"
                                            :style="`width: ${(stats.total_likes / (stats.total_likes + stats.total_dislikes)) * 100}%`">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ═══════════ ÉVÉNEMENTS ═══════════ -->
            <div x-show="activeTab === 'events'" x-cloak>
                <!-- Filtres + Ajout -->
                <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between">
                    <div class="flex flex-wrap gap-2">
                        <select x-model="eventFilter.status" @change="loadEvents(1)"
                            class="search-input !w-auto !py-2 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="published">Publié</option>
                            <option value="draft">Brouillon</option>
                            <option value="archived">Archivé</option>
                        </select>
                        <select x-model="eventFilter.category_id" @change="loadEvents(1)"
                            class="search-input !w-auto !py-2 text-sm">
                            <option value="">Toutes catégories</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.icon + ' ' + cat.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <input type="text" x-model="eventFilter.search" @input.debounce="loadEvents(1)"
                            placeholder="Rechercher..."
                            class="search-input !py-2 text-sm flex-1 sm:flex-none">
                        <button @click="openEventModal()" class="btn-primary text-sm whitespace-nowrap">
                            + Nouvel événement
                        </button>
                    </div>
                </div>

                <!-- Tableau événements -->
                <div class="event-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Titre</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 hidden sm:table-cell">Catégorie</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 hidden md:table-cell">Date</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Statut</th>
                                    <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr x-show="eventsLoading">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        Chargement...
                                    </td>
                                </tr>
                                <template x-for="event in events" :key="event.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white truncate max-w-[200px] sm:max-w-xs" x-text="event.title"></div>
                                            <div class="text-xs text-gray-400" x-text="event.user?.name || '—'"></div>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            <span class="category-pill text-xs"
                                                  :style="{ backgroundColor: event.category?.color + '20', color: event.category?.color }">
                                                <span x-text="event.category?.icon || '📌'"></span>
                                                <span x-text="event.category?.name || '—'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell">
                                            <div x-text="formatDate(event.date_start)"></div>
                                            <div x-show="event.date_end" class="text-gray-400" x-text="'→ ' + formatDate(event.date_end)"></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': event.status === 'published',
                                                      'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': event.status === 'draft',
                                                      'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400': event.status === 'archived',
                                                  }">
                                                <span x-text="statusIcon(event.status)"></span>
                                                <span x-text="event.status"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button @click="toggleEventStatus(event)" class="btn-ghost !p-1.5 text-xs"
                                                    :title="event.status === 'published' ? 'Archiver' : event.status === 'archived' ? 'Publier' : 'Publier'">
                                                    <span x-text="event.status === 'published' ? '📦' : event.status === 'archived' ? '✅' : '✅'"></span>
                                                </button>
                                                <button @click="openEventModal(event)" class="btn-ghost !p-1.5 text-xs" title="Modifier">
                                                    ✏️
                                                </button>
                                                <button @click="confirmDeleteEvent(event)" class="btn-ghost !p-1.5 text-xs !text-red-500" title="Supprimer">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!eventsLoading && events.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                        Aucun événement trouvé
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div x-show="events.length > 0" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-500">
                        <span x-text="`Page ${eventsPage} / ${eventsLastPage}`"></span>
                        <div class="flex gap-1">
                            <button @click="loadEvents(eventsPage - 1)" :disabled="eventsPage <= 1"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="eventsPage <= 1 ? 'opacity-40' : ''">
                                ← Préc.
                            </button>
                            <button @click="loadEvents(eventsPage + 1)" :disabled="eventsPage >= eventsLastPage"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="eventsPage >= eventsLastPage ? 'opacity-40' : ''">
                                Suiv. →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ CATÉGORIES ═══════════ -->
            <div x-show="activeTab === 'categories'" x-cloak>
                <div class="flex justify-end mb-4">
                    <button @click="openCategoryModal()" class="btn-primary text-sm">
                        + Nouvelle catégorie
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="cat in categories" :key="cat.id">
                        <div class="event-card p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl" x-text="cat.icon || '🏷️'"></span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white" x-text="cat.name"></div>
                                        <div class="text-xs text-gray-400" x-text="`${cat.events_count || 0} événements`"></div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="openCategoryModal(cat)" class="btn-ghost !p-1.5 text-xs">✏️</button>
                                    <button @click="confirmDeleteCategory(cat)" class="btn-ghost !p-1.5 text-xs !text-red-500">🗑️</button>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: cat.color || '#6366f1' }"></div>
                                <span class="text-xs text-gray-400" x-text="cat.slug"></span>
                                <span class="text-xs text-gray-400" x-text="cat.color || '—'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ═══════════ UTILISATEURS ═══════════ -->
            <div x-show="activeTab === 'users'" x-cloak>
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <input type="text" x-model="userSearch" @input.debounce="loadUsers(1)"
                        placeholder="Rechercher un utilisateur..."
                        class="search-input !py-2 text-sm max-w-sm">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Les comptes suspendus ne peuvent plus se connecter.
                    </p>
                </div>

                <div class="event-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Nom</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Email</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Rôle</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 hidden lg:table-cell">Activité</th>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Statut</th>
                                    <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr x-show="usersLoading">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Chargement...</td>
                                </tr>
                                <template x-for="user in users" :key="user.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white" x-text="user.name"></div>
                                            <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400" x-text="`Inscrit ${formatDate(user.created_at)}`"></div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300" x-text="user.email"></td>
                                        <td class="px-4 py-3">
                                            <select :value="user.role" :aria-label="`Rôle de ${user.name}`" @change="updateUser(user, { role: $event.target.value })"
                                                class="rounded-lg border-gray-200 bg-white py-1.5 text-xs font-semibold text-gray-700 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                <option value="user">Utilisateur</option>
                                                <option value="admin">Administrateur</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 hidden lg:table-cell">
                                            <div class="flex flex-wrap gap-1 text-[11px]">
                                                <span class="rounded-full bg-brand-50 px-2 py-1 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300" x-text="`${user.events_count || 0} événements`"></span>
                                                <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="`${user.preferences_count || 0} avis`"></span>
                                                <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="`${user.saved_events_count || 0} favoris`"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="user.is_active
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-300'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'"
                                                x-text="user.is_active ? 'Actif' : 'Suspendu'"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" @click="updateUser(user, { is_active: !user.is_active })"
                                                    class="btn-ghost !px-2 !py-1 text-xs"
                                                    x-text="user.is_active ? 'Suspendre' : 'Réactiver'"></button>
                                                <button type="button" @click="deleteUser(user)"
                                                    class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                                    Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!usersLoading && users.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="users.length > 0" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-500">
                        <span x-text="`Page ${usersPage} / ${usersLastPage}`"></span>
                        <div class="flex gap-1">
                            <button @click="loadUsers(usersPage - 1)" :disabled="usersPage <= 1"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="usersPage <= 1 ? 'opacity-40' : ''">← Préc.</button>
                            <button @click="loadUsers(usersPage + 1)" :disabled="usersPage >= usersLastPage"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="usersPage >= usersLastPage ? 'opacity-40' : ''">Suiv. →</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ PARAMÈTRES ═══════════ -->
            <div x-show="activeTab === 'settings'" x-cloak>
                <div class="mb-5">
                    <p class="eyebrow">Configuration sécurisée</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">Paramètres du site</h2>
                    <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        Les secrets sont chiffrés avec <code>APP_KEY</code>. Une valeur enregistrée ici remplace celle de l’environnement.
                    </p>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-5">
                    <template x-for="(items, groupName) in settingsGroups" :key="groupName">
                        <section class="event-card p-4 sm:p-6">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold text-gray-950 dark:text-white" x-text="groupName"></h3>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                    x-text="`${items.length} paramètres`"></span>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <template x-for="item in items" :key="item.key">
                                    <label class="block" :class="item.type === 'boolean' ? 'md:col-span-2' : ''">
                                        <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            <span x-text="item.label"></span>
                                            <span x-show="item.secret && item.configured"
                                                class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] text-green-700 dark:bg-green-950/60 dark:text-green-300">Configuré</span>
                                            <button x-show="item.secret && item.configured && item.source === 'backoffice'" type="button" @click.prevent="clearSetting(item)"
                                                class="text-[11px] font-semibold text-red-600 hover:underline dark:text-red-400">
                                                Revenir à l’environnement
                                            </button>
                                        </span>

                                        <template x-if="item.type === 'select'">
                                            <select x-model="settingsForm[item.key]" class="search-input !py-2.5">
                                                <template x-for="(label, value) in item.options" :key="value">
                                                    <option :value="value" x-text="label"></option>
                                                </template>
                                            </select>
                                        </template>

                                        <template x-if="item.type === 'boolean'">
                                            <span class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                                                <input type="checkbox" x-model="settingsForm[item.key]"
                                                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800">
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="settingsForm[item.key] ? 'Activé' : 'Désactivé'"></span>
                                            </span>
                                        </template>

                                        <template x-if="!['select', 'boolean'].includes(item.type)">
                                            <input :type="item.type" x-model="settingsForm[item.key]"
                                                :placeholder="item.secret && item.configured ? '••••••••••••' : ''"
                                                :step="item.key === 'llm.temperature' ? '0.1' : null"
                                                class="search-input !py-2.5">
                                        </template>

                                        <span x-show="item.help" class="mt-1.5 block text-xs text-gray-500 dark:text-gray-400" x-text="item.help"></span>
                                    </label>
                                </template>
                            </div>
                        </section>
                    </template>

                    <div class="sticky bottom-20 z-20 flex justify-end rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-xl backdrop-blur dark:border-gray-700 dark:bg-gray-900/95 lg:bottom-4">
                        <button type="submit" class="btn-primary" :disabled="settingsSaving">
                            <span x-text="settingsSaving ? 'Enregistrement…' : 'Enregistrer les paramètres'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ═══════════ LOGS ═══════════ -->
            <div x-show="activeTab === 'logs'" x-cloak>
                <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between">
                    <div class="flex gap-2">
                        <select x-model="logFilter.level" @change="loadLogs(1)"
                            class="search-input !w-auto !py-2 text-sm">
                            <option value="">Tous les niveaux</option>
                            <option value="ERROR">ERROR</option>
                            <option value="WARNING">WARNING</option>
                            <option value="INFO">INFO</option>
                            <option value="DEBUG">DEBUG</option>
                        </select>
                        <select x-model="logFilter.days" @change="loadLogs(1)"
                            class="search-input !w-auto !py-2 text-sm">
                            <option value="1">24h</option>
                            <option value="7">7 jours</option>
                            <option value="30">30 jours</option>
                            <option value="365">1 an</option>
                        </select>
                    </div>
                    <button @click="clearLogs()" class="btn-ghost text-sm !text-red-500">
                        🗑️ Vider les logs
                    </button>
                </div>

                <div class="event-card overflow-hidden">
                    <div x-show="logsLoading" class="px-4 py-8 text-center text-gray-400">
                        Chargement des logs...
                    </div>
                    <template x-if="!logsLoading && logs.length === 0">
                        <div class="px-4 py-12 text-center text-gray-400">
                            <div class="text-4xl mb-3">🎉</div>
                            <p>Aucune erreur récente</p>
                        </div>
                    </template>
                    <div x-show="!logsLoading && logs.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="(log, idx) in logs" :key="idx">
                            <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 mt-0.5 w-2 h-2 rounded-full"
                                          :class="{
                                              'bg-red-500': log.level === 'ERROR',
                                              'bg-yellow-500': log.level === 'WARNING',
                                              'bg-blue-500': log.level === 'INFO',
                                              'bg-gray-400': log.level === 'DEBUG',
                                          }">
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-mono text-gray-400" x-text="log.timestamp"></span>
                                            <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                                  :class="{
                                                      'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': log.level === 'ERROR',
                                                      'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': log.level === 'WARNING',
                                                  }"
                                                  x-text="log.level"></span>
                                        </div>
                                        <div class="text-sm font-mono text-gray-700 dark:text-gray-300 break-all line-clamp-2"
                                             x-text="log.message"></div>
                                        <button @click="log.expanded = !log.expanded" x-show="log.full?.length > 300"
                                            class="text-xs text-brand-500 mt-1 hover:underline">
                                            <span x-text="log.expanded ? 'Réduire' : 'Voir plus...'"></span>
                                        </button>
                                        <div x-show="log.expanded" class="mt-2 text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-pre-wrap break-all bg-gray-50 dark:bg-gray-900 rounded-lg p-3 max-h-64 overflow-y-auto"
                                             x-text="log.full">
                                        </div>
                                    </div>
                                    <button @click="copyLog(log)" class="btn-ghost !p-1 text-xs shrink-0" title="Copier">
                                        📋
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="!logsLoading && logs.length > 0"
                         class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-500">
                        <span x-text="`${logsTotal} entrées`"></span>
                        <div class="flex gap-1">
                            <button @click="loadLogs(logsPage - 1)" :disabled="logsPage <= 1"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="logsPage <= 1 ? 'opacity-40' : ''">← Préc.</button>
                            <button @click="loadLogs(logsPage + 1)" :disabled="logsPage >= logsLastPage"
                                class="btn-ghost !px-2 !py-1 text-xs" :class="logsPage >= logsLastPage ? 'opacity-40' : ''">Suiv. →</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══════════ EVENT MODAL ═══════════ -->
        <div x-show="showEventModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" x-cloak
             @click.away="showEventModal = false">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showEventModal = false"></div>
            <div class="relative z-10 w-full sm:max-w-2xl bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 sm:p-8 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        <span x-text="editingEvent ? '✏️ Modifier' : '➕ Nouvel événement'"></span>
                    </h2>
                    <button @click="showEventModal = false" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre *</label>
                        <input type="text" x-model="eventForm.title" class="search-input !py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea x-model="eventForm.description" rows="3" class="search-input !py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début *</label>
                        <input type="datetime-local" x-model="eventForm.date_start" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                        <input type="datetime-local" x-model="eventForm.date_end" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lieu</label>
                        <input type="text" x-model="eventForm.location" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie</label>
                        <select x-model="eventForm.category_id" class="search-input !py-2">
                            <option value="">Sélectionner</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.icon + ' ' + cat.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                        <select x-model="eventForm.status" class="search-input !py-2">
                            <option value="published">Publié</option>
                            <option value="draft">Brouillon</option>
                            <option value="archived">Archivé</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix (€)</label>
                        <input type="number" step="0.01" x-model="eventForm.price" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organisateur</label>
                        <input type="text" x-model="eventForm.organizer" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image URL</label>
                        <input type="url" x-model="eventForm.image_url" class="search-input !py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source URL</label>
                        <input type="url" x-model="eventForm.source_url" class="search-input !py-2">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <button @click="showEventModal = false" class="btn-secondary text-sm">Annuler</button>
                    <button @click="saveEvent()" :disabled="eventSubmitting"
                        class="btn-primary text-sm">
                        <span x-show="!eventSubmitting" x-text="editingEvent ? '💾 Enregistrer' : '✨ Créer'"></span>
                        <span x-show="eventSubmitting">⏳...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ CATEGORY MODAL ═══════════ -->
        <div x-show="showCategoryModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" x-cloak
             @click.away="showCategoryModal = false">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCategoryModal = false"></div>
            <div class="relative z-10 w-full sm:max-w-md bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 sm:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        <span x-text="editingCategory ? '✏️ Modifier' : '➕ Nouvelle catégorie'"></span>
                    </h2>
                    <button @click="showCategoryModal = false" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                        <input type="text" x-model="categoryForm.name" class="search-input !py-2" placeholder="Concert">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug *</label>
                        <input type="text" x-model="categoryForm.slug" class="search-input !py-2" placeholder="concert">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icône</label>
                        <input type="text" x-model="categoryForm.icon" class="search-input !py-2" placeholder="🎵" maxlength="10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Couleur</label>
                        <div class="flex gap-3 items-center">
                            <input type="color" x-model="categoryForm.color" class="h-10 w-14 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                            <input type="text" x-model="categoryForm.color" class="search-input !py-2 flex-1" placeholder="#f43f5e">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <button @click="showCategoryModal = false" class="btn-secondary text-sm">Annuler</button>
                    <button @click="saveCategory()" :disabled="categorySubmitting"
                        class="btn-primary text-sm">
                        <span x-show="!categorySubmitting" x-text="editingCategory ? '💾 Enregistrer' : '✨ Créer'"></span>
                        <span x-show="categorySubmitting">⏳...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ DELETE CONFIRM MODAL ═══════════ -->
        <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
            <div class="relative z-10 w-full max-w-sm bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 mx-4">
                <div class="text-center mb-6">
                    <div class="text-5xl mb-4">⚠️</div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Confirmer la suppression</h2>
                    <p class="text-sm text-gray-500" x-text="deleteMessage"></p>
                </div>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false" class="btn-secondary flex-1 justify-center text-sm">Annuler</button>
                    <button @click="executeDelete()" :disabled="deleteLoading"
                        class="btn-primary flex-1 justify-center text-sm !bg-red-500 hover:!bg-red-600">
                        <span x-show="!deleteLoading">🗑️ Supprimer</span>
                        <span x-show="deleteLoading">⏳...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ TOAST ═══════════ -->
        <div x-show="toast.show" x-cloak
             x-transition:enter="transition duration-300 ease-out"
             x-transition:enter-start="translate-y-2 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition duration-200 ease-in"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-2 opacity-0"
             class="fixed bottom-6 right-6 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-medium"
             :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminPanel', () => ({
                // ─── TABS ───────────────────────────────────
                tabs: [
                    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
                    { id: 'events', label: 'Événements', icon: '📅' },
                    { id: 'categories', label: 'Catégories', icon: '🏷️' },
                    { id: 'users', label: 'Utilisateurs', icon: '👥' },
                    { id: 'settings', label: 'Paramètres', icon: '⚙️' },
                    { id: 'logs', label: 'Logs', icon: '📋' },
                ],
                activeTab: 'dashboard',

                switchTab(tab) {
                    this.activeTab = tab;
                    if (tab === 'dashboard' && !this.statsLoaded) this.loadStats();
                    if (tab === 'events') this.loadEvents(1);
                    if (tab === 'categories') this.loadCategories();
                    if (tab === 'users') this.loadUsers(1);
                    if (tab === 'settings' && !this.settingsLoaded) this.loadSettings();
                    if (tab === 'logs') this.loadLogs(1);
                },

                // ─── TOAST ──────────────────────────────────
                toast: { show: false, message: '', type: 'success' },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 3000);
                },

                getCsrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.content || '';
                },

                // ─── DASHBOARD ───────────────────────────────
                stats: {},
                statsLoading: true,
                statsLoaded: false,

                async loadStats() {
                    this.statsLoading = true;
                    try {
                        const res = await fetch('/api/admin/stats');
                        this.stats = await res.json();
                        this.statsLoaded = true;
                    } catch (e) {
                        console.error('Stats error', e);
                        this.showToast('Erreur chargement stats', 'error');
                    } finally {
                        this.statsLoading = false;
                    }
                },

                get maxMonthCount() {
                    const counts = Object.values(this.stats.events_by_month || {});
                    return counts.length ? Math.max(...counts) : 1;
                },

                catPct(count) {
                    const total = this.stats.total_events || 1;
                    return Math.round((count / total) * 100);
                },

                // ─── EVENTS ──────────────────────────────────
                events: [],
                eventsLoading: false,
                eventsPage: 1,
                eventsLastPage: 1,

                eventFilter: { status: '', category_id: '', search: '' },

                async loadEvents(page = 1) {
                    this.eventsLoading = true;
                    this.eventsPage = page;
                    try {
                        const params = new URLSearchParams();
                        params.set('page', page);
                        if (this.eventFilter.status) params.set('status', this.eventFilter.status);
                        if (this.eventFilter.category_id) params.set('category_id', this.eventFilter.category_id);
                        if (this.eventFilter.search) params.set('search', this.eventFilter.search);

                        const res = await fetch(`/api/admin/events?${params}`);
                        const data = await res.json();
                        this.events = data.data || [];
                        this.eventsLastPage = data.last_page || 1;
                    } catch (e) {
                        console.error('Events load error', e);
                        this.showToast('Erreur chargement événements', 'error');
                    } finally {
                        this.eventsLoading = false;
                    }
                },

                // ─── EVENT MODAL ─────────────────────────────
                showEventModal: false,
                editingEvent: null,
                eventSubmitting: false,
                eventForm: {},

                openEventModal(event = null) {
                    this.editingEvent = event;
                    this.eventForm = event ? { ...event } : {
                        title: '', description: '', date_start: '', date_end: null,
                        location: '', category_id: '', status: 'published',
                        price: null, organizer: '', image_url: '', source_url: '',
                    };
                    this.showEventModal = true;
                },

                async saveEvent() {
                    this.eventSubmitting = true;
                    try {
                        const url = this.editingEvent
                            ? `/api/admin/events/${this.editingEvent.id}`
                            : '/api/admin/events';
                        const method = this.editingEvent ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify(this.eventForm),
                        });

                        if (!res.ok) {
                            const err = await res.json();
                            throw new Error(err.message || Object.values(err.errors || {}).flat().join(', '));
                        }

                        this.showEventModal = false;
                        this.showToast(this.editingEvent ? 'Événement modifié ✅' : 'Événement créé ✅');
                        await this.loadEvents(this.eventsPage);
                    } catch (e) {
                        this.showToast(`Erreur: ${e.message}`, 'error');
                    } finally {
                        this.eventSubmitting = false;
                    }
                },

                async toggleEventStatus(event) {
                    const newStatus = event.status === 'published' ? 'archived'
                        : event.status === 'archived' ? 'published'
                        : 'published';
                    try {
                        const res = await fetch(`/api/admin/events/${event.id}/status`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify({ status: newStatus }),
                        });
                        if (res.ok) {
                            event.status = newStatus;
                            this.showToast(`Statut changé → ${newStatus}`);
                        }
                    } catch (e) {
                        this.showToast('Erreur changement statut', 'error');
                    }
                },

                // ─── DELETE ──────────────────────────────────
                showDeleteModal: false,
                deleteTarget: null,
                deleteAction: '',
                deleteMessage: '',
                deleteLoading: false,

                confirmDeleteEvent(event) {
                    this.deleteTarget = event;
                    this.deleteAction = 'event';
                    this.deleteMessage = `Supprimer l'événement "${event.title}" ?`;
                    this.showDeleteModal = true;
                },

                confirmDeleteCategory(cat) {
                    this.deleteTarget = cat;
                    this.deleteAction = 'category';
                    this.deleteMessage = `Supprimer la catégorie "${cat.name}" ?`;
                    this.showDeleteModal = true;
                },

                async executeDelete() {
                    this.deleteLoading = true;
                    try {
                        let url = '';
                        if (this.deleteAction === 'event') {
                            url = `/api/admin/events/${this.deleteTarget.id}`;
                        } else if (this.deleteAction === 'category') {
                            url = `/api/admin/categories/${this.deleteTarget.id}`;
                        }

                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.getCsrf() },
                        });

                        if (res.ok) {
                            this.showDeleteModal = false;
                            this.showToast('Supprimé ✅');
                            if (this.deleteAction === 'event') await this.loadEvents(this.eventsPage);
                            if (this.deleteAction === 'category') await this.loadCategories();
                        } else {
                            const err = await res.json();
                            throw new Error(err.message || 'Erreur suppression');
                        }
                    } catch (e) {
                        this.showToast(`Erreur: ${e.message}`, 'error');
                    } finally {
                        this.deleteLoading = false;
                        this.deleteTarget = null;
                    }
                },

                // ─── CATEGORIES ──────────────────────────────
                categories: [],
                categoriesLoading: false,

                async loadCategories() {
                    this.categoriesLoading = true;
                    try {
                        const res = await fetch('/api/admin/categories');
                        this.categories = await res.json();
                    } catch (e) {
                        console.error('Categories load error', e);
                    } finally {
                        this.categoriesLoading = false;
                    }
                },

                showCategoryModal: false,
                editingCategory: null,
                categorySubmitting: false,
                categoryForm: {},

                openCategoryModal(cat = null) {
                    this.editingCategory = cat;
                    this.categoryForm = cat ? { ...cat } : {
                        name: '', slug: '', icon: '📌', color: '#6366f1',
                    };
                    this.showCategoryModal = true;
                },

                async saveCategory() {
                    this.categorySubmitting = true;
                    try {
                        const url = this.editingCategory
                            ? `/api/admin/categories/${this.editingCategory.id}`
                            : '/api/admin/categories';
                        const method = this.editingCategory ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify(this.categoryForm),
                        });

                        if (!res.ok) {
                            const err = await res.json();
                            throw new Error(err.message || Object.values(err.errors || {}).flat().join(', '));
                        }

                        this.showCategoryModal = false;
                        this.showToast(this.editingCategory ? 'Catégorie modifiée ✅' : 'Catégorie créée ✅');
                        await this.loadCategories();
                    } catch (e) {
                        this.showToast(`Erreur: ${e.message}`, 'error');
                    } finally {
                        this.categorySubmitting = false;
                    }
                },

                // ─── USERS ───────────────────────────────────
                users: [],
                usersLoading: false,
                userSearch: '',
                usersPage: 1,
                usersLastPage: 1,

                async loadUsers(page = 1) {
                    this.usersLoading = true;
                    this.usersPage = page;
                    try {
                        const params = new URLSearchParams();
                        params.set('page', page);
                        if (this.userSearch) params.set('search', this.userSearch);

                        const res = await fetch(`/api/admin/users?${params}`);
                        const data = await res.json();
                        this.users = data.data || [];
                        this.usersLastPage = data.last_page || 1;
                    } catch (e) {
                        console.error('Users load error', e);
                    } finally {
                        this.usersLoading = false;
                    }
                },

                async updateUser(user, changes) {
                    try {
                        const res = await fetch(`/api/admin/users/${user.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify(changes),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || Object.values(data.errors || {}).flat().join(', '));
                        }
                        Object.assign(user, data);
                        this.showToast('Utilisateur mis à jour');
                    } catch (e) {
                        await this.loadUsers(this.usersPage);
                        this.showToast(e.message || 'Mise à jour impossible', 'error');
                    }
                },

                async deleteUser(user) {
                    if (!confirm(`Supprimer définitivement le compte de ${user.name} ?`)) return;

                    try {
                        const res = await fetch(`/api/admin/users/${user.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || Object.values(data.errors || {}).flat().join(', '));
                        }
                        this.showToast('Utilisateur supprimé');
                        await this.loadUsers(this.usersPage);
                        await this.loadStats();
                    } catch (e) {
                        this.showToast(e.message || 'Suppression impossible', 'error');
                    }
                },

                // ─── SETTINGS ────────────────────────────────
                settingsGroups: {},
                settingsForm: {},
                settingsLoaded: false,
                settingsSaving: false,

                async loadSettings() {
                    try {
                        const res = await fetch('/api/admin/settings', {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Chargement impossible');

                        this.settingsGroups = data.groups || {};
                        this.settingsForm = {};
                        Object.values(this.settingsGroups).flat().forEach(item => {
                            this.settingsForm[item.key] = item.value ?? '';
                        });
                        this.settingsLoaded = true;
                    } catch (e) {
                        this.showToast(e.message || 'Erreur de chargement des paramètres', 'error');
                    }
                },

                async saveSettings() {
                    this.settingsSaving = true;
                    try {
                        const res = await fetch('/api/admin/settings', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify({ settings: this.settingsForm }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || Object.values(data.errors || {}).flat().join(', '));
                        }

                        this.settingsGroups = data.groups || {};
                        Object.values(this.settingsGroups).flat().forEach(item => {
                            if (item.secret) this.settingsForm[item.key] = '';
                        });
                        this.showToast('Paramètres enregistrés');
                    } catch (e) {
                        this.showToast(e.message || 'Enregistrement impossible', 'error');
                    } finally {
                        this.settingsSaving = false;
                    }
                },

                async clearSetting(item) {
                    if (!confirm(`Supprimer la valeur enregistrée pour « ${item.label} » ?`)) return;

                    try {
                        const res = await fetch('/api/admin/settings', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify({
                                settings: this.settingsForm,
                                clear: [item.key],
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Suppression impossible');
                        await this.loadSettings();
                        this.showToast('Valeur du back-office supprimée');
                    } catch (e) {
                        this.showToast(e.message || 'Suppression impossible', 'error');
                    }
                },

                // ─── LOGS ────────────────────────────────────
                logs: [],
                logsLoading: false,
                logsPage: 1,
                logsLastPage: 1,
                logsTotal: 0,
                logFilter: { level: '', days: '30' },

                async loadLogs(page = 1) {
                    this.logsLoading = true;
                    this.logsPage = page;
                    try {
                        const params = new URLSearchParams();
                        params.set('page', page);
                        if (this.logFilter.level) params.set('level', this.logFilter.level);
                        params.set('days', this.logFilter.days);

                        const res = await fetch(`/api/admin/logs?${params}`);
                        const data = await res.json();
                        this.logs = data.data || [];
                        this.logsTotal = data.total || 0;
                        this.logsLastPage = data.last_page || 1;
                    } catch (e) {
                        console.error('Logs load error', e);
                    } finally {
                        this.logsLoading = false;
                    }
                },

                async clearLogs() {
                    if (!confirm('Vider tous les logs ?')) return;
                    try {
                        const res = await fetch('/api/admin/logs', {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.getCsrf() },
                        });
                        if (res.ok) {
                            this.logs = [];
                            this.logsTotal = 0;
                            this.showToast('Logs vidés ✅');
                        }
                    } catch (e) {
                        this.showToast('Erreur vidage logs', 'error');
                    }
                },

                copyLog(log) {
                    navigator.clipboard.writeText(log.full || log.message).then(() => {
                        this.showToast('Copié 📋');
                    });
                },

                // ─── UTILITIES ───────────────────────────────
                statusIcon(status) {
                    return status === 'published' ? '✅' : status === 'draft' ? '✏️' : '📦';
                },

                formatDate(date) {
                    if (!date) return '—';
                    return new Date(date).toLocaleDateString('fr-FR', {
                        day: 'numeric', month: 'short', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                },

                // ─── INIT ────────────────────────────────────
                async init() {
                    await this.loadStats();
                    await this.loadCategories();
                },
            }));
        });
    </script>
    @endpush
</x-app-layout>
