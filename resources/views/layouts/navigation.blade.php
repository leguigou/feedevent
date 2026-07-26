<nav x-data="{ open: false }" class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50 backdrop-blur-lg bg-white/80 dark:bg-gray-900/80">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <span class="text-2xl">🎉</span>
                        <span class="text-lg font-extrabold bg-gradient-to-r from-brand-500 to-purple-600 bg-clip-text text-transparent hidden sm:inline">
                            Feedevent
                        </span>
                        <span class="text-lg font-extrabold text-brand-600 dark:text-brand-400 sm:hidden">F</span>
                    </a>
                </div>

                <!-- Navigation Links - Desktop -->
                <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-1">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" class="!rounded-xl">
                        {{ __('Feed') }}
                    </x-nav-link>
                    <x-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" class="!rounded-xl">
                        {{ __('Calendrier') }}
                    </x-nav-link>
                    <x-nav-link :href="route('map')" :active="request()->routeIs('map')" class="!rounded-xl">
                        {{ __('Carte') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Right side actions -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Dark mode toggle -->
                <button @click="toggleTheme()" 
                        class="p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                        :title="isDark ? 'Mode clair' : 'Mode sombre'">
                    <svg x-show="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <!-- Auth -->
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-900 text-brand-600 dark:text-brand-300 text-xs font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-400 border-b dark:border-gray-700">
                            {{ Auth::user()->email }}
                        </div>
                        @if(Auth::user()->role === 'admin')
                        <x-dropdown-link :href="route('admin')" class="!text-purple-600 dark:!text-purple-400">
                            ⚙️ Administration
                        </x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" class="!text-red-500"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Déconnexion') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('login') }}" class="btn-ghost text-sm">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary text-sm !px-3 !py-1.5">Inscription</a>
                </div>
                @endauth

                <!-- Hamburger -->
                <button @click="open = ! open" 
                        class="sm:hidden p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                    <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu - Mobile -->
    <div :class="{'block': open, 'hidden': ! open}" class="sm:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')" class="!rounded-xl">
                🎉 Feed
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" class="!rounded-xl">
                📅 Calendrier
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('map')" :active="request()->routeIs('map')" class="!rounded-xl">
                🗺️ Carte
            </x-responsive-nav-link>
        </div>

        @auth
        <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-3">
            <div class="flex items-center gap-3 mb-3">
                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900 text-brand-600 dark:text-brand-300 font-bold">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </span>
                <div>
                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <div class="space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="!rounded-xl">
                    ⚙️ Profile
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" class="!rounded-xl !text-red-500"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        🚪 Déconnexion
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-3 space-y-2">
            <a href="{{ route('login') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm font-medium transition-all">
                🔑 Se connecter
            </a>
            <a href="{{ route('register') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-medium hover:bg-brand-600 transition-all">
                ✨ Créer un compte
            </a>
        </div>
        @endauth
    </div>
</nav>
