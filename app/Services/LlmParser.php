<?php

namespace App\Services;

use App\Helpers\DateNormalizer;
use App\Models\Category;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LlmParser — Parse URLs and images to extract structured event data via LLM.
 *
 * Uses OpenRouter as the primary gateway (with DeepSeek as default model)
 * and falls back to DeepSeek API if needed.
 */
class LlmParser
{
    public function __construct(private readonly SettingManager $settings) {}

    /**
     * HTTP timeout in seconds.
     */
    protected int $timeout = 60;

    /**
     * Maximum content length (chars) to send to the LLM for URL parsing.
     */
    protected int $maxContentLength = 20_000;

    /**
     * Parse multiple sources and return structured event data.
     *
     * @param  array  $sources  Array of source strings (URLs or local image paths)
     * @param  int|null  $userId  Optional user ID to associate with the parsed event
     * @return array Parsed result with event data and metadata
     */
    public function parse(array $sources, ?int $userId = null): array
    {
        $results = [];

        foreach ($sources as $source) {
            $type = $this->detectSourceType($source);

            try {
                $parsed = match ($type) {
                    'url' => $this->parseUrl($source),
                    'image' => $this->parseImage($source),
                    default => throw new Exception("Unsupported source type: {$type}"),
                };

                if ($parsed !== null) {
                    $parsed['_source'] = $source;
                    $parsed['_type'] = $type;
                    $results[] = $parsed;
                }
            } catch (Exception $e) {
                Log::warning('LlmParser: failed to parse source', [
                    'source' => $source,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Merge multiple results if more than one source was provided
        return $this->mergeResults($results);
    }

    /**
     * Parse a single URL and extract event information via LLM.
     *
     * @param  string  $url  The URL to parse
     * @return array|null Structured event data or null on failure
     */
    public function parseUrl(string $url): ?array
    {
        // Fetch the page content
        $content = $this->fetchUrlContent($url);

        if (empty($content)) {
            throw new Exception("Failed to fetch content from URL: {$url}");
        }

        // Truncate to avoid blowing the LLM context
        $content = mb_substr($content, 0, $this->maxContentLength);

        // Extract structured data via LLM
        return $this->extractWithLLM([
            'type' => 'url',
            'url' => $url,
            'content' => $content,
        ]);
    }

    /**
     * Parse an image to extract event information via Vision LLM.
     *
     * @param  string  $imagePath  Local path or URL to the image
     * @return array|null Structured event data or null on failure
     */
    public function parseImage(string $imagePath): ?array
    {
        // If it's a local path, check it exists; otherwise treat as URL
        $imageData = $this->prepareImageForVision($imagePath);

        if ($imageData === null) {
            throw new Exception("Cannot read image: {$imagePath}");
        }

        return $this->extractWithVisionLLM($imageData);
    }

    /**
     * Fetch a preview of parsed data WITHOUT creating an event.
     *
     * @param  string  $source  URL to parse for preview
     * @return array Parsed data with confidence info
     */
    public function parsePreview(string $source): array
    {
        $type = $this->detectSourceType($source);

        try {
            $parsed = match ($type) {
                'url' => $this->parseUrl($source),
                'image' => $this->parseImage($source),
                default => throw new Exception('Unsupported source type'),
            };

            if ($parsed === null) {
                return [
                    'success' => false,
                    'error' => 'Could not parse event information from the source.',
                ];
            }

            return [
                'success' => true,
                'source' => $source,
                'source_type' => $type,
                'event' => $parsed,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────
    //   SOURCE DETECTION
    // ─────────────────────────────────────────────

    /**
     * Detect whether a source is a URL or a local image path.
     */
    protected function detectSourceType(string $source): string
    {
        // If it matches a URL pattern, treat as URL
        if (filter_var($source, FILTER_VALIDATE_URL) !== false) {
            return 'url';
        }

        // If it's a local file with an image extension, treat as image
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        if (in_array($extension, $imageExtensions, true) && file_exists($source)) {
            return 'image';
        }

        // Check if it's a URL that didn't pass FILTER_VALIDATE_URL
        // (e.g., missing scheme)
        if (preg_match('#^https?://#i', $source) || preg_match('#^[a-z0-9]([-a-z0-9]*[a-z0-9])?\.[a-z]{2,}#i', $source)) {
            return 'url';
        }

        // Default: treat as image path (will fail gracefully)
        return 'image';
    }

    // ─────────────────────────────────────────────
    //   URL CONTENT FETCHING
    // ─────────────────────────────────────────────

    /**
     * Fetch and extract textual content from a URL.
     */
    protected function fetchUrlContent(string $url): string
    {
        // Ensure the URL has a scheme
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new Exception("HTTP {$response->status()} for URL: {$url}");
            }

            $html = $response->body();

            // Extract meaningful text content from HTML
            return $this->extractTextFromHtml($html);
        } catch (ConnectionException $e) {
            // Fallback: try with file_get_contents + stream context
            return $this->fetchUrlContentFallback($url);
        }
    }

    /**
     * Fallback URL fetching using file_get_contents.
     */
    protected function fetchUrlContentFallback(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
                ],
                'timeout' => $this->timeout,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            throw new Exception("Failed to fetch URL (fallback): {$url}");
        }

        return $this->extractTextFromHtml($html);
    }

    /**
     * Strip HTML tags and extract readable text.
     */
    protected function extractTextFromHtml(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', ' ', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', ' ', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/si', ' ', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/si', ' ', $html);

        // Decode HTML entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text); // Remove control chars except newlines

        return trim($text);
    }

    // ─────────────────────────────────────────────
    //   IMAGE PREPARATION
    // ─────────────────────────────────────────────

    /**
     * Prepare image data for vision API calls.
     * Returns a data URL or base64 representation.
     */
    protected function prepareImageForVision(string $imagePath): ?array
    {
        // If it's a remote URL, return it as-is
        if (filter_var($imagePath, FILTER_VALIDATE_URL) !== false) {
            return ['type' => 'url', 'data' => $imagePath];
        }

        // Local file: read and convert to base64 data URL
        if (! file_exists($imagePath)) {
            // Maybe it's a URL without scheme — try prepending
            if (preg_match('#^[a-z0-9]([-a-z0-9]*[a-z0-9])?\.[a-z]{2,}#i', $imagePath)) {
                return ['type' => 'url', 'data' => 'https://'.$imagePath];
            }

            return null;
        }

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
        ];

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$extension] ?? 'image/jpeg';

        $binary = file_get_contents($imagePath);
        if ($binary === false) {
            return null;
        }

        $base64 = base64_encode($binary);

        return [
            'type' => 'base64',
            'data' => "data:{$mime};base64,{$base64}",
        ];
    }

    // ─────────────────────────────────────────────
    //   LLM EXTRACTION (Text, from URL content)
    // ─────────────────────────────────────────────

    /**
     * Extract event information from text content using an LLM.
     */
    protected function extractWithLLM(array $input): ?array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($input);

        $response = $this->callOpenRouter($systemPrompt, $userPrompt);

        if ($response === null) {
            return null;
        }

        return $this->normalizeParsedData($response);
    }

    /**
     * Extract event information from an image using a Vision LLM.
     */
    protected function extractWithVisionLLM(array $imageData): ?array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildVisionUserPrompt($imageData);

        $response = $this->callOpenRouterVision($systemPrompt, $userPrompt, $imageData);

        if ($response === null) {
            return null;
        }

        return $this->normalizeParsedData($response);
    }

    // ─────────────────────────────────────────────
    //   PROMPT BUILDING
    // ─────────────────────────────────────────────

    /**
     * Build the system prompt for event extraction.
     */
    protected function buildSystemPrompt(): string
    {
        $categories = Category::pluck('slug')->toArray();
        $categoriesList = implode(', ', $categories);

        return <<<PROMPT
Tu es un assistant spécialisé dans l'extraction d'informations d'événements.

Analyse le contenu fourni et extrait les informations structurées de l'événement.
Répond UNIQUEMENT avec un objet JSON valide, sans commentaires ni texte additionnel.

L'objet JSON doit contenir les champs suivants (tous optionnels sauf title):
{
  "title": "Titre de l'événement",
  "description": "Description complète de l'événement",
  "date_start": "Date de début (format: YYYY-MM-DD HH:MM:SS ou texte lisible comme '15 août 2026 à 20h')",
  "date_end": "Date de fin (même format, nullable)",
  "location": "Nom du lieu",
  "address": "Adresse complète",
  "latitude": "Latitude (nombre décimal ou null)",
  "longitude": "Longitude (nombre décimal ou null)",
  "image_url": "URL de l'image de l'événement si présente dans le contenu",
  "price": "Prix (nombre ou null si gratuit ou non spécifié)",
  "organizer": "Organisateur de l'événement",
  "tags": ["liste", "de", "mots-clés"],
  "category": "Catégorie parmi: {$categoriesList} (choisis la plus appropriée)",
  "confidence": "Niveau de confiance de 0.0 à 1.0"
}

IMPORTANT:
- Si tu trouves une date en français (ex: "15 août 2026 à 20h30"), normalise-la en format ISO.
- Sois précis sur les dates et lieux.
- Si une information n'est pas disponible, mets null ou une chaîne vide selon le type.
- Le champ confidence indique à quel point tu es certain des informations extraites.
PROMPT;
    }

    /**
     * Build the user prompt for URL-based extraction.
     */
    protected function buildUserPrompt(array $input): string
    {
        return <<<PROMPT
Voici le contenu extrait d'une page web (source: {$input['url']}):

---
{$input['content']}
---

Extrais les informations de l'événement présenté dans ce contenu au format JSON.
PROMPT;
    }

    /**
     * Build the vision user prompt for image-based extraction.
     */
    protected function buildVisionUserPrompt(array $imageData): string
    {
        return <<<'PROMPT'
Cette image contient probablement un flyer ou une affiche d'événement.
Extrais toutes les informations de l'événement (titre, date, lieu, description, prix, organisateur, etc.)
et retourne-les au format JSON demandé.
PROMPT;
    }

    // ─────────────────────────────────────────────
    //   API CALLS (OpenRouter)
    // ─────────────────────────────────────────────

    /**
     * Call OpenRouter API for text-based LLM extraction.
     */
    protected function callOpenRouter(string $systemPrompt, string $userPrompt): ?array
    {
        $apiKey = $this->settings->get('llm.api_key');

        if (empty($apiKey)) {
            Log::error('LlmParser: OPENROUTER_API_KEY is not configured');
            throw new Exception('OpenRouter API key is not configured');
        }

        $model = $this->settings->get('llm.model', 'deepseek/deepseek-chat');
        $baseUrl = rtrim($this->settings->get('llm.base_url', 'https://openrouter.ai/api/v1'), '/');

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => (float) $this->settings->get('llm.temperature', 0.1),
            'max_tokens' => (int) $this->settings->get('llm.max_tokens', 2000),
            'response_format' => ['type' => 'json_object'],
        ];

        return $this->sendApiRequest($baseUrl, $apiKey, $payload);
    }

    /**
     * Call OpenRouter API with vision capabilities.
     */
    protected function callOpenRouterVision(string $systemPrompt, string $userPrompt, array $imageData): ?array
    {
        $apiKey = $this->settings->get('llm.api_key');

        if (empty($apiKey)) {
            Log::error('LlmParser: OPENROUTER_API_KEY is not configured');
            throw new Exception('OpenRouter API key is not configured');
        }

        $visionModel = $this->settings->get('llm.vision_model', 'meta-llama/llama-3.2-11b-vision-instruct');
        $baseUrl = rtrim($this->settings->get('llm.base_url', 'https://openrouter.ai/api/v1'), '/');

        // Build message with image content
        $userContent = [
            ['type' => 'text', 'text' => $userPrompt],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageData['data'],
                ],
            ],
        ];

        $payload = [
            'model' => $visionModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent],
            ],
            'temperature' => (float) $this->settings->get('llm.temperature', 0.1),
            'max_tokens' => (int) $this->settings->get('llm.max_tokens', 2000),
            'response_format' => ['type' => 'json_object'],
        ];

        return $this->sendApiRequest($baseUrl, $apiKey, $payload);
    }

    /**
     * Send the actual HTTP request to the API.
     */
    protected function sendApiRequest(string $baseUrl, string $apiKey, array $payload): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => 'FeedEvent',
                ])
                ->post($baseUrl.'/chat/completions', $payload);

            if (! $response->successful()) {
                Log::error('LlmParser: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("API request failed with status {$response->status()}");
            }

            $body = $response->json();

            if (! isset($body['choices'][0]['message']['content'])) {
                Log::error('LlmParser: Unexpected API response structure', ['body' => $body]);
                throw new Exception('Unexpected API response structure');
            }

            $content = $body['choices'][0]['message']['content'];

            // Extract JSON from the response (handle markdown-wrapped JSON)
            $json = $this->extractJson($content);

            if ($json === null) {
                Log::error('LlmParser: Failed to parse JSON from LLM response', ['content' => $content]);
                throw new Exception('Failed to parse JSON from LLM response');
            }

            return $json;
        } catch (ConnectionException $e) {
            Log::error('LlmParser: Connection error to API', ['error' => $e->getMessage()]);
            throw new Exception('Connection error to LLM API: '.$e->getMessage());
        }
    }

    // ─────────────────────────────────────────────
    //   DATA NORMALIZATION
    // ─────────────────────────────────────────────

    /**
     * Normalize the parsed data: convert dates, resolve category, etc.
     */
    protected function normalizeParsedData(array $data): array
    {
        // Normalize dates
        $dateStart = null;
        $dateEnd = null;

        if (! empty($data['date_start'])) {
            $parsedStart = DateNormalizer::normalize($data['date_start']);
            if ($parsedStart) {
                $dateStart = $parsedStart;
            }
        }

        if (! empty($data['date_end'])) {
            $parsedEnd = DateNormalizer::normalize($data['date_end']);
            if ($parsedEnd) {
                $dateEnd = $parsedEnd;
            }
        }

        // Resolve category slug to ID
        $categoryId = null;
        if (! empty($data['category'])) {
            $category = Category::where('slug', $data['category'])->first();
            $categoryId = $category?->id;
        }

        // Clean tags
        $tags = null;
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = array_values(array_filter(array_map('trim', $data['tags'])));
            if (empty($tags)) {
                $tags = null;
            }
        }

        // Normalize price
        $price = null;
        if (isset($data['price']) && is_numeric($data['price'])) {
            $price = (float) $data['price'];
        }

        return [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'date_start' => $dateStart?->toDateTimeString(),
            'date_end' => $dateEnd?->toDateTimeString(),
            'location' => $data['location'] ?? '',
            'address' => $data['address'] ?? '',
            'latitude' => isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null,
            'longitude' => isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null,
            'image_url' => $data['image_url'] ?? '',
            'price' => $price,
            'organizer' => $data['organizer'] ?? '',
            'tags' => $tags,
            'category_id' => $categoryId,
            'confidence' => (float) ($data['confidence'] ?? 0.5),
        ];
    }

    /**
     * Merge results from multiple sources, taking the highest-confidence values.
     */
    protected function mergeResults(array $results): array
    {
        if (empty($results)) {
            return [
                'success' => false,
                'error' => 'No event information could be extracted from the provided sources.',
            ];
        }

        if (count($results) === 1) {
            $data = $results[0];
            $meta = [
                'parsed_at' => now()->toIso8601String(),
                'sources' => [$data['_source']],
                'source_types' => [$data['_type']],
                'confidence' => $data['confidence'],
                'model' => $this->settings->get('llm.model', 'deepseek/deepseek-chat'),
            ];

            unset($data['_source'], $data['_type']);

            return [
                'success' => true,
                'event' => $data,
                'llm_meta' => $meta,
            ];
        }

        // Merge multiple results: pick the one with highest confidence
        usort($results, fn ($a, $b) => ($b['confidence'] ?? 0) <=> ($a['confidence'] ?? 0));
        $best = $results[0];

        $sources = [];
        $types = [];
        foreach ($results as $r) {
            $sources[] = $r['_source'];
            $types[] = $r['_type'];
        }

        $meta = [
            'parsed_at' => now()->toIso8601String(),
            'sources' => $sources,
            'source_types' => $types,
            'confidence' => $best['confidence'],
            'model' => $this->settings->get('llm.model', 'deepseek/deepseek-chat'),
            'merged_from_count' => count($results),
        ];

        unset($best['_source'], $best['_type']);

        return [
            'success' => true,
            'event' => $best,
            'llm_meta' => $meta,
        ];
    }

    /**
     * Extract JSON from a string that may contain markdown code blocks or extra text.
     */
    protected function extractJson(string $content): ?array
    {
        // Try parsing the whole thing as JSON first
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try extracting from markdown code blocks ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Try finding first { and last }
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Set timeout value.
     */
    public function setTimeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set max content length.
     */
    public function setMaxContentLength(int $length): static
    {
        $this->maxContentLength = $length;

        return $this;
    }
}
