# STRack

STRack ir pilna cikla (full-stack) transportlīdzekļu pārvaldības platforma: degvielas uzskaites, apkopes termiņu, remontu, modifikāciju un regulāro izmaksu kontrolei vairākiem auto vienlaicīgi.

## Satura rādītājs

- Par projektu
- Galvenās iespējas
- Arhitektūra
- Tehnoloģiju steks
- Repozitorija struktūra
- Prasības videi
- Ātrais starts
- Vides konfigurācija
- API pārskats
- Autentifikācija un drošība
- Skripti un komandas
- Koda kvalitāte un testēšana
- Build un izvietošana
- Diagnostika un problēmu novēršana
- Roadmap
- Contributing
- Licence

## Par projektu

STRack palīdz strukturēti pārvaldīt auto ekspluatāciju un atbildēt uz praktiskiem jautājumiem:

- Cik izmaksā katra auto uzturēšana mēnesī un gadā.
- Kā laika gaitā mainās degvielas patēriņš.
- Kad beidzas OCTA/KASKO, tehniskā apskate vai citi svarīgi termiņi.
- Kādi remonti un uzlabojumi veikti, kad un par kādu summu.

Projekts sastāv no divām daļām:

- backend: Laravel API ar biznesa loģiku un datu slāni.
- frontend: Vue 3 SPA ar lietotāja saskarni un analītiku.

## Galvenās iespējas

- Droša autentifikācija ar Laravel Sanctum.
- Vairāku automobiļu pārvaldība vienā kontā.
- Degvielas uzskaite ar patēriņa un izmaksu analītiku.
- Remontu vēsture un darbu uzskaite.
- Modifikāciju/uzlabojumu uzskaite.
- Regulāro izmaksu uzskaite (apdrošināšana, nodokļi, abonementi u.c.).
- Kritisko termiņu kontrole un atgādinājumu loģika.
- Dashboard kopsavilkums ar ekspluatācijas rādītājiem.
- Profila datu un paroles pārvaldība.
- E-pasta verifikācijas plūsma.

## Arhitektūra

### Augsta līmeņa plūsma

1. Pārlūkā darbojas Vue SPA.
2. Frontend piekļūst Laravel API pa HTTP.
3. API autorizē pieprasījumus ar Sanctum tokeniem.
4. Backend glabā un nolasa datus no MySQL (vai SQLite lokālai izstrādei).

### Backend (Laravel)

- Maršruti: backend/routes/api.php
- Kontrolieri: backend/app/Http/Controllers/Api
- Validācija: backend/app/Http/Requests
- Modeļi: backend/app/Models
- Migrācijas un sējumi: backend/database/migrations, backend/database/seeders

Galvenie domēna modeļi:

- User
- Car
- FuelLog
- Repair
- Mod
- RecurringCost

### Frontend (Vue)

- Maršrutēšana: frontend/src/router
- Stāvokļa pārvaldība: frontend/src/stores (Pinia)
- API slānis: frontend/src/api (Axios)
- Komponentes un lapas: frontend/src/components, frontend/src/pages
- Lokalizācija: frontend/src/i18n
- UI bibliotēka: Vuetify 3

## Tehnoloģiju steks

| Slānis | Tehnoloģijas |
|---|---|
| Frontend | Vue 3, Vuetify 3, Pinia, Vue Router, Vite, Axios, Chart.js |
| Backend | Laravel 11, PHP 8.2+, Laravel Sanctum |
| Datubāze | MySQL 8.4 (Docker) vai SQLite |
| Testēšana | Vitest (frontend), PHPUnit (backend) |
| Koda kvalitāte | ESLint, Prettier, Laravel Pint |
| Lokālā infrastruktūra | Docker Compose, phpMyAdmin |

## Repozitorija struktūra

```text
STrack/
├── backend/                     # Laravel API
│   ├── app/                     # Biznesa loģika, modeļi, kontrolieri
│   ├── config/                  # Laravel konfigurācija
│   ├── database/                # Migrācijas, fabrikas, sējumi
│   ├── routes/                  # API un web maršruti
│   ├── tests/                   # Backend testi
│   └── artisan                  # Laravel CLI
├── frontend/                    # Vue SPA
│   ├── src/
│   │   ├── api/                 # HTTP klienti un API loģika
│   │   ├── components/          # UI komponentes
│   │   ├── pages/               # Lapas
│   │   ├── router/              # Maršrutēšana
│   │   ├── stores/              # Pinia store
│   │   ├── i18n/                # Lokalizācija
│   │   └── styles/              # Stili
│   ├── package.json
│   └── vite.config.js
├── docker-compose.yml           # MySQL + phpMyAdmin lokālai videi
└── README.md
```

## Prasības videi

- PHP 8.2+
- Composer 2+
- Node.js 20+
- npm 10+
- Docker un Docker Compose (nav obligāti, bet ieteicami)

## Ātrais starts

### 1. Repozitorija klonēšana

```bash
git clone <your-repo-url>
cd STrack
```

### 2. Infrastruktūras palaišana (nav obligāta)

```bash
docker compose up -d mysql phpmyadmin
```

Servisi pēc palaišanas:

- MySQL: 127.0.0.1:3306
- phpMyAdmin: http://localhost:8081

### 3. Backend palaišana

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

API bāzes adrese: http://localhost:8000/api

### 4. Frontend palaišana

Atsevišķā terminālī:

```bash
cd frontend
npm install
npm run dev
```

Frontend adrese: http://localhost:5173

## Vides konfigurācija

### Backend .env (galvenie parametri)

```env
APP_NAME=STRack
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=STRack
DB_USERNAME=STRack
DB_PASSWORD=STRack

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
SESSION_DOMAIN=localhost
FRONTEND_URL=http://localhost:5173
```

Ja izmanto SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/backend/database/database.sqlite
```

SQLite inicializācija:

```bash
cd backend
touch database/database.sqlite
php artisan migrate --seed
```

### Frontend .env (ieteicams)

Izveido frontend/.env failu:

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

## API pārskats

Bāzes prefikss: /api

### Auth

- POST /auth/register
- POST /auth/login
- POST /auth/email/verification-notification
- GET /auth/email/verify/{id}/{hash}
- GET /auth/me (nepieciešama autentifikācija)
- POST /auth/logout (nepieciešama autentifikācija)

### Profile

- PUT /profile
- PUT /profile/password
- DELETE /profile

### Dashboard

- GET /dashboard/summary

### Cars

- GET /cars
- POST /cars
- GET /cars/{car}
- PUT /cars/{car}
- DELETE /cars/{car}

### Fuel logs

- GET /cars/{car}/fuel-logs
- POST /cars/{car}/fuel-logs
- PUT /fuel-logs/{fuelLog}
- DELETE /fuel-logs/{fuelLog}

### Repairs

- GET /cars/{car}/repairs
- POST /cars/{car}/repairs
- PUT /repairs/{repair}
- DELETE /repairs/{repair}

### Mods

- GET /cars/{car}/mods
- POST /cars/{car}/mods
- PUT /mods/{mod}
- DELETE /mods/{mod}

### Recurring costs

- GET /cars/{car}/recurring-costs
- POST /cars/{car}/recurring-costs
- PUT /recurring-costs/{recurringCost}
- DELETE /recurring-costs/{recurringCost}

### Autorizēta pieprasījuma piemērs

```bash
curl -X GET http://localhost:8000/api/cars \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/json"
```

## Autentifikācija un drošība

- Autentifikācija balstīta uz Laravel Sanctum tokeniem.
- Login/verification endpointiem izmantots pieprasījumu ierobežojums (throttle).
- Atbalstīta e-pasta verifikācijas plūsma.
- Production vidē rekomendēti stingri CORS iestatījumi.
- Production vidē obligāti jāizmanto HTTPS.

## Skripti un komandas

### Frontend

```bash
cd frontend
npm run dev
npm run lint
npm run format
npm run format:write
npm run test
npm run build
npm run preview
```

### Backend

```bash
cd backend
php artisan serve
php artisan migrate --seed
./vendor/bin/pint --test
./vendor/bin/pint
php artisan test
php artisan route:list
```

### Docker

```bash
docker compose up -d mysql phpmyadmin
docker compose ps
docker compose logs -f mysql
docker compose down
```

## Koda kvalitāte un testēšana

Ieteicamais minimālais pipeline pirms merge:

```bash
# Frontend
cd frontend
npm ci
npm run lint
npm run test
npm run build

# Backend
cd ../backend
composer install --no-interaction --prefer-dist
./vendor/bin/pint --test
php artisan test
```

Ieteikumi:

- Jauniem API endpointiem pievienot feature testus.
- Nodrošināt vienotu stilu ar ESLint, Prettier un Pint.
- Testēt gan veiksmīgo scenāriju, gan validācijas kļūdas.

## Build un izvietošana

### Production kontrolsaraksts

1. Sakonfigurēt backend/.env production režīmam:
   - APP_ENV=production
   - APP_DEBUG=false
   - korekti DB, CORS un MAIL parametri
2. Uzbūvēt frontend:

```bash
cd frontend
npm ci
npm run build
```

3. Optimizēt backend:

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

4. Sakonfigurēt Nginx/Apache un HTTPS.
5. Ieviest logu monitoringu un rotāciju.

## Diagnostika un problēmu novēršana

### Backend nestartējas

- Pārbaudi PHP versiju (vajag 8.2+).
- Pārbaudi, vai eksistē backend/.env un izpildīts php artisan key:generate.
- Pārbaudi DB piekļuvi un MySQL konteinera stāvokli.

### CORS kļūdas pārlūkā

- Pārbaudi backend config/cors.php un FRONTEND_URL vērtību.
- Pārliecinies, ka frontend izmanto pareizu API adresi.

### Frontend nevar sasniegt API

- Pārbaudi VITE_API_BASE_URL (ja tiek izmantots).
- Pārliecinies, ka backend klausās uz http://localhost:8000.

### Migrāciju problēmas

```bash
cd backend
php artisan migrate:status
php artisan migrate --seed
```

## Roadmap

- Paplašināta izmaksu analītika pa periodiem un kategorijām.
- Atskaites eksportam CSV/PDF formātā.
- Atgādinājumi e-pastā un ziņapmaiņas platformās.
- Vairāku valūtu atbalsts.
- Lomu un piekļuves tiesību modelis (RBAC).
- CI/CD plūsmas ar GitHub Actions.

## Contributing

1. Izveido feature branch no main.
2. Veic izmaiņas un pievieno testus.
3. Lokāli palaid lint un testus.
4. Atver Pull Request ar skaidru aprakstu.

Ieteicamie commit prefiksi:

- feat: jauna funkcionalitāte
- fix: kļūdas labojums
- refactor: pārstrukturēšana bez funkcionalitātes maiņas
- test: testu papildināšana/uzlabojumi
- docs: dokumentācijas atjauninājumi

## Licence

Projekts tiek izplatīts ar MIT licenci.