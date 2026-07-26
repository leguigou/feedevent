<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConnectorImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'date_start' => ['required', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
        ]);

        $facebookId = $this->facebookEventId($validated['source_url']);
        $duplicate = Event::withTrashed()
            ->where(function ($query) use ($facebookId, $validated) {
                $query->where('source_url', $validated['source_url'])
                    ->when($facebookId, fn ($query) => $query->orWhere('facebook_event_id', $facebookId));
            })
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Cet événement a déjà été importé.',
                'event_id' => $duplicate->id,
            ], 409);
        }

        $event = Event::create([
            ...$validated,
            'description' => $validated['description'] ?? null,
            'date_end' => $validated['date_end'] ?? null,
            'location' => $validated['location'] ?? null,
            'address' => $validated['address'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'organizer' => $validated['organizer'] ?? null,
            'price' => $validated['price'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'source_type' => $facebookId ? 'facebook' : 'scrape',
            'facebook_event_id' => $facebookId,
            'user_id' => $request->user()->id,
            'status' => 'draft',
            'is_llm_generated' => false,
            'llm_meta' => [
                'connector' => 'chrome',
                'imported_at' => now()->toIso8601String(),
                'requires_review' => true,
            ],
        ]);

        return response()->json([
            'message' => 'Événement envoyé pour validation.',
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status,
            ],
        ], 201);
    }

    private function facebookEventId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com'], true)) {
            return null;
        }

        return preg_match('~/events/(\d+)~', $url, $matches) ? $matches[1] : null;
    }
}
