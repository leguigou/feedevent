<?php

namespace Tests\Feature;

use App\Models\ConnectorToken;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_a_personalized_extension(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/connector/download');

        $response->assertOk()
            ->assertDownload('feedevent-connecteur-chrome.zip');

        $tokenRecord = ConnectorToken::firstOrFail();
        $this->assertSame($user->id, $tokenRecord->user_id);
        $this->assertSame(64, strlen($tokenRecord->token_hash));
        $this->assertTrue($tokenRecord->expires_at->isFuture());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));

        $manifest = json_decode($zip->getFromName('feedevent-connector/manifest.json'), true);
        $configuration = $zip->getFromName('feedevent-connector/config.js');

        $this->assertSame(3, $manifest['manifest_version']);
        $this->assertContains('http://localhost/*', $manifest['host_permissions']);
        $this->assertSame(['activeTab', 'scripting'], $manifest['permissions']);
        $this->assertArrayNotHasKey('content_scripts', $manifest);
        $this->assertStringContainsString('/api/connector/events', $configuration);
        $this->assertMatchesRegularExpression('/"token":"[^"]{80}"/', $configuration);
        $this->assertNotFalse($zip->getFromName('feedevent-connector/extractor.js'));
        $zip->close();
    }

    public function test_valid_connector_token_creates_a_draft_event(): void
    {
        $user = User::factory()->create();
        $plainToken = 'connector-token-'.str_repeat('a', 64);
        ConnectorToken::create([
            'user_id' => $user->id,
            'name' => 'Chrome',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMonth(),
        ]);

        $this->withToken($plainToken)
            ->postJson('/api/connector/events', [
                'title' => 'Concert importé',
                'description' => 'Une soirée musicale.',
                'date_start' => now()->addWeek()->toIso8601String(),
                'source_url' => 'https://www.facebook.com/events/123456789/?acontext='.str_repeat('x', 400),
                'location' => 'Nantes',
                'latitude' => 47.218371,
                'longitude' => -1.553621,
            ])
            ->assertCreated()
            ->assertJsonPath('event.status', 'draft');

        $this->assertDatabaseHas('events', [
            'title' => 'Concert importé',
            'status' => 'draft',
            'source_type' => 'facebook',
            'facebook_event_id' => '123456789',
            'source_url' => 'https://www.facebook.com/events/123456789/',
            'user_id' => $user->id,
            'latitude' => 47.218371,
            'longitude' => -1.553621,
        ]);
    }

    public function test_invalid_expired_or_revoked_connector_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $plainToken = 'connector-token-'.str_repeat('b', 64);
        ConnectorToken::create([
            'user_id' => $user->id,
            'name' => 'Chrome',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->subMinute(),
        ]);

        $payload = [
            'title' => 'Événement',
            'date_start' => now()->addDay()->toIso8601String(),
            'source_url' => 'https://example.com/event',
        ];

        $this->withToken('invalid-'.str_repeat('x', 64))
            ->postJson('/api/connector/events', $payload)
            ->assertUnauthorized();

        $this->withToken($plainToken)
            ->postJson('/api/connector/events', $payload)
            ->assertUnauthorized();
    }

    public function test_duplicate_source_is_not_imported_twice(): void
    {
        $user = User::factory()->create();
        $plainToken = 'connector-token-'.str_repeat('c', 64);
        ConnectorToken::create([
            'user_id' => $user->id,
            'name' => 'Chrome',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMonth(),
        ]);
        Event::create([
            'title' => 'Existant',
            'date_start' => now()->addDay(),
            'source_url' => 'https://example.com/event/42',
            'status' => 'draft',
        ]);

        $this->withToken($plainToken)
            ->postJson('/api/connector/events', [
                'title' => 'Doublon',
                'date_start' => now()->addDay()->toIso8601String(),
                'source_url' => 'https://example.com/event/42',
            ])
            ->assertConflict();

        $this->assertSame(1, Event::withTrashed()->count());
    }

    public function test_user_can_only_revoke_their_own_connector(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = ConnectorToken::create([
            'user_id' => $user->id,
            'name' => 'Chrome',
            'token_hash' => hash('sha256', 'token'),
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($otherUser)
            ->delete("/connector/tokens/{$token->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->delete("/connector/tokens/{$token->id}")
            ->assertRedirect();

        $this->assertNotNull($token->fresh()->revoked_at);
    }
}
