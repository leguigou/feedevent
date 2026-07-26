<x-app-layout>
    <div class="py-7 sm:py-10">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="eyebrow">Ton espace</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-950 dark:text-white">Mon profil</h1>
            </div>
            <div class="surface p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="surface p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="surface p-5 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-950 dark:text-white">Connecteur Chrome</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Importe la page d’un événement directement dans FeedEvent.
                        </p>
                    </div>
                    <a href="{{ route('connector.index') }}" class="btn-primary shrink-0">Installer l’extension</a>
                </div>
            </div>

            <div class="surface border-red-200 p-5 dark:border-red-900/50 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
