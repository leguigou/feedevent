<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        $events = [
            ['title' => 'Nuit électro sous la verrière', 'description' => 'Une nuit immersive entre lives électroniques, scénographie lumineuse et DJ sets. Bar et restauration sur place.', 'days' => 2, 'hour' => 20, 'location' => 'Le Carreau du Temple, Paris', 'lat' => 48.8647, 'lng' => 2.3621, 'category' => 'concert', 'price' => 24, 'image' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Couleurs libres — exposition contemporaine', 'description' => 'Une sélection de jeunes artistes explore la couleur, le mouvement et les nouveaux récits urbains.', 'days' => 4, 'hour' => 11, 'location' => 'Galerie du Marais, Paris', 'lat' => 48.8590, 'lng' => 2.3624, 'category' => 'exposition', 'price' => 0, 'image' => 'https://images.unsplash.com/photo-1549490349-8643362247b5?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Brunch des chefs engagés', 'description' => 'Un grand brunch locavore imaginé par quatre chefs, accompagné d’un marché de producteurs franciliens.', 'days' => 6, 'hour' => 12, 'location' => 'La REcyclerie, Paris', 'lat' => 48.8976, 'lng' => 2.3431, 'category' => 'gastronomie', 'price' => 32, 'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Le Misanthrope, version pop', 'description' => 'Molière rencontre la pop culture dans une mise en scène vive, drôle et résolument actuelle.', 'days' => 8, 'hour' => 19, 'location' => 'Théâtre de Belleville, Paris', 'lat' => 48.8715, 'lng' => 2.3791, 'category' => 'theatre', 'price' => 18, 'image' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Marché des créateurs du canal', 'description' => 'Illustration, céramique, mode responsable et objets singuliers réunis au bord de l’eau.', 'days' => 9, 'hour' => 10, 'location' => 'Canal Saint-Martin, Paris', 'lat' => 48.8720, 'lng' => 2.3641, 'category' => 'marche', 'price' => 0, 'image' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Atelier céramique & apéro', 'description' => 'Façonne ta première pièce avec une céramiste, dans une ambiance détendue. Matériel et cuisson inclus.', 'days' => 11, 'hour' => 18, 'location' => 'Atelier Voltaire, Paris', 'lat' => 48.8578, 'lng' => 2.3803, 'category' => 'atelier', 'price' => 39, 'image' => 'https://images.unsplash.com/photo-1452626038306-9aae5e071dd3?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Cinéma en plein air : les classiques', 'description' => 'Une projection sous les étoiles avec transats, food trucks et vue sur les quais.', 'days' => 13, 'hour' => 21, 'location' => 'Parc de la Villette, Paris', 'lat' => 48.8938, 'lng' => 2.3906, 'category' => 'cinema', 'price' => 0, 'image' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=85'],
            ['title' => 'Mini-festival des familles curieuses', 'description' => 'Spectacles, expériences scientifiques et ateliers créatifs pour petits et grands.', 'days' => 15, 'hour' => 10, 'location' => 'Cité des sciences, Paris', 'lat' => 48.8956, 'lng' => 2.3874, 'category' => 'famille', 'price' => 12, 'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1200&q=85'],
        ];

        foreach ($events as $data) {
            Event::updateOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'date_start' => now()->addDays($data['days'])->setTime($data['hour'], 0),
                    'date_end' => now()->addDays($data['days'])->setTime($data['hour'] + 2, 0),
                    'location' => $data['location'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'category_id' => $categories[$data['category']] ?? null,
                    'price' => $data['price'],
                    'image_url' => $data['image'],
                    'organizer' => 'Collectif Feedevent',
                    'status' => 'published',
                ],
            );
        }
    }
}
