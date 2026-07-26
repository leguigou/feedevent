<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MyEventController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['published', 'draft', 'archived'], true)
            ? $request->query('status')
            : null;

        $events = $request->user()
            ->events()
            ->with('category')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('date_start')
            ->paginate(12)
            ->withQueryString();

        $statusCounts = $request->user()
            ->events()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $logs = $request->user()
            ->importLogs()
            ->latest()
            ->limit(20)
            ->get();

        return view('my-events.index', compact('events', 'status', 'statusCounts', 'logs'));
    }

    public function edit(Request $request, Event $event): View
    {
        $this->authorizeOwner($request, $event);

        return view('my-events.edit', [
            'event' => $event,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'date_start' => ['required', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $event->update($validated);

        return redirect()
            ->route('my-events.index')
            ->with('status', 'event-updated');
    }

    public function updateStatus(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['published', 'archived'])],
        ]);
        $event->update($validated);

        return back()->with('status', 'event-status-updated');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);
        $event->delete();

        return back()->with('status', 'event-deleted');
    }

    private function authorizeOwner(Request $request, Event $event): void
    {
        abort_unless($event->user_id === $request->user()->id, 404);
    }
}
