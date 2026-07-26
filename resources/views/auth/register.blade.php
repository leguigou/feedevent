<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow">Bienvenue</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight">Crée ton compte</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sauvegarde tes sorties et affine tes recommandations.</p>
    </div>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="name" value="Prénom ou pseudo" />
            <x-text-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <x-primary-button class="w-full">Créer mon compte</x-primary-button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-500">Déjà inscrit ? <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline dark:text-brand-300">Se connecter</a></p>
</x-guest-layout>
