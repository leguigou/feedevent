<x-app-layout>
    <div class="py-7 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <header>
                <a href="{{ route('my-events.index') }}" class="text-sm font-bold text-brand-700 hover:text-brand-800 dark:text-brand-300">← Mes événements</a>
                <p class="eyebrow mt-5">Édition</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-950 dark:text-white">Modifier l’événement</h1>
            </header>

            <form method="POST" action="{{ route('my-events.update', $event) }}" class="surface mt-6 space-y-5 p-5 sm:p-8">
                @csrf
                @method('PUT')

                <label class="block">
                    <span class="form-label">Titre</span>
                    <input class="form-input" type="text" name="title" value="{{ old('title', $event->title) }}" maxlength="255" required>
                    @error('title')<span class="form-error">{{ $message }}</span>@enderror
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="form-label">Début</span>
                        <input class="form-input" type="datetime-local" name="date_start" value="{{ old('date_start', $event->date_start?->format('Y-m-d\TH:i')) }}" required>
                        @error('date_start')<span class="form-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="form-label">Fin</span>
                        <input class="form-input" type="datetime-local" name="date_end" value="{{ old('date_end', $event->date_end?->format('Y-m-d\TH:i')) }}">
                        @error('date_end')<span class="form-error">{{ $message }}</span>@enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="form-label">Lieu</span>
                        <input class="form-input" type="text" name="location" value="{{ old('location', $event->location) }}" maxlength="255">
                    </label>
                    <label class="block">
                        <span class="form-label">Organisateur</span>
                        <input class="form-input" type="text" name="organizer" value="{{ old('organizer', $event->organizer) }}" maxlength="255">
                    </label>
                </div>

                <label class="block">
                    <span class="form-label">Adresse</span>
                    <input class="form-input" type="text" name="address" value="{{ old('address', $event->address) }}" maxlength="1000">
                </label>

                <label class="block">
                    <span class="form-label">Description</span>
                    <textarea class="form-input min-h-40" name="description" maxlength="20000">{{ old('description', $event->description) }}</textarea>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="form-label">Catégorie</span>
                        <select class="form-input" name="category_id">
                            <option value="">Sans catégorie</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $event->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="form-label">Statut</span>
                        <select class="form-input" name="status" required>
                            <option value="published" @selected(old('status', $event->status) === 'published')>Publié</option>
                            <option value="draft" @selected(old('status', $event->status) === 'draft')>Brouillon</option>
                            <option value="archived" @selected(old('status', $event->status) === 'archived')>Archivé</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block">
                        <span class="form-label">Latitude</span>
                        <input class="form-input" type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude', $event->latitude) }}">
                    </label>
                    <label class="block">
                        <span class="form-label">Longitude</span>
                        <input class="form-input" type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude', $event->longitude) }}">
                    </label>
                    <label class="block">
                        <span class="form-label">Prix</span>
                        <input class="form-input" type="number" step="0.01" min="0" name="price" value="{{ old('price', $event->price) }}">
                    </label>
                </div>

                <label class="block">
                    <span class="form-label">URL de l’image</span>
                    <input class="form-input" type="url" name="image_url" value="{{ old('image_url', $event->image_url) }}" maxlength="2048">
                    @error('image_url')<span class="form-error">{{ $message }}</span>@enderror
                </label>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 dark:border-gray-800 sm:flex-row sm:justify-end">
                    <a href="{{ route('my-events.index') }}" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
