<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IcsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_import_an_ics_file_as_drafts(): void
    {
        $user = User::factory()->create();
        $calendar = <<<'ICS'
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:e1245674667674500@facebook.com
DTSTART:20260919T183000Z
DTEND:20260919T213000Z
ORGANIZER;CN=Festival de Musique Toulon et région:MAILTO:noreply@facebookmail.com
SUMMARY:400 ans d’histoire\, Toulon fête sa Marine
LOCATION:Fort Lamalgue\, Toulon
URL:https://www.facebook.com/events/1245674667674500/?acontext=long
DESCRIPTION:Spectacle son et lumière\nDeuxième ligne
END:VEVENT
END:VCALENDAR
ICS;

        $response = $this->actingAs($user)->post(route('connector.ics.store'), [
            'calendar' => UploadedFile::fake()->createWithContent('facebook.ics', $calendar),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('ics-import.imported', 1)
            ->assertSessionHas('ics-import.skipped', 0);

        $this->assertDatabaseHas('events', [
            'title' => '400 ans d’histoire, Toulon fête sa Marine',
            'description' => "Spectacle son et lumière\nDeuxième ligne",
            'location' => 'Fort Lamalgue, Toulon',
            'organizer' => 'Festival de Musique Toulon et région',
            'source_url' => 'https://www.facebook.com/events/1245674667674500/',
            'facebook_event_id' => '1245674667674500',
            'source_type' => 'facebook',
            'status' => 'draft',
            'user_id' => $user->id,
        ]);
    }

    public function test_reimporting_the_same_ics_event_skips_the_duplicate(): void
    {
        $user = User::factory()->create();
        $calendar = <<<'ICS'
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:event-42@example.com
DTSTART;TZID=Europe/Paris:20260919T203000
SUMMARY:Concert local
LOCATION:Toulon
END:VEVENT
END:VCALENDAR
ICS;

        foreach (range(1, 2) as $attempt) {
            $response = $this->actingAs($user)->post(route('connector.ics.store'), [
                'calendar' => UploadedFile::fake()->createWithContent("calendar-{$attempt}.ics", $calendar),
            ]);
        }

        $response->assertSessionHas('ics-import.imported', 0)
            ->assertSessionHas('ics-import.skipped', 1);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('events', [
            'title' => 'Concert local',
            'source_type' => 'manual',
            'status' => 'draft',
        ]);
    }

    public function test_invalid_ics_file_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('connector.ics.store'), [
                'calendar' => UploadedFile::fake()->createWithContent('invalid.ics', 'not a calendar'),
            ])
            ->assertSessionHasErrors('calendar');

        $this->assertDatabaseCount('events', 0);
    }
}
