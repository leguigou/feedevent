<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // ─── DASHBOARD STATS ──────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $totalEvents = Event::withTrashed()->count();
        $publishedEvents = Event::where('status', 'published')->count();
        $archivedEvents = Event::where('status', 'archived')->count();
        $draftEvents = Event::where('status', 'draft')->count();
        $totalUsers = User::count();
        $totalCategories = Category::count();

        // Events by month (last 12)
        $eventsByMonth = Event::withTrashed()
            ->selectRaw("DATE_FORMAT(date_start, '%Y-%m') as month, count(*) as total")
            ->where('date_start', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Events by category
        $eventsByCategory = Event::withTrashed()
            ->selectRaw('category_id, count(*) as total')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->with('category:id,name,icon,color')
            ->get()
            ->map(fn($e) => [
                'name' => $e->category?->name ?? 'Sans catégorie',
                'icon' => $e->category?->icon ?? '📌',
                'color' => $e->category?->color ?? '#6b7280',
                'total' => $e->total,
            ]);

        // Likes / dislikes counts
        $totalLikes = UserPreference::where('type', 'like')->count();
        $totalDislikes = UserPreference::where('type', 'dislike')->count();

        return response()->json([
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'archived_events' => $archivedEvents,
            'draft_events' => $draftEvents,
            'total_users' => $totalUsers,
            'total_categories' => $totalCategories,
            'events_by_month' => $eventsByMonth,
            'events_by_category' => $eventsByCategory,
            'total_likes' => $totalLikes,
            'total_dislikes' => $totalDislikes,
        ]);
    }

    // ─── EVENTS CRUD ─────────────────────────────────────────────

    public function events(Request $request): JsonResponse
    {
        $query = Event::withTrashed()->with('category', 'user:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $events = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($events);
    }

    public function storeEvent(Request $request): JsonResponse
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
            'status' => 'nullable|in:draft,published,archived',
            'price' => 'nullable|numeric|min:0',
            'organizer' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['status'] ??= 'published';

        $event = Event::create($validated);

        return response()->json($event->load('category'), 201);
    }

    public function updateEvent(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_start' => 'sometimes|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'source_url' => 'nullable|url|max:2048',
            'source_type' => 'nullable|in:facebook,manual,scrape,flyer',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:draft,published,archived',
            'price' => 'nullable|numeric|min:0',
            'organizer' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $event->update($validated);

        return response()->json($event->fresh()->load('category'));
    }

    public function deleteEvent(Event $event): JsonResponse
    {
        $event->delete(); // soft delete
        return response()->json(['message' => 'Event deleted']);
    }

    public function updateEventStatus(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived',
        ]);

        $event->update($validated);

        return response()->json(['status' => $event->status, 'message' => 'Status updated']);
    }

    // ─── CATEGORIES CRUD ─────────────────────────────────────────

    public function categories(): JsonResponse
    {
        return response()->json(Category::withCount('events')->orderBy('name')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:10',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $category->id,
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:10',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function deleteCategory(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }

    // ─── USERS ────────────────────────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['events', 'preferences'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($users);
    }

    // ─── LOGS ─────────────────────────────────────────────────────

    public function logs(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return response()->json(['logs' => []]);
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);

        // Parse log entries (Laravel format: [YYYY-MM-DD HH:MM:SS] local.ERROR: ...)
        $entries = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+):/', $line, $m)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = [
                    'timestamp' => $m[1],
                    'environment' => $m[2],
                    'level' => $m[3],
                    'message' => substr($line, strpos($line, $m[3] . ':') + strlen($m[3]) + 2),
                    'full' => $line,
                ];
            } elseif ($currentEntry) {
                $currentEntry['full'] .= "\n" . $line;
            }
        }
        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        // Filter by level
        if ($request->filled('level')) {
            $entries = array_filter($entries, fn($e) => strtolower($e['level']) === strtolower($request->level));
        }

        // Filter by date (last N days)
        $days = (int) $request->input('days', 30);
        $cutoff = now()->subDays($days);
        $entries = array_filter($entries, fn($e) => \Carbon\Carbon::parse($e['timestamp'])->greaterThanOrEqualTo($cutoff));

        // Reverse chronological
        $entries = array_reverse(array_values($entries));

        // Paginate
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);
        $total = count($entries);
        $paginated = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'data' => array_values($paginated),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function clearLogs(): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        return response()->json(['message' => 'Logs cleared']);
    }
}
