<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\UserPreference;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->where('status', 'published')->with('category');

        // Filtre par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $query->where('date_start', '>=', $request->input('from', now()));

        if ($request->filled('to')) {
            $query->where('date_start', '<=', $request->to);
        }

        if ($request->filled('date_filter')) {
            [$from, $to] = $this->dateRange($request->string('date_filter')->toString());
            $query->whereBetween('date_start', [$from, $to]);
        }

        if ($request->boolean('free')) {
            $query->where(fn ($price) => $price->whereNull('price')->orWhere('price', 0));
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $radius = min(max((int) $request->input('radius', 25), 1), 200);

            if (DB::getDriverName() === 'mysql') {
                $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';
                $query->select('events.*')
                    ->selectRaw("$distanceSql as distance_km", [$lat, $lng, $lat])
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radius]);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($nested) use ($search) {
                $nested->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('organizer', 'like', "%{$search}%");
            });
        }

        $events = $query->orderBy('date_start')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $this->appendUserState($events->getCollection(), $request);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Not found'], 404);
        }

        $event->load('category');
        $this->appendUserState(collect([$event]), request());

        return response()->json($event);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'source_url' => 'nullable|url|max:2048',
            'source_type' => 'nullable|in:facebook,manual,scrape,flyer',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'nullable|numeric|min:0',
            'organizer' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['status'] = $request->user()?->role === 'admin' ? 'published' : 'draft';

        $event = Event::create($validated);

        return response()->json([
            'event' => $event->load('category'),
            'message' => $validated['status'] === 'draft'
                ? 'Merci ! Ton événement a été envoyé pour validation.'
                : 'Événement publié.',
        ], 201);
    }

    public function like(Event $event): JsonResponse
    {
        $user = request()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->togglePreference($event, 'like');
    }

    public function dislike(Event $event): JsonResponse
    {
        $user = request()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->togglePreference($event, 'dislike');
    }

    public function removePreference(Event $event): JsonResponse
    {
        $user = request()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        UserPreference::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->delete();

        return response()->json(['status' => 'removed']);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Event::query()->where('status', 'published')->with('category');

        if ($user) {
            // Exclure les events dislikés
            $dislikedIds = UserPreference::where('user_id', $user->id)
                ->where('type', 'dislike')
                ->pluck('event_id');

            $query->whereNotIn('id', $dislikedIds);

            // Favoriser les catégories likées
            $likedCategoryIds = UserPreference::where('user_id', $user->id)
                ->where('type', 'like')
                ->join('events', 'user_preferences.event_id', '=', 'events.id')
                ->pluck('events.category_id')
                ->unique();

            if ($likedCategoryIds->isNotEmpty()) {
                $query->orderByRaw('CASE WHEN category_id IN ('.$likedCategoryIds->implode(',').') THEN 0 ELSE 1 END');
            }
        }

        $events = $query->where('date_start', '>=', now())
            ->orderBy('date_start')
            ->limit(20)
            ->get();

        return response()->json($events);
    }

    public function toggleSave(Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $user = request()->user();
        $saved = $user->savedEvents()->whereKey($event->id)->exists();

        if ($saved) {
            $user->savedEvents()->detach($event->id);
        } else {
            $user->savedEvents()->syncWithoutDetaching([$event->id]);
        }

        return response()->json(['is_saved' => ! $saved]);
    }

    public function saved(Request $request): JsonResponse
    {
        $events = Event::query()
            ->where('status', 'published')
            ->whereHas('savedBy', fn ($query) => $query->whereKey($request->user()->id))
            ->with('category')
            ->orderBy('date_start')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $this->appendUserState($events->getCollection(), $request);

        return response()->json($events);
    }

    private function togglePreference(Event $event, string $type): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $user = request()->user();
        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($preference?->type === $type) {
            $preference->delete();

            return response()->json(['preference' => null]);
        }

        UserPreference::updateOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['type' => $type],
        );

        return response()->json(['preference' => $type]);
    }

    private function appendUserState($events, Request $request): void
    {
        if (! $request->user() || $events->isEmpty()) {
            return;
        }

        $user = $request->user();
        $ids = $events->pluck('id');
        $preferences = UserPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $ids)
            ->pluck('type', 'event_id');
        $savedIds = $user->savedEvents()->whereIn('events.id', $ids)->pluck('events.id')->flip();

        $events->each(function (Event $event) use ($preferences, $savedIds): void {
            $event->setAttribute('user_preference', $preferences->get($event->id));
            $event->setAttribute('is_saved', $savedIds->has($event->id));
        });
    }

    private function dateRange(string $filter): array
    {
        $now = now();

        return match ($filter) {
            'today' => [$now, $now->copy()->endOfDay()],
            'tonight' => [$now->copy()->setTime(17, 0), $now->copy()->addDay()->setTime(5, 0)],
            'weekend' => $now->isSunday()
                ? [$now, $now->copy()->endOfDay()]
                : [
                    $now->isSaturday() ? $now : $now->copy()->next(Carbon::FRIDAY)->setTime(17, 0),
                    $now->copy()->next(Carbon::SUNDAY)->endOfDay(),
                ],
            default => [$now, $now->copy()->addYear()],
        };
    }
}
