<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('feedevent.admin.password');

        if (! $password) {
            $this->command?->warn('ADMIN_PASSWORD absent : aucun compte administrateur créé.');

            return;
        }

        User::updateOrCreate(
            ['email' => config('feedevent.admin.email')],
            [
                'name' => config('feedevent.admin.name'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ],
        );
    }
}
