<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\LlmParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EventImportController extends Controller
{
    protected LlmParser $llmParser;

    public function __construct(LlmParser $llmParser)
    {
        $this->llmParser = $llmParser;
    }

    /**
     * Import an event from one or more sources (URLs, image paths).
     *
     * POST /api/events/import
     *
     * @bodyParam sources array required Array of URLs or image paths to parse.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sources' => 'required|array|min:1',
                'sources.*' => 'required|string|max:4096',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $sources = $validated['sources'];
        $userId = $request->user()?->id;

        try {
            $result = $this->llmParser->parse($sources, $userId);
        } catch (\Exception $e) {
            Log::error('EventImport: LLM parsing failed', [
                'sources' => $sources,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to parse event information.',
                'error' => $e->getMessage(),
            ], 422);
        }

        if (! $result['success']) {
            return response()->json([
                'message' => $result['error'] ?? 'Could not extract event information from the provided sources.',
            ], 422);
        }

        $eventData = $result['event'];
        $llmMeta = $result['llm_meta'];

        // Validate minimum data
        if (empty($eventData['title'])) {
            return response()->json([
                'message' => 'Could not extract a title from the provided sources.',
                'parsed' => $eventData,
            ], 422);
        }

        // If no date was found, default to today
        if (empty($eventData['date_start'])) {
            $eventData['date_start'] = now()->toDateTimeString();
        }

        // Create the event
        $event = Event::create([
            'title' => $eventData['title'],
            'description' => $eventData['description'] ?: null,
            'date_start' => $eventData['date_start'],
            'date_end' => $eventData['date_end'] ?: null,
            'location' => $eventData['location'] ?: null,
            'address' => $eventData['address'] ?: null,
            'latitude' => $eventData['latitude'] ?? null,
            'longitude' => $eventData['longitude'] ?? null,
            'image_url' => $eventData['image_url'] ?: null,
            'price' => $eventData['price'] ?? null,
            'organizer' => $eventData['organizer'] ?: null,
            'tags' => $eventData['tags'],
            'category_id' => $eventData['category_id'] ?? null,
            'source_url' => $sources[0] ?? null,
            'source_type' => $this->detectSourceTypeFromLlmMeta($llmMeta),
            'user_id' => $userId,
            'status' => 'published',
            'is_llm_generated' => true,
            'llm_meta' => $llmMeta,
        ]);

        return response()->json([
            'message' => 'Event imported successfully.',
            'event' => $event->load('category'),
            'llm_meta' => $llmMeta,
        ], 201);
    }

    /**
     * Preview parsed data from a source WITHOUT creating an event.
     *
     * GET /api/events/parse-preview
     *
     * @bodyParam source string required URL or image path to preview.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function parsePreview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'source' => 'required|string|max:4096',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $preview = $this->llmParser->parsePreview($validated['source']);

        if (! $preview['success']) {
            return response()->json([
                'message' => $preview['error'] ?? 'Could not parse event information.',
            ], 422);
        }

        return response()->json([
            'source' => $preview['source'],
            'source_type' => $preview['source_type'],
            'event' => $preview['event'],
            'can_import' => ! empty($preview['event']['title']),
        ]);
    }

    /**
     * Detect source_type from LLM metadata.
     */
    protected function detectSourceTypeFromLlmMeta(array $llmMeta): string
    {
        $types = $llmMeta['source_types'] ?? [];

        if (in_array('image', $types, true)) {
            return 'flyer';
        }

        return 'scrape';
    }
}
