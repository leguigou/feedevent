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

## Fonctionnalités

- feed éditorial responsive et filtres rapides ;
- recherche, catégories, date, gratuité, distance et géolocalisation ;
- fiches événement avec partage, itinéraire, favoris et export `.ics` ;
- carte avec marqueurs regroupés, liste et recherche dans la zone ;
- calendrier mensuel, hebdomadaire et liste mobile ;
- favoris persistants et préférences utilisateur ;
- contributions placées en brouillon avant modération ;
- connecteur Chrome téléchargeable pour importer la page ouverte comme brouillon ;
- administration des événements, catégories, utilisateurs et journaux ;
- mode sombre, navigation mobile et accessibilité clavier ;
- métadonnées Open Graph et Schema.org Event.

## Vérification

```bash
php artisan test
npm run build
```

## Accès au back-office

Le compte administrateur initial est défini dans `.env` :

```dotenv
ADMIN_NAME="Admin Feedevent"
ADMIN_EMAIL=admin@feedevent.fr
ADMIN_PASSWORD="un-mot-de-passe-long-et-unique"
```

Créez ou synchronisez ce compte, puis connectez-vous normalement avant d’ouvrir `/admin` :

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

L’onglet **Paramètres** permet ensuite de gérer la configuration du site, du provider LLM et de l’application Facebook. Les secrets enregistrés depuis le back-office sont chiffrés en base avec `APP_KEY`, masqués dans l’API et prioritaires sur les valeurs de `.env`.

Les identifiants Facebook globaux peuvent être préparés ici. La connexion OAuth des comptes Facebook personnels nécessite toutefois l’intégration Meta et son App Review.

## Connecteur Chrome

Une fois connecté, ouvre `/connector` puis clique sur **Télécharger le connecteur**. FeedEvent génère un ZIP personnalisé contenant un jeton d’import limité, haché en base, révocable et valable 180 jours.

Après décompression :

1. ouvre `chrome://extensions` ;
2. active le mode développeur ;
3. clique sur **Charger l’extension non empaquetée** ;
4. sélectionne le dossier `feedevent-connector`.

L’extension utilise uniquement les permissions Chrome `activeTab` et `scripting`. Elle n’analyse que la page ouverte après un clic et crée toujours un brouillon soumis à validation.
