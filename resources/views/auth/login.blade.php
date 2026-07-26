<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow">Bon retour</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight">Connecte-toi</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Retrouve tes favoris et tes recommandations.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" value="Mot de passe" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-brand-700 hover:underline dark:text-brand-300" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label for="remember_me" class="flex min-h-11 items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" name="remember">
            Rester connecté
        </label>
        <x-primary-button class="w-full">Se connecter</x-primary-button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-500">Pas encore de compte ? <a href="{{ route('register') }}" class="font-bold text-brand-700 hover:underline dark:text-brand-300">Créer mon compte</a></p>
</x-guest-layout>
