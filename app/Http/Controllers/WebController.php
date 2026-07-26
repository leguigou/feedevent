<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;

class WebController extends Controller
{
    public function home()
    {
        $categories = Category::all();

        return view('home', compact('categories'));
    }

    public function calendar()
    {
        return view('calendar');
    }

    public function map()
    {
        return view('map', ['categories' => Category::orderBy('name')->get()]);
    }

    public function show(Event $event)
    {
        abort_unless($event->status === 'published', 404);

        $event->load('category');
        $relatedEvents = Event::query()
            ->where('status', 'published')
            ->whereKeyNot($event->id)
            ->when($event->category_id, fn ($query) => $query->where('category_id', $event->category_id))
            ->where('date_start', '>=', now())
            ->with('category')
            ->orderBy('date_start')
            ->limit(3)
            ->get();

        $isSaved = auth()->check()
            && auth()->user()->savedEvents()->whereKey($event->id)->exists();
        $userPreference = auth()->check()
            ? auth()->user()->preferences()->where('event_id', $event->id)->value('type')
            : null;

        $pageTitle = $event->title.' — Feedevent';
        $pageDescription = str($event->description ?: "Découvre {$event->title} sur Feedevent.")
            ->squish()
            ->limit(155);
        $safeSourceUrl = filter_var($event->source_url, FILTER_VALIDATE_URL)
            && in_array(parse_url($event->source_url, PHP_URL_SCHEME), ['http', 'https'], true)
                ? $event->source_url
                : null;

        return view('events.show', compact('event', 'relatedEvents', 'isSaved', 'userPreference', 'pageTitle', 'pageDescription', 'safeSourceUrl'));
    }

    public function saved()
    {
        return view('saved');
    }

    public function calendarDownload(Event $event)
    {
        abort_unless($event->status === 'published', 404);

        $escape = fn (?string $value) => str_replace(
            ['\\', "\r\n", "\n", ',', ';'],
            ['\\\\', '\\n', '\\n', '\\,', '\\;'],
            $value ?? '',
        );
        $start = $event->date_start->utc()->format('Ymd\THis\Z');
        $end = ($event->date_end ?: $event->date_start->copy()->addHours(2))->utc()->format('Ymd\THis\Z');
        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Feedevent//FR',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            "UID:event-{$event->id}@feedevent.fr",
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            "DTSTART:{$start}",
            "DTEND:{$end}",
            'SUMMARY:'.$escape($event->title),
            'DESCRIPTION:'.$escape($event->description),
            'LOCATION:'.$escape($event->address ?: $event->location),
            'URL:'.route('events.show', $event),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="feedevent-'.$event->id.'.ics"',
        ]);
    }
}
