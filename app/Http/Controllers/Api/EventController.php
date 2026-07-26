<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->where('status', 'published')->with('category');

        // Filtre par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtre par date (à partir de maintenant par défaut)
        $query->where('date_start', '>=', $request->input('from', now()));

        if ($request->filled('to')) {
            $query->where('date_start', '<=', $request->to);
        }

        // Filtre par localisation (rayon en km)
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $radius = (int) $request->input('radius', 50);

            // Approximation Haversine
            $query->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                [$lat, $lng, $lat, $radius]
            );
        }

        // Recherche fulltext
        if ($request->filled('search')) {
            $query->whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', [$request->search]);
        }

        $events = $query->orderBy('date_start')->paginate($request->input('per_page', 20));

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($event->load('category'));
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
        $validated['status'] = 'published';

        $event = Event::create($validated);

        return response()->json($event->load('category'), 201);
    }

    public function like(Event $event): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        UserPreference::updateOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['type' => 'like']
        );

        return response()->json(['status' => 'liked']);
    }

    public function dislike(Event $event): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        UserPreference::updateOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['type' => 'dislike']
        );

        return response()->json(['status' => 'disliked']);
    }

    public function removePreference(Event $event): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
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
                $query->orderByRaw('CASE WHEN category_id IN (' . $likedCategoryIds->implode(',') . ') THEN 0 ELSE 1 END');
            }
        }

        $events = $query->where('date_start', '>=', now())
            ->orderBy('date_start')
            ->limit(20)
            ->get();

        return response()->json($events);
    }
}
