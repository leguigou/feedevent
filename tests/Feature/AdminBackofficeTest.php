<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SettingManager;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBackofficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_and_api_require_an_admin_account(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->getJson('/api/admin/settings')->assertForbidden();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->getJson('/api/admin/settings')->assertOk();
    }

    public function test_secrets_are_encrypted_at_rest_and_never_returned_by_the_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->putJson('/api/admin/settings', [
            'settings' => [
                'llm.api_key' => 'secret-test-key',
                'llm.model' => 'test/model',
            ],
        ]);

        $response->assertOk();

        $apiKey = collect($response->json('groups.LLM'))
            ->firstWhere('key', 'llm.api_key');

        $this->assertSame('', $apiKey['value']);
        $this->assertTrue($apiKey['configured']);
        $this->assertSame('backoffice', $apiKey['source']);

        $rawValue = DB::table('site_settings')->where('key', 'llm.api_key')->value('value');

        $this->assertNotSame('secret-test-key', $rawValue);
        $this->assertSame('secret-test-key', decrypt($rawValue));
        $this->assertSame('secret-test-key', app(SettingManager::class)->get('llm.api_key'));
    }

    public function test_admin_can_manage_users_and_read_their_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$user->id}", [
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('is_active', false)
            ->assertJsonStructure(['events_count', 'preferences_count', 'saved_events_count']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonStructure([
                'total_users',
                'active_users',
                'new_users_30d',
                'saved_events',
                'llm_events',
                'facebook_events',
            ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_suspend_demote_or_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$admin->id}", ['is_active' => false])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$admin->id}", ['role' => 'user'])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertUnprocessable();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_setting_model_decrypts_values_transparently(): void
    {
        $setting = SiteSetting::create([
            'key' => 'facebook.app_secret',
            'value' => 'facebook-secret',
        ]);

        $this->assertSame('facebook-secret', $setting->fresh()->value);
    }

    public function test_admin_account_is_bootstrapped_from_environment_configuration(): void
    {
        config([
            'feedevent.admin.name' => 'Administratrice',
            'feedevent.admin.email' => 'admin@example.test',
            'feedevent.admin.password' => 'a-long-environment-password',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame('Administratrice', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('a-long-environment-password', $admin->password));
    }
}
