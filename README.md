# Feedevent 🎉

Agenda intelligent — découvre les événements près de chez toi et laisse l'algo apprendre tes goûts.

## Stack

- **Laravel 13** + MySQL 8.4 (Docker)
- **Breeze** (auth Blade + Alpine)
- **Tailwind CSS** + Vite
- **FullCalendar.io** — calendrier interactif
- **Leaflet + OpenStreetMap** — carte des événements

## Quick start

```bash
cp .env.example .env
composer install
npm install && npm run build

# Démarrer MySQL (port 3307)
docker compose up -d

# Migrer + seed
php artisan migrate:fresh --seed

# Lancer le serveur
php artisan serve --host=0.0.0.0 --port=8001
```

Accès : http://localhost:8001

## Comptes

| Rôle | Email | Password |
|---|---|---|
| Admin | admin@feedevent.fr | admin123 |

## Fonctionnalités (en cours)

- [ ] Feed d'événements (liste + carte)
- [ ] Calendrier FullCalendar
- [ ] Like / Dislike → recommandations
- [ ] Ajout par lien URL (LLM parse la page)
- [ ] Ajout par affiche / flyer (OCR + Vision LLM)
- [ ] Sync Facebook Events
- [ ] Catégories et tags
- [ ] Filtres (distance, date, catégorie)
- [ ] Comptes utilisateurs

## Modèles

- **Event** — titre, description, dates, lieu (lat/lng), catégorie, image, source, tags, métadonnées LLM
- **Category** — nom, slug, couleur, icône
- **UserPreference** — like/dislike lié à un event
- **event_user** — événements sauvegardés (favoris)

## Structure API

| Méthode | Route | Description |
|---|---|---|
| GET | /api/events | Liste des événements |
| GET | /api/events/{id} | Détail d'un événement |
| POST | /api/events | Ajouter un événement |
| POST | /api/events/{id}/like | Like |
| POST | /api/events/{id}/dislike | Dislike |
| GET | /api/recommendations | Recommandations perso |

## Déploiement

Projet prévu pour Dokploy (comme les autres projets).
