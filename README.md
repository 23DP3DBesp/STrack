# STRack

STRack - Your personal car fleet tracker. Track fuel efficiency, maintenance, repairs, and modifications across multiple vehicles from one dashboard.

## Stack

- **Backend**: Laravel 11 + Sanctum API
- **Frontend**: Vue 3 + Vuetify 3 + Pinia + Vue Router
- **Database**: MySQL 8 / SQLite
- **Optional**: phpMyAdmin (via Docker)

## Core Features

- 👥 User authentication & profiles
- 🚗 Multi-car garage management (add/edit cars)
- 📅 Insurance & inspection expiry tracking with smart reminders
- ⛽ Fuel logging & analytics (MPG, consumption trends, cost tracking)
- 🔧 Repair & service history
- ⚙️ Modifications & upgrades inventory
- 📊 Dashboard with interactive charts (mileage, efficiency, costs)
- 🛠️ Smart maintenance reminders (inspection, insurance renewal)
- 📱 Responsive design for mobile/desktop

## Project Layout

```
STrack/
├─ backend/
│  ├─ app/
│  │  ├─ Http/Controllers/Api/
│  │  │  ├─ AuthController.php
│  │  │  ├─ CarController.php
│  │  │  ├─ DashboardController.php
│  │  │  ├─ FuelLogController.php
│  │  │  ├─ ModController.php
│  │  │  ├─ ProfileController.php
│  │  │  └─ RepairController.php
│  │  ├─ Models/
│  │  │  ├─ Car.php
│  │  │  ├─ FuelLog.php
│  │  │  ├─ Mod.php
│  │  │  ├─ Repair.php
│  │  │  └─ User.php
│  │  └─ Concerns/ResolvesOwnedCar.php
│  ├─ database/migrations/ (cars, fuel_logs, repairs, mods)
│  ├─ routes/api.php
│  ├─ artisan
│  └─ composer.json
├─ frontend/
│  ├─ src/
│  │  ├─ pages/
│  │  │  ├─ DashboardPage.vue
│  │  │  ├─ dashboard/
│  │  │  │  ├─ components/
│  │  │  │  │  ├─ DashboardOverviewSection.vue
│  │  │  │  │  ├─ DashboardGarageSection.vue
│  │  │  │  │  └─ ExpiryCard.vue
│  │  │  │  ├─ charts.js
│  │  │  │  ├─ expiryHelper.js
│  │  │  │  ├─ formatters.js
│  │  │  │  ├─ forms.js
│  │  │  │  └─ reminders.js
│  │  │  ├─ ProfilePage.vue
│  │  │  ├─ HomePage.vue
│  │  │  └─ auth/LoginPage.vue + RegisterPage.vue
│  │  ├─ layouts/MainLayout.vue
│  │  ├─ stores/auth.js + garage.js
│  │  ├─ components/ChartWrapper.vue + DateInput.vue
│  │  ├─ api/client.js
│  │  └─ main.js + App.vue
│  ├─ package.json
│  └─ vite.config.js
└─ docker-compose.yml
```

## Quick Start (Local Development)

### 1. Start Database
```bash
docker compose up -d mysql phpmyadmin
```
- MySQL: `127.0.0.1:3306`
- phpMyAdmin: `http://localhost:8081`

### 2. Backend Setup
```bash
cd backend
cp .env.example .env
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Frontend Setup (new terminal)
```bash
cd frontend
npm install
npm run dev
```
Frontend: `http://localhost:5173`

### 4. Create Test User (Tinker)
```bash
cd backend
php artisan tinker
```
```php
User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('password')]);
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register user |
| POST | `/api/auth/login` | Login user |
| POST | `/api/auth/logout` | Logout |
| GET | `/api/dashboard` | Dashboard stats/charts |
| GET/POST/PUT/DELETE | `/api/cars` `/api/cars/{id}` | Manage cars |
| GET/POST | `/api/fuel-logs` `/api/cars/{car}/fuel-logs` | Fuel entries |
| GET/POST | `/api/repairs` `/api/cars/{car}/repairs` | Repairs |
| GET/POST | `/api/mods` `/api/cars/{car}/mods` | Modifications |
| GET/PUT | `/api/profile` | User profile |

## Quality Checks

**Backend**:
```bash
cd backend
./vendor/bin/pint --test
php artisan test
```

**Frontend**:
```bash
cd frontend
npm run lint
npm run test:unit
npm run build
```

## Production Notes
- Set `APP_ENV=production` & generate key
- Use HTTPS & configure CORS
- Queue workers: `php artisan queue:work`
- Optimize: `php artisan config:cache`, `npm run build`

🚀 Ready to track your fleet!

