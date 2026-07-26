# Feedevent

Ton radar de sorties locales : découvre les événements près de chez toi et garde tes favoris sous la main.

## Stack

- Laravel 13, Blade et Alpine.js
- Tailwind CSS et Vite
- FullCalendar pour l’agenda
- Leaflet et MarkerCluster pour la carte
- MySQL en production, SQLite possible en local

## Installation

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Pour charger les événements de démonstration, conserve `SEED_DEMO_EVENTS=true`.

## Compte administrateur

Le seeder ne crée plus de compte avec un mot de passe public. Renseigne avant la migration :

```dotenv
ADMIN_NAME="Admin Feedevent"
ADMIN_EMAIL=admin@feedevent.fr
ADMIN_PASSWORD=un-mot-de-passe-long-et-unique
```

Sans `ADMIN_PASSWORD`, aucun administrateur n’est créé.

## Fonctionnalités

- feed éditorial responsive et filtres rapides ;
- recherche, catégories, date, gratuité, distance et géolocalisation ;
- fiches événement avec partage, itinéraire, favoris et export `.ics` ;
- carte avec marqueurs regroupés, liste et recherche dans la zone ;
- calendrier mensuel, hebdomadaire et liste mobile ;
- favoris persistants et préférences utilisateur ;
- contributions placées en brouillon avant modération ;
- administration des événements, catégories, utilisateurs et journaux ;
- mode sombre, navigation mobile et accessibilité clavier ;
- métadonnées Open Graph et Schema.org Event.

## Vérification

```bash
php artisan test
npm run build
```
