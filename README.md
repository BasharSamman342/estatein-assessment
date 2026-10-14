# Estatein — Bilingual Real-Estate Website

Estatein is a bilingual English/Arabic real-estate website with a public Vue 3 SPA and a Laravel REST API backed by MySQL. The public site includes Home, About, Services, Properties, Property Detail and Contact pages. The admin dashboard manages testimonials, FAQs, team members, valued clients, properties and property galleries.

## Technology

- **Backend:** PHP 8.3+, Laravel 13, Sanctum bearer-token authentication, MySQL 8.
- **Frontend:** Node.js 22.18+ (or 24.12+), Vue 3, Vite, Pinia, Vue Router, vue-i18n and Tailwind CSS.
- **Tests:** PHPUnit feature tests, Vitest unit tests and Playwright end-to-end specs.
- **Languages:** English and Arabic, including LTR/RTL layout support.

## Repository layout

```text
backend/                 Laravel API
frontend/                Vue SPA, unit tests and Playwright specs
docs/design.md           API/data contract and implementation notes
docs/screenshots/        Captured public-site screenshots
compose.yaml             Local MySQL + API Docker Compose setup
USAGE_AI.md              AI tooling, authorship and verification disclosure
```

## Requirements

Install PHP 8.3+ with the `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `ctype`, and `curl` extensions; Composer 2; Node.js 22.18+ or 24.12+ with npm; and Docker Compose v2 or a local MySQL 8 server.

## Setup and run

### 1. Configure the backend

From the repository root:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Check the database values in `backend/.env`. The defaults expect MySQL at `127.0.0.1:3306`, database `estatein`, username `root`, and an empty password. For Docker Compose, first return to the repository root and start the database as described below.

### 2. Start MySQL

From the repository root:

```bash
docker compose up -d mysql
```

The MySQL service is bound to localhost. It creates `estatein` and `estatein_testing`. If you use an existing MySQL 8 server instead, create both databases with `utf8mb4` and update `DB_*` values in `backend/.env`.

### 3. Migrate and seed

```bash
cd backend
php artisan migrate --seed
php artisan storage:link
```

The seeders create the local admin account and bilingual demo content. Seed images are generated placeholders, not exported Figma photography.

### 4. Run the API

In `backend/`:

```bash
composer serve
```

The API is available at `http://localhost:8000`. If using another port, update `APP_URL` in `backend/.env` to match it. The API returns absolute image URLs based on `APP_URL`.

### 5. Configure and run the frontend

In a second terminal, from the repository root:

```bash
cd frontend
npm ci
cp .env.example .env.local
npm run dev
```

Vite normally serves the SPA at `http://localhost:5173`. The example environment enables mock data for frontend-only review. To connect to the Laravel API, set `VITE_USE_MOCK_DATA=false` and `VITE_API_URL=http://localhost:8000/api` in `frontend/.env.local`. Keep `.env.local` out of version control.

### Admin login

- **Email:** `admin@estatein.test`
- **Password:** `password`

These are development-only seed credentials. Override `ADMIN_EMAIL` and `ADMIN_PASSWORD` before seeding any shared or deployed environment.

## Tests and quality checks

Run each command from the directory shown.

### Backend (requires MySQL and Composer dependencies)

```bash
cd backend
php artisan test
```

The PHPUnit suite is configured to use the separate `estatein_testing` database. It resets that database during tests; do not point it at a database containing data you need to keep.

### Frontend unit tests

```bash
cd frontend
npm ci
npm run test:run
```

### Frontend production build

```bash
cd frontend
npm run build
```

### Frontend lint

```bash
cd frontend
npm run lint
```

### Browser end-to-end tests

Start MySQL, the backend API, and the frontend first. Seed the backend and make sure the frontend is configured to use the API rather than mock data. Then run:

```bash
cd frontend
npx playwright install chromium
npm run test:e2e
```

Playwright specs exercise public pages and admin flows against the running application. Login is throttled to five attempts per minute per IP, so repeated E2E logins may hit the rate limit; allow the throttle window to reset before rerunning if a test reports `Too Many Attempts`.

## Screenshots

Public UI screenshots are in [`docs/screenshots/`](docs/screenshots/):

- English and Arabic desktop Home
- English and Arabic mobile Home
- English and Arabic desktop Properties
- English desktop Property Detail

The planned admin dashboard and property-editor screenshots have not been captured yet.

## Limitations and known trade-offs

- Seed property/team/client images are generated placeholders rather than photos exported from Figma.
- Figma was not used directly during implementation; visual fidelity should be reviewed against the original design before final evaluation.
- There is no dedicated tablet design frame; intermediate widths reflow the desktop layout.
- The Comprehensive Pricing Details block is omitted, as allowed by the brief.
- The admin interface is English-only, as allowed by the brief.
- Contact and property inquiries are stored in the database, but there is no admin screen to review them.
- Admin screenshots are not yet included.

## Design and API contract

See [`docs/design.md`](docs/design.md) for the data model, endpoint contract, validation rules, seeded content assumptions and UI notes.
