<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_validation_messages_are_localized(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'adresse-invalide',
            'password' => 'court',
            'password_confirmation' => 'court',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'email' => 'Le champ adresse e-mail doit être une adresse e-mail valide.',
            'password' => 'Le champ mot de passe doit comporter au moins 8 caractères.',
        ]);
    }
}
