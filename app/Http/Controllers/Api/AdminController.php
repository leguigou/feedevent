<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\SettingManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function __construct(private readonly SettingManager $settings) {}

    // ─── DASHBOARD STATS ──────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $totalEvents = Event::withTrashed()->count();
        $publishedEvents = Event::where('status', 'published')->count();
        $archivedEvents = Event::where('status', 'archived')->count();
        $draftEvents = Event::where('status', 'draft')->count();
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsers = User::where('created_at', '>=', now()->subDays(30))->count();
        $totalCategories = Category::count();
        $savedEvents = DB::table('event_user')->count();
        $llmEvents = Event::withTrashed()->where('is_llm_generated', true)->count();
        $facebookEvents = Event::withTrashed()->where('source_type', 'facebook')->count();

        // Events by month (last 12)
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', date_start)"
            : "DATE_FORMAT(date_start, '%Y-%m')";

        $eventsByMonth = Event::withTrashed()
            ->selectRaw("{$monthExpression} as month, count(*) as total")
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
            ->map(fn ($e) => [
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
            'active_users' => $activeUsers,
            'new_users_30d' => $newUsers,
            'total_categories' => $totalCategories,
            'saved_events' => $savedEvents,
            'llm_events' => $llmEvents,
            'facebook_events' => $facebookEvents,
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
            'slug' => 'sometimes|string|max:255|unique:categories,slug,'.$category->id,
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

        $users = $query->withCount(['events', 'preferences', 'savedEvents'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($users);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->user()->is($user)) {
            if (($validated['role'] ?? 'admin') !== 'admin' || ($validated['is_active'] ?? true) === false) {
                throw ValidationException::withMessages([
                    'user' => 'Vous ne pouvez pas retirer vos propres droits administrateur.',
                ]);
            }
        }

        if ($user->role === 'admin' && ($validated['role'] ?? 'admin') !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Le dernier administrateur ne peut pas être rétrogradé.',
            ]);
        }

        $user->update($validated);

        return response()->json(
            $user->fresh()->loadCount(['events', 'preferences', 'savedEvents']),
        );
    }

    public function deleteUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Vous ne pouvez pas supprimer votre propre compte depuis le back-office.',
            ]);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Le dernier administrateur ne peut pas être supprimé.',
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    // ─── SETTINGS ───────────────────────────────────────────────────────────

    public function settings(): JsonResponse
    {
        $groups = [];

        foreach ($this->settingsCatalog() as $key => $definition) {
            $isSecret = $this->settings->isSecret($key);
            $value = $this->settings->get($key);

            $groups[$definition['group']][] = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'help' => $definition['help'] ?? null,
                'options' => $definition['options'] ?? null,
                'value' => $isSecret ? '' : $value,
                'secret' => $isSecret,
                'configured' => $isSecret ? filled($value) : null,
                'source' => $this->settings->hasStored($key) ? 'backoffice' : 'environment',
            ];
        }

        return response()->json(['groups' => $groups]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'settings' => ['required', 'array'],
            'clear' => ['sometimes', 'array'],
            'clear.*' => ['string'],
        ]);

        $catalog = $this->settingsCatalog();

        foreach ($payload['settings'] as $key => $value) {
            if (! isset($catalog[$key])) {
                throw ValidationException::withMessages([$key => 'Paramètre inconnu.']);
            }

            if ($this->settings->isSecret($key) && blank($value)) {
                continue;
            }

            $validated = Validator::make(
                ['value' => $value],
                ['value' => $catalog[$key]['rules']],
            )->validate();

            $this->settings->set($key, $validated['value'], $request->user()->id);
        }

        foreach ($payload['clear'] ?? [] as $key) {
            if (isset($catalog[$key])) {
                $this->settings->forget($key);
            }
        }

        return $this->settings();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function settingsCatalog(): array
    {
        return [
            'site.name' => ['group' => 'Site', 'label' => 'Nom du site', 'type' => 'text', 'rules' => ['required', 'string', 'max:80']],
            'site.support_email' => ['group' => 'Site', 'label' => 'E-mail de support', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:255']],
            'site.default_city' => ['group' => 'Site', 'label' => 'Ville par défaut', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
            'site.registration_enabled' => ['group' => 'Site', 'label' => 'Autoriser les inscriptions', 'type' => 'boolean', 'rules' => ['required', 'boolean']],

            'llm.provider' => ['group' => 'LLM', 'label' => 'Provider', 'type' => 'select', 'options' => ['openrouter' => 'OpenRouter', 'deepseek' => 'DeepSeek', 'custom' => 'Compatible OpenAI'], 'rules' => ['required', Rule::in(['openrouter', 'deepseek', 'custom'])]],
            'llm.api_key' => ['group' => 'LLM', 'label' => 'Clé API', 'type' => 'password', 'help' => 'Laisser vide pour conserver la clé actuelle.', 'rules' => ['nullable', 'string', 'max:2048']],
            'llm.base_url' => ['group' => 'LLM', 'label' => 'URL de base', 'type' => 'url', 'rules' => ['required', 'url', 'max:2048']],
            'llm.model' => ['group' => 'LLM', 'label' => 'Modèle texte', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
            'llm.vision_model' => ['group' => 'LLM', 'label' => 'Modèle vision', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
            'llm.temperature' => ['group' => 'LLM', 'label' => 'Température', 'type' => 'number', 'rules' => ['required', 'numeric', 'between:0,2']],
            'llm.max_tokens' => ['group' => 'LLM', 'label' => 'Jetons maximum', 'type' => 'number', 'rules' => ['required', 'integer', 'between:128,32000']],

            'facebook.enabled' => ['group' => 'Facebook', 'label' => 'Activer Facebook', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
            'facebook.app_id' => ['group' => 'Facebook', 'label' => 'App ID', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
            'facebook.app_secret' => ['group' => 'Facebook', 'label' => 'App Secret', 'type' => 'password', 'help' => 'Chiffré avec APP_KEY. Laisser vide pour conserver.', 'rules' => ['nullable', 'string', 'max:2048']],
            'facebook.redirect_uri' => ['group' => 'Facebook', 'label' => 'URL de redirection OAuth', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:2048']],
            'facebook.graph_version' => ['group' => 'Facebook', 'label' => 'Version Graph API', 'type' => 'text', 'rules' => ['required', 'regex:/^v\d+\.\d+$/']],
            'facebook.system_access_token' => ['group' => 'Facebook', 'label' => 'Jeton système', 'type' => 'password', 'help' => 'Optionnel. Les jetons personnels seront gérés par OAuth.', 'rules' => ['nullable', 'string', 'max:4096']],
        ];
    }

    // ─── LOGS ─────────────────────────────────────────────────────

    public function logs(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
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
                    'message' => substr($line, strpos($line, $m[3].':') + strlen($m[3]) + 2),
                    'full' => $line,
                ];
            } elseif ($currentEntry) {
                $currentEntry['full'] .= "\n".$line;
            }
        }
        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        // Filter by level
        if ($request->filled('level')) {
            $entries = array_filter($entries, fn ($e) => strtolower($e['level']) === strtolower($request->level));
        }

        // Filter by date (last N days)
        $days = (int) $request->input('days', 30);
        $cutoff = now()->subDays($days);
        $entries = array_filter($entries, fn ($e) => Carbon::parse($e['timestamp'])->greaterThanOrEqualTo($cutoff));

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
