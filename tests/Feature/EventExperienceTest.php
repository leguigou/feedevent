<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventExperienceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Concert',
            'slug' => 'concert',
            'color' => '#7c3aed',
            'icon' => '♪',
        ]);
    }

    public function test_feed_only_returns_published_upcoming_events(): void
    {
        $published = $this->event(['title' => 'Concert publié']);
        $this->event(['title' => 'Brouillon', 'status' => 'draft']);
        $this->event(['title' => 'Événement passé', 'date_start' => now()->subDay()]);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_toggle_a_saved_event(): void
    {
        $user = User::factory()->create();
        $event = $this->event();

        $this->actingAs($user)->postJson("/api/events/{$event->id}/save")
            ->assertOk()
            ->assertJson(['is_saved' => true]);

        $this->assertDatabaseHas('event_user', ['user_id' => $user->id, 'event_id' => $event->id]);

        $this->actingAs($user)->postJson("/api/events/{$event->id}/save")
            ->assertOk()
            ->assertJson(['is_saved' => false]);

        $this->assertDatabaseMissing('event_user', ['user_id' => $user->id, 'event_id' => $event->id]);
    }

    public function test_preference_endpoint_really_toggles_the_preference(): void
    {
        $user = User::factory()->create();
        $event = $this->event();

        $this->actingAs($user)->postJson("/api/events/{$event->id}/like")
            ->assertJson(['preference' => 'like']);
        $this->actingAs($user)->postJson("/api/events/{$event->id}/like")
            ->assertJson(['preference' => null]);

        $this->assertDatabaseMissing('user_preferences', ['user_id' => $user->id, 'event_id' => $event->id]);
    }

    public function test_user_contribution_is_created_as_a_draft(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Nouvelle sortie',
            'date_start' => now()->addWeek()->toIso8601String(),
            'category_id' => $this->category->id,
        ])->assertCreated()
            ->assertJsonPath('event.status', 'draft');

        $this->assertDatabaseHas('events', ['title' => 'Nouvelle sortie', 'status' => 'draft']);
    }

    public function test_event_detail_and_calendar_download_are_available(): void
    {
        $event = $this->event(['title' => 'Festival local']);

        $this->get("/events/{$event->id}")
            ->assertOk()
            ->assertSee('Festival local')
            ->assertSee('application/ld+json', false);

        $this->get("/events/{$event->id}/calendar.ics")
            ->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR');
    }

    public function test_draft_detail_is_not_public(): void
    {
        $event = $this->event(['status' => 'draft']);

        $this->get("/events/{$event->id}")->assertNotFound();
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Sortie de test',
            'description' => 'Une belle sortie locale.',
            'date_start' => now()->addDays(3),
            'location' => 'Paris',
            'category_id' => $this->category->id,
            'status' => 'published',
        ], $attributes));
    }
}
