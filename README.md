# STRack

STRack is a full-stack vehicle management platform for tracking fuel usage, maintenance, repairs, modifications, and recurring ownership costs across multiple cars.

## Highlights

- Secure auth flow (Laravel Sanctum)
- Multi-vehicle garage management
- Fuel logs with consumption/cost analytics
- Repairs and modifications history
- Insurance/inspection expiry tracking and reminders
- Recurring costs tracking
- Dashboard summary for operational insights

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vue 3, Vuetify 3, Pinia, Vue Router, Vite |
| Backend | Laravel 11 (PHP 8.2+), Sanctum |
| Database | MySQL 8.4 (Docker) or SQLite |
| Tooling | ESLint, Prettier, Vitest, Laravel Pint, PHPUnit |

## Repository Structure

```text
STrack/
├── backend/          # Laravel API
├── frontend/         # Vue client app
└── docker-compose.yml
```

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+ and npm
- Docker (optional, recommended for MySQL/phpMyAdmin)

## Local Development

### 1. Start infrastructure (optional)

```bash
docker compose up -d mysql phpmyadmin
```

- MySQL: `127.0.0.1:3306`
- phpMyAdmin: `http://localhost:8081`

### 2. Configure and run backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

API base URL: `http://localhost:8000/api`

### 3. Configure and run frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend URL: `http://localhost:5173`

## API Overview

### Authentication

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me` (auth required)
- `POST /api/auth/logout` (auth required)

### Profile

- `PUT /api/profile`
- `PUT /api/profile/password`
- `DELETE /api/profile`

### Dashboard

- `GET /api/dashboard/summary`

### Domain entities

- Cars: `GET/POST /api/cars`, `GET/PUT/DELETE /api/cars/{car}`
- Fuel logs: `GET/POST /api/cars/{car}/fuel-logs`, `PUT/DELETE /api/fuel-logs/{fuelLog}`
- Repairs: `GET/POST /api/cars/{car}/repairs`, `PUT/DELETE /api/repairs/{repair}`
- Modifications: `GET/POST /api/cars/{car}/mods`, `PUT/DELETE /api/mods/{mod}`
- Recurring costs: `GET/POST /api/cars/{car}/recurring-costs`, `PUT/DELETE /api/recurring-costs/{recurringCost}`

## Scripts

### Frontend

```bash
cd frontend
npm run dev
npm run lint
npm run test
npm run build
```

### Backend

```bash
cd backend
./vendor/bin/pint --test
php artisan test
```

## Deployment Notes

- Set production env values in `backend/.env` (`APP_ENV=production`, DB, CORS, etc.)
- Build frontend assets with `npm run build`
- Cache Laravel config/routes where applicable (`php artisan config:cache`, `php artisan route:cache`)

## License

This project is licensed under the MIT License.
