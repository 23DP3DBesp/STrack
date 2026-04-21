# STRack

STRack is a secure document storage platform with role-based access, document sharing, version history, and backup automation.

## Stack

- Backend: Laravel 11 + Sanctum
- Frontend: Vue 3 + Vuetify 3 + Pinia + Vue Router
- Database: MySQL 8
- Optional DB UI: phpMyAdmin

## Core Features

- Centralized document storage
- Role-based and per-document access control
- Version history and one-click restore
- Folder-based organization with quick filtering
- Audit log for document lifecycle and permission actions
- Inline editor for text-based documents with versioned saves
- Secure private file storage
- Scheduled backup with retention policy

## Project Layout

```text
STRack/
├─ backend/
│  ├─ app/
│  │  ├─ Console/
│  │  │  ├─ Commands/BackupDatabaseCommand.php
│  │  │  └─ Kernel.php
│  │  ├─ Http/
│  │  │  ├─ Controllers/Api/
│  │  │  │  ├─ AuthController.php
│  │  │  │  ├─ DocumentController.php
│  │  │  │  ├─ DocumentShareController.php
│  │  │  │  └─ DocumentVersionController.php
│  │  │  ├─ Middleware/EnsureDocumentPermission.php
│  │  │  └─ Requests/
│  │  ├─ Jobs/RunBackupJob.php
│  │  ├─ Models/
│  │  │  ├─ User.php
│  │  │  ├─ Document.php
│  │  │  ├─ DocumentVersion.php
│  │  │  └─ DocumentAccess.php
│  │  ├─ Policies/DocumentPolicy.php
│  │  ├─ Providers/
│  │  │  ├─ AppServiceProvider.php
│  │  │  └─ AuthServiceProvider.php
│  │  └─ Services/DocumentService.php
│  ├─ bootstrap/
│  │  ├─ app.php
│  │  └─ providers.php
│  ├─ config/
│  │  ├─ app.php
│  │  ├─ auth.php
│  │  ├─ cache.php
│  │  ├─ cors.php
│  │  ├─ database.php
│  │  ├─ STRack.php
│  │  ├─ filesystems.php
│  │  ├─ logging.php
│  │  ├─ queue.php
│  │  └─ session.php
│  ├─ database/migrations/
│  ├─ public/index.php
│  ├─ routes/
│  │  ├─ api.php
│  │  ├─ console.php
│  │  └─ web.php
│  ├─ .env.example
│  ├─ artisan
│  └─ composer.json
├─ frontend/
│  ├─ src/
│  │  ├─ api/client.js
│  │  ├─ components/
│  │  │  ├─ DocumentUploader.vue
│  │  │  └─ DocumentShareDialog.vue
│  │  ├─ layouts/MainLayout.vue
│  │  ├─ pages/
│  │  │  ├─ DashboardPage.vue
│  │  │  ├─ auth/
│  │  │  │  ├─ LoginPage.vue
│  │  │  │  └─ RegisterPage.vue
│  │  │  └─ documents/
│  │  │     ├─ DocumentsPage.vue
│  │  │     └─ DocumentDetailsPage.vue
│  │  ├─ plugins/vuetify.js
│  │  ├─ router/index.js
│  │  ├─ stores/
│  │  │  ├─ auth.js
│  │  │  └─ documents.js
│  │  ├─ styles/main.css
│  │  ├─ App.vue
│  │  └─ main.js
│  ├─ .env.example
│  ├─ index.html
│  ├─ package.json
│  └─ vite.config.js
└─ docker-compose.yml
```

## Run with Local PHP + Node

### 1. Start MySQL

```bash
docker compose up -d mysql phpmyadmin
```

- MySQL: `127.0.0.1:3306`
- phpMyAdmin: `http://localhost:8081`

### 2. Install backend dependencies

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 3. Create first admin user

Use Laravel Tinker:

```bash
php artisan tinker
```

Then:

```php
App\Models\User::create([
  'name' => 'Admin',
  'email' => 'admin@STRack.local',
  'password' => 'Admin12345',
  'role' => 'admin'
]);
```

### 4. Run backend

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 5. Install frontend dependencies

Open a new terminal:

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Frontend URL: `http://localhost:5173`

## Backup

Manual backup:

```bash
cd backend
php artisan STRack:backup
```

Scheduled backup:

```bash
php artisan schedule:work
```

Backups are stored in `backend/storage/app/backup` and old backups are auto-removed by retention policy.

Restore check (dry-run, latest backup):

```bash
cd backend
php artisan STRack:backup:restore --latest --dry-run
```

Restore from a specific SQL dump:

```bash
cd backend
php artisan STRack:backup:restore --file=/absolute/path/to/docbox_YYYYmmdd_HHMMSS.sql
```

## Quality Gates

Backend:

```bash
cd backend
vendor/bin/pint --test
php artisan test
```

Frontend:

```bash
cd frontend
npm install
npm run lint
npm run test
npm run build
```

CI (`.github/workflows/ci.yml`) runs lint + test + build on pull requests.

## API Docs

OpenAPI specification: `backend/openapi.yaml`

## API Endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/documents`
- `POST /api/documents`
- `GET /api/documents/{id}`
- `PUT /api/documents/{id}`
- `DELETE /api/documents/{id}`
- `GET /api/documents/{id}/download`
- `GET /api/documents/{id}/content`
- `PUT /api/documents/{id}/content`
- `GET /api/documents/{id}/versions`
- `POST /api/documents/{id}/versions/{versionId}/restore`
- `GET /api/documents/{id}/audit-logs`
- `GET /api/documents/{id}/shares`
- `POST /api/documents/{id}/shares`
- `PUT /api/documents/{id}/shares/{userId}`
- `DELETE /api/documents/{id}/shares/{userId}`
- `GET /api/folders`
- `POST /api/folders`
- `PUT /api/folders/{id}`
- `DELETE /api/folders/{id}`

## Production Hardening Checklist

- Configure HTTPS and secure headers
- Use S3-compatible private storage
- Enable antivirus scan for uploaded files
- Add audit logs for document events
- Move queue workers and scheduler to separate processes
- Configure offsite backup storage
- Add rate limits and brute-force protection
- Add automated tests and CI/CD pipeline
