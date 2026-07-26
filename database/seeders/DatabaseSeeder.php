<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        Category::insert([
            ['name' => 'Concert', 'slug' => 'concert', 'color' => '#f43f5e', 'icon' => '🎵'],
            ['name' => 'Festival', 'slug' => 'festival', 'color' => '#8b5cf6', 'icon' => '🎪'],
            ['name' => 'Théâtre', 'slug' => 'theatre', 'color' => '#f59e0b', 'icon' => '🎭'],
            ['name' => 'Cinéma', 'slug' => 'cinema', 'color' => '#06b6d4', 'icon' => '🎬'],
            ['name' => 'Sport', 'slug' => 'sport', 'color' => '#22c55e', 'icon' => '⚽'],
            ['name' => 'Exposition', 'slug' => 'exposition', 'color' => '#ec4899', 'icon' => '🖼️'],
            ['name' => 'Atelier', 'slug' => 'atelier', 'color' => '#14b8a6', 'icon' => '🔧'],
            ['name' => 'Conférence', 'slug' => 'conference', 'color' => '#6366f1', 'icon' => '📢'],
            ['name' => 'Soirée', 'slug' => 'soiree', 'color' => '#e11d48', 'icon' => '🍸'],
            ['name' => 'Marché', 'slug' => 'marche', 'color' => '#84cc16', 'icon' => '🛍️'],
            ['name' => 'Gastronomie', 'slug' => 'gastronomie', 'color' => '#f97316', 'icon' => '🍽️'],
            ['name' => 'Famille', 'slug' => 'famille', 'color' => '#a855f7', 'icon' => '👨‍👩‍👧‍👦'],
            ['name' => 'Autre', 'slug' => 'autre', 'color' => '#6b7280', 'icon' => '📌'],
        ]);
    }
}
