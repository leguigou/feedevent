<nav class="sticky top-0 z-50 border-b border-gray-200/70 bg-white/85 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-950/85" aria-label="Navigation principale">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" aria-label="Feedevent, accueil">
                <x-brand-mark />
            </a>

            <div class="hidden items-center gap-1 md:flex">
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')">Découvrir</x-nav-link>
                <x-nav-link :href="route('map')" :active="request()->routeIs('map')">Carte</x-nav-link>
                <x-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')">Agenda</x-nav-link>
                @auth
                    <x-nav-link :href="route('saved')" :active="request()->routeIs('saved')">Favoris</x-nav-link>
                @endauth
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="button" @click="toggleTheme()" :aria-pressed="isDark.toString()"
                    class="icon-button" :aria-label="isDark ? 'Activer le mode clair' : 'Activer le mode sombre'">
                <svg x-show="!isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.35 15.35A9 9 0 018.65 3.65 9 9 0 1012 21a9 9 0 008.35-5.65Z"/>
                </svg>
                <svg x-show="isDark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36 6.36-1.41-1.41M7.05 7.05 5.64 5.64m12.72 0-1.41 1.41M7.05 16.95l-1.41 1.41M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                </svg>
            </button>

            @auth
                <div class="hidden md:block">
                    <x-dropdown align="right" width="56" contentClasses="py-1 bg-white dark:bg-gray-900">
                        <x-slot name="trigger">
                            <button type="button" class="flex min-h-11 items-center gap-2 rounded-xl px-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" aria-label="Ouvrir le menu du compte">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                                <span>{{ Auth::user()->name }}</span>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-3 text-xs text-gray-500 dark:border-gray-800">{{ Auth::user()->email }}</div>
                            @if(Auth::user()->role === 'admin')
                                <x-dropdown-link :href="route('admin')">Administration</x-dropdown-link>
                            @endif
                            <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" class="!text-red-600" onclick="event.preventDefault(); this.closest('form').submit();">Déconnexion</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary !min-h-10 !px-3.5">S’inscrire</a>
            @endauth
        </div>
    </div>
</nav>

<nav class="mobile-tab-bar {{ Auth::check() ? 'grid-cols-5' : 'grid-cols-4' }} md:hidden" aria-label="Navigation mobile">
    <a href="{{ route('home') }}" class="mobile-tab {{ request()->routeIs('home', 'events.show') ? 'mobile-tab-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12.5 12 5l8 7.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-7.5Z"/></svg>
        <span>Découvrir</span>
    </a>
    <a href="{{ route('map') }}" class="mobile-tab {{ request()->routeIs('map') ? 'mobile-tab-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3-6-3Zm0 0V3m6 18V6"/></svg>
        <span>Carte</span>
    </a>
    <a href="{{ route('calendar') }}" class="mobile-tab {{ request()->routeIs('calendar') ? 'mobile-tab-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
        <span>Agenda</span>
    </a>
    @auth
        <a href="{{ route('saved') }}" class="mobile-tab {{ request()->routeIs('saved') ? 'mobile-tab-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20.5 4.8 13.6A4.8 4.8 0 0 1 11.6 6.8l.4.4.4-.4a4.8 4.8 0 1 1 6.8 6.8L12 20.5Z"/></svg>
            <span>Favoris</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="mobile-tab {{ request()->routeIs('profile.*') ? 'mobile-tab-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
            <span>Profil</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="mobile-tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
            <span>Connexion</span>
        </a>
    @endauth
</nav>
