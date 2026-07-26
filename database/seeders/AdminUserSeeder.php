<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $this->command?->warn('ADMIN_PASSWORD absent : aucun compte administrateur créé.');

            return;
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@feedevent.fr')],
            [
                'name' => env('ADMIN_NAME', 'Admin Feedevent'),
                'password' => Hash::make($password),
                'role' => 'admin',
            ],
        );
    }
}
