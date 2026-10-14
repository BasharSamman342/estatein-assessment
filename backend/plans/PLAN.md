# Estatein Backend: Plan

This is the plan for the first phase of the Estatein assessment: the **Laravel REST API**. It records what we set out to build, the decisions behind it, and the rules the code follows. [`EXECUTION.md`](EXECUTION.md) describes how it was carried out.

## 1. Goal

The assessment brief asks for a full-stack real-estate site from a Figma design:

- Laravel + Vue 3.
- English and Arabic, with LTR/RTL.
- A public site, and an admin dashboard that manages Testimonials, FAQs, Team, Valued Clients and Properties (with a multi-image gallery).

This phase delivers **only the backend**: every API the public site and the admin dashboard need, with seed data, tests and documentation. The Vue frontend is built next, against the same contract.

What a successful backend looks like:

- **Contract:** a frontend built from the design doc works against it without changes.
- **Visibility:** drafts never reach the public API.
- **Validation:** every input is validated on the server, uploads included.
- **Files:** image files never get out of sync with the database.
- **Tests:** they cover the behaviour whose failure would cause real problems.

## 2. Inputs

| Document | Role |
|---|---|
| `estatein-fullstack-assessment-en.md` + the Arabic PDF | The assessment brief: scope, requirements, evaluation weights |
| [`docs/design.md`](../docs/design.md) | **The binding contract:** data model, routes, payload keys, status codes, seed data, test list. Chosen by the developer during planning (see §3). |

## 3. How the plan was decided

1. **First draft.** After reading the brief, a first backend plan was drafted. It proposed:
   - JSON translation columns (spatie/laravel-translatable);
   - localized responses chosen by `Accept-Language`;
   - an `/api/v1` prefix;
   - admin and editor roles.
2. **Clarifying questions.** The developer answered them: bearer tokens, MySQL in Docker, auto-generated API docs as an extra.
3. **Independent review.** A second agent checked the draft against the brief and Laravel 13 specifics. That review surfaced a second document, `docs/design.md`, which locked several decisions differently.
4. **Contract chosen.** Asked which document should win, the developer chose **the design doc as the contract**. The plan was rewritten around it. The review's Laravel-specific findings were kept (§9).

Final decisions:

| Topic | Decision | Source |
|---|---|---|
| Framework | Laravel 13 (PHP ≥ 8.3), API only, no Blade | Design doc |
| Bilingual content | Paired `*_en` / `*_ar` columns; every response returns both languages, so switching language never refetches | Design doc |
| Auth | Sanctum **bearer tokens**, one admin role; `auth:sanctum` is the admin gate | Design doc + developer |
| API base | `/api`, Laravel's `{data}` / `{data, links, meta}` shapes, 422 `{message, errors}` | Design doc |
| Database | **MySQL 8 in Docker** (`compose.yaml`, MySQL only; the app runs on the host) instead of XAMPP | Developer |
| Tests | PHPUnit feature tests on MySQL `estatein_testing` | Design doc |
| Seed images | Placeholder images generated with GD (no Figma export) | Developer |
| Version control | None in this folder; plain source files only | Developer |
| API docs generator | Dropped when the design doc became the contract | Developer |

## 4. Scope

**In scope:**
- Auth: login, logout and `me`.
- Public read endpoints for the five sections.
- The property search filters, plus the locations list for the filter dropdown.
- A public inquiries endpoint.
- Admin create, view, edit and delete for the five sections.
- Property gallery management.
- Migrations, seeders and fixtures.
- Feature tests.
- The README and AI usage documentation.

**Out of scope for this phase:**
- The Vue SPA.
- Screenshots.
- Roles beyond a single admin.
- Registration and password reset.
- An inquiries admin screen.
- Image re-encoding and thumbnails.
- Drag-and-drop ordering: `sort_order` is a plain number field.

## 5. Architecture

```
Vue SPA (:5173, later)
   │  JSON over HTTP, Authorization: Bearer <token> for /api/admin/*
   ▼
Laravel 13 API (:8000)
   ├─ routes/api.php           public, auth, admin route groups
   ├─ Form Requests            all validation (the browser is never trusted)
   ├─ Controllers              thin; Public/* read-only, Admin/* CRUD + gallery
   ├─ Services                 ImageStorage, PropertyService, PropertyImageService
   ├─ API Resources            explicit output fields; is_published only on admin routes
   └─ Eloquent models          Publishable / HasStoredImages concerns
   ▼
MySQL 8 (estatein; estatein_testing for tests)
storage/app/public  ← uploaded images, served at /storage via storage:link
```

The pieces the five sections share are written once and reused:

- **Publishable** (model trait): `published()` and `ordered()` scopes, with defaults that mirror the column defaults.
- **CmsRequest** (Form Request base):
  - one rule set serves both create and update; on update every field becomes optional, so a partial `PUT` only touches what it sends;
  - multipart boolean strings are normalized.
- **CmsResource** (API Resource base): adds `sort_order`, and `is_published` on admin routes only.
- **ImageStorage** (service): stores uploads and keeps them in step with database commits (§7.2).

## 6. Data model

Every CMS table has `sort_order` (unsigned int, default 0) and `is_published` (boolean, default false), with an index on `(is_published, sort_order)`. Every list is ordered by `sort_order`, then `id`.

| Table | Columns |
|---|---|
| `users` | Standard Laravel columns; Sanctum `personal_access_tokens` |
| `testimonials` | `quote_en/ar` (text), `author_name_en/ar` (120), `role_en/ar` (nullable), `rating` (nullable, 1–5), `avatar_path` (nullable) |
| `faqs` | `question_en/ar` (500), `answer_en/ar` (text) |
| `team_members` | `name_en/ar`, `role_en/ar` (120), `bio_en/ar` (nullable text), `photo_path` (nullable) |
| `clients` | `name_en/ar` (120, doubles as the logo's alt text), `logo_path` |
| `properties` | See below |
| `property_images` | `property_id` FK cascade, `path`, `alt_en/ar` (nullable), `is_cover`, `sort_order` |
| `property_features` | `property_id` FK cascade, `label_en/ar` (120), `sort_order` |
| `inquiries` | `type` (`contact` or `property`), `property_id` (nullable FK, null on delete), `name`, `email`, `phone` (nullable), `message` |

`properties` columns:

- `slug`: unique, max 160 characters, kebab-case.
- `title_en/ar`, `description_en/ar`, `location_en/ar`.
- `price`: decimal(12,2), returned as the string `"550000.00"`.
- `currency`: char(3), default `USD`, stored uppercase.
- `bedrooms`, `bathrooms`, `area_sqft`, `year_built`.
- `property_type`: `villa`, `apartment` or `commercial`.
- Indexes on type, price and both location columns.

## 7. Rules the code follows

### 7.1 Validation and contract rules

- Every write goes through a Form Request. Models only receive `validated()` data and declare explicit `$fillable`.
- **Property slug:** optional on create, and built with `Str::slug(title_en)` plus `-2`, `-3`, … on collision. Once set, it stays the same when the title changes.
- **Features:** at most 30 per property. The array replaces the existing rows in a transaction, but only when the key is sent.
- **Publishing a property** requires at least one image; otherwise 422.
- **Inquiries:** `property_id` is required, and must be a *published* property, when `type=property`. It is prohibited when `type=contact`.
- **Property search:**
  - `locale` picks which columns the search uses;
  - `q` is a case-insensitive substring, with `%` and `_` escaped;
  - `location` is an exact, case-sensitive match (`utf8mb4_bin`);
  - type, price, size and year use fixed bands, the middle band inclusive;
  - an unknown value returns 422.

### 7.2 Images

- **Accepted files:** JPEG, PNG or WebP, at most 5 MB, checked by *content* (`mimes` and `mimetypes` both use finfo). SVG, AVIF, HEIC and renamed files are rejected.
- **Storage:** the `public` disk, in per-section folders, under random names whose extension comes from the detected content type. URLs in responses are absolute, built from `APP_URL`; paths are never returned.
- **File lifecycle:**
  - a new file is stored first, and removed again if the database write fails;
  - a replaced or deleted file is removed only **after commit**;
  - deleting a property collects its image paths first, because the database cascade fires no model events.
- **Cover invariant:** whenever a property has images, exactly one of them is the cover.
  - The first upload becomes the cover.
  - Setting a new cover clears the old one in the same transaction.
  - Deleting the cover promotes the next image by `(sort_order, id)`.
  - If several covers are ever stored, readers use the lowest `(sort_order, id)`, and the next write clears the rest.
  - Clearing the only cover returns 422.
  - The last image of a published property can't be deleted.
  - A property can hold at most 20 images.
  - Gallery writes take a row lock on the property.

### 7.3 Errors and security

- **Errors:** all `/api/*` responses are JSON, whatever `Accept` header is sent.
  - 404 carries a generic message; Laravel's default names the model class.
  - A body over the PHP limit returns a JSON 413.
  - 500 returns only `{"message":"Server Error"}` when `APP_DEBUG=false`.
- **Login:** a bad login returns 401 with one generic message, whether or not the email exists. The time taken is the same in both cases, so it doesn't reveal whether the account exists. Login is throttled to 5 per minute per IP.
- **Tokens:** expire after `SANCTUM_EXPIRATION` minutes (8h); expired tokens are pruned daily.
- **Origins:** CORS allows only `FRONTEND_URL`. There are no cookies, so no CSRF surface.
- **Throttles:** inquiries 10 per minute per IP, the whole API 120 per minute per IP.
- **Secrets:** none in source. `.env` is excluded; the seeded admin comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD`, defaulting to the doc's sample credentials.
- **Plain text:** CMS content is plain text. The frontend renders it with interpolation, never `v-html`.

### 7.4 Performance

- `Model::shouldBeStrict()` outside production, so lazy loading (N+1) throws in dev and in tests.
- List endpoints eager-load only what they return: the cover image for lists; images and features for a detail.
- The cover is a single `ofMany` subquery.
- Only the property list is paginated (default 9, max 50). The other public lists and the admin lists are small and unpaginated, as the doc specifies.

## 8. Testing strategy

- **Engine:** PHPUnit feature tests against **MySQL**, so collation and JSON behaviour match production. They run in `estatein_testing`, and `TestCase` refuses to start if the configured database is `estatein`.
- **Uploads:** upload tests build files from real bytes. `UploadedFile::fake()` reports a MIME type based on the file name, which would let a "renamed text file" test pass for the wrong reason.
- **Coverage, per the doc's test list plus additions:**
  - auth and throttling;
  - guest access to every admin route (plus a sweep checking each one has `auth:sanctum`);
  - create, update and delete for each section, with validation;
  - files cleaned up on replace and delete;
  - draft visibility, and no leaks in public payloads;
  - every filter and band edge;
  - the gallery invariants;
  - inquiries;
  - error shapes;
  - seed-data coverage;
  - a constant query count on the property list.

## 9. Risks identified up front

| Risk | Mitigation |
|---|---|
| The machine's default PHP is 8.2; Composer would quietly install Laravel 12 | Use the installed PHP 8.5 explicitly; pin Composer's platform to PHP 8.3 so the lock file still installs on the doc's target |
| php.ini caps uploads at 2 MB, below the 5 MB rule | `composer serve` starts the server with higher limits; oversized bodies get a JSON 413 |
| `utf8mb4_unicode_ci` makes `=` and `DISTINCT` case-insensitive | Use `utf8mb4_bin` for the exact location match and the locations list |
| Tests wiping development data | A dedicated test database, plus the `TestCase` guard |
| Multipart `PUT` isn't parsed by PHP, and booleans arrive as strings | `POST` with `_method=PUT` (documented); booleans normalized in requests |
| A list-screen publish toggle that sends only `{is_published}` could wipe fields | Partial-update semantics on `PUT` |

## 10. Deliverables of this phase

- `backend/`: the API, migrations, seeders, placeholder fixtures and 136 feature tests.
- `compose.yaml` and `docker/mysql/init/`: local MySQL with both databases.
- `docs/design.md`: the contract.
- `README.md`: setup, environment variables, API reference, assumptions and limits.
- `AI_USAGE.md`.
- This folder.
