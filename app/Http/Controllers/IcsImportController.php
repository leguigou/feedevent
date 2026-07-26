<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ImportLog;
use App\Services\IcsParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class IcsImportController extends Controller
{
    public function store(Request $request, IcsParser $parser): RedirectResponse
    {
        $request->validate([
            'calendar' => ['required', 'file', 'extensions:ics', 'max:5120'],
        ]);

        try {
            $events = $parser->parse((string) file_get_contents($request->file('calendar')->getRealPath()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'calendar' => $exception->getMessage(),
            ]);
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $details = [];

        foreach ($events as $eventData) {
            $sourceUrl = $this->canonicalSourceUrl($eventData['source_url']);
            $facebookId = $this->facebookEventId($sourceUrl);

            if ($this->alreadyExists($sourceUrl, $facebookId, $eventData['uid'])) {
                $skipped++;
                $details[] = [
                    'title' => $eventData['title'],
                    'result' => 'skipped',
                ];

                continue;
            }

            try {
                $event = Event::create([
                    'title' => $eventData['title'],
                    'description' => $eventData['description'],
                    'date_start' => $eventData['date_start'],
                    'date_end' => $eventData['date_end'],
                    'location' => $eventData['location'],
                    'organizer' => $eventData['organizer'],
                    'source_url' => $sourceUrl,
                    'source_type' => $facebookId ? 'facebook' : 'manual',
                    'facebook_event_id' => $facebookId,
                    'user_id' => $request->user()->id,
                    'status' => 'published',
                    'is_llm_generated' => false,
                    'llm_meta' => [
                        'connector' => 'ics',
                        'ics_uid' => $eventData['uid'],
                        'imported_at' => now()->toIso8601String(),
                        'requires_review' => false,
                    ],
                ]);
                $imported++;
                $details[] = [
                    'title' => $event->title,
                    'event_id' => $event->id,
                    'result' => 'imported',
                ];
            } catch (Throwable $exception) {
                $failed++;
                $details[] = [
                    'title' => $eventData['title'],
                    'result' => 'failed',
                ];
                Log::warning('Échec de l’import d’un événement ICS.', [
                    'uid' => $eventData['uid'],
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        ImportLog::create([
            'user_id' => $request->user()->id,
            'source' => 'ics',
            'filename' => $request->file('calendar')->getClientOriginalName(),
            'status' => $failed > 0 ? ($imported > 0 ? 'partial' : 'failed') : 'success',
            'total' => count($events),
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'details' => $details,
        ]);

        return back()->with('ics-import', compact('imported', 'skipped', 'failed'));
    }

    private function alreadyExists(?string $sourceUrl, ?string $facebookId, ?string $uid): bool
    {
        if ($sourceUrl === null && $facebookId === null && $uid === null) {
            return false;
        }

        return Event::withTrashed()
            ->where(function ($query) use ($sourceUrl, $facebookId, $uid) {
                if ($sourceUrl !== null) {
                    $query->orWhere('source_url', $sourceUrl);
                }
                if ($facebookId !== null) {
                    $query->orWhere('facebook_event_id', $facebookId);
                }
                if ($uid !== null) {
                    $query->orWhere('llm_meta->ics_uid', $uid);
                }
            })
            ->exists();
    }

    private function canonicalSourceUrl(?string $url): ?string
    {
        $facebookId = $this->facebookEventId($url);

        return $facebookId
            ? "https://www.facebook.com/events/{$facebookId}/"
            : $url;
    }

    private function facebookEventId(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com'], true)) {
            return null;
        }

        return preg_match('~/events/(\d+)~', $url, $matches) ? $matches[1] : null;
    }
}
