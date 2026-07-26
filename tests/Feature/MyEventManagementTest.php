<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyEventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_events_and_import_logs(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownEvent = $this->event($user, ['title' => 'Mon concert']);
        $this->event($otherUser, ['title' => 'Concert privé tiers']);
        ImportLog::create([
            'user_id' => $user->id,
            'source' => 'ics',
            'filename' => 'agenda.ics',
            'total' => 1,
            'imported' => 1,
        ]);
        ImportLog::create([
            'user_id' => $otherUser->id,
            'source' => 'chrome',
            'total' => 1,
            'imported' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('my-events.index'))
            ->assertOk()
            ->assertSee($ownEvent->title)
            ->assertSee('agenda.ics')
            ->assertDontSee('Concert privé tiers');
    }

    public function test_owner_can_edit_archive_publish_and_delete_their_event(): void
    {
        $user = User::factory()->create();
        $event = $this->event($user);

        $this->actingAs($user)
            ->put(route('my-events.update', $event), [
                'title' => 'Titre corrigé',
                'date_start' => now()->addMonth()->format('Y-m-d H:i:s'),
                'status' => 'published',
            ])
            ->assertRedirect(route('my-events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Titre corrigé',
            'status' => 'published',
        ]);

        $this->actingAs($user)
            ->patch(route('my-events.status', $event), ['status' => 'archived'])
            ->assertRedirect();
        $this->assertSame('archived', $event->fresh()->status);

        $this->actingAs($user)
            ->patch(route('my-events.status', $event), ['status' => 'published'])
            ->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);

        $this->actingAs($user)
            ->delete(route('my-events.destroy', $event))
            ->assertRedirect();
        $this->assertSoftDeleted($event);
    }

    public function test_user_cannot_manage_another_users_event(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = $this->event($owner);

        $this->actingAs($otherUser)
            ->get(route('my-events.edit', $event))
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->patch(route('my-events.status', $event), ['status' => 'archived'])
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->delete(route('my-events.destroy', $event))
            ->assertNotFound();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'published']);
    }

    private function event(User $user, array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Événement utilisateur',
            'description' => 'Description',
            'date_start' => now()->addWeek(),
            'status' => 'published',
            'source_type' => 'manual',
            'user_id' => $user->id,
            ...$attributes,
        ]);
    }
}
