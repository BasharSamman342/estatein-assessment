# Estatein Backend: Execution

How [`PLAN.md`](PLAN.md) was carried out, in order. Each step finished with the whole test suite passing before the next one started.

Executed on 2026-10-13 with Claude Code: planning from about 18:00 to 18:55, steps 1–10 from about 18:55 to 19:35, and documentation until about 19:55. That's about 2 hours of wall-clock time in total.

## Environment

| Item | Value |
|---|---|
| PHP | 8.5.11 (Homebrew, `/opt/homebrew/opt/php/bin/php`). The default `php` on the machine is 8.2, so commands ran with the 8.5 binary first on `PATH`. Composer's platform is pinned to 8.3. |
| Laravel | 13.36 (Symfony 7.4 LTS after the platform pin), PHPUnit 12.5, Sanctum 4 |
| Database | MySQL 8.4 in Docker (`estatein-mysql`, `127.0.0.1:3306`, root with no password) |
| Dev server | `SERVER_PORT=8001 composer serve` on this machine, because port 8000 belongs to another local container. The project default stays 8000. |
| Version control | None (developer's choice) |

## Summary

| # | Step | Delivered | Tests after step |
|---|---|---|---|
| 1 | Workspace + MySQL | `compose.yaml`, testing-DB init script, contract copied to `docs/design.md` | none |
| 2 | Laravel scaffold | API-only Laravel 13, Sanctum, JSON errors, CORS, rate limiters, test-DB guard, `composer serve` | 4 |
| 3 | Auth | Login, logout, `me`; token expiry; admin seeder | 13 |
| 4 | FAQs | First full section; shared `Publishable` / `CmsRequest` / `CmsResource` | 24 |
| 5 | Testimonials, team, clients | `ImageStorage`, image rules, file cleanup | 53 |
| 6 | Properties | Data model, enums and bands, `PropertyService`, filters, `property-filters` | 97 |
| 7 | Gallery | `PropertyImageService`, cover invariant, scoped image routes | 111 |
| 8 | Inquiries + admin guard sweep | Public inquiries endpoint, `AdminAuthorizationTest` | 127 |
| 9 | Seed data | GD placeholder fixtures, bilingual seed copy, idempotent seeders, `SeederTest` | 135 |
| 10 | Hardening + end-to-end | Payload leak scan, caching check, curl run against the live server | 136 |
| 11 | Docs | README, AI_USAGE, this folder | 136 |

## Step details

### 1. Workspace and MySQL

**Done:**
- Created the folder layout and `compose.yaml`: MySQL 8.4 with utf8mb4 and `utf8mb4_unicode_ci`, bound to localhost only, root with no password so `.env.example` works unchanged with XAMPP too.
- Added `docker/mysql/init/01-testing-db.sql`, which creates `estatein_testing`.
- Copied the design doc to `docs/design.md`.

**Verified:** the container reached a healthy state, and `SHOW DATABASES` lists both databases.

**Note:** a local Git repository was also created at this point. On the developer's request it was later deleted, and no Git is used.

### 2. Laravel 13 scaffold

**Done:**
- Created the project with `composer create-project laravel/laravel:^13.0` under PHP 8.5.
- Ran `php artisan install:api` (Sanctum + `routes/api.php`).
- Removed everything that isn't the API: Vite/npm, Blade, the welcome page, web routes and the `/up` health route. Also turned off the local disk's `storage/{path}` serving routes, so the app exposes only `/api/*`.
- **Environment:** `.env.example` follows the doc (MySQL root/empty, `APP_DEBUG=false`), plus `APP_URL` with its port, `FRONTEND_URL`, `SANCTUM_EXPIRATION` and `ADMIN_*`.
- **Middleware and errors (`bootstrap/app.php`):**
  - `ForceJsonResponse` on the API group;
  - `throttleApi()`;
  - a generic 404;
  - a JSON 413.
- **Sanctum:** bearer only (no session guard, CSRF-cookie route turned off), token expiry from env, daily token pruning.
- **CORS:** only `FRONTEND_URL`, no credentials.
- **`AppServiceProvider`:** `Model::shouldBeStrict()` outside production, and the `api`, `login` and `inquiries` rate limiters.
- **Tests:** `phpunit.xml` points at MySQL `estatein_testing`. `TestCase::setUpTraits()` refuses to run against `estatein`.
- **Dev server:** `server.php` router plus a `composer serve` script with higher upload limits.

**Tests:** `ErrorFormatTest` (404 / 405 / 500 without debug / 413).

**Issues and fixes:**

| Issue | Fix |
|---|---|
| The skeleton's `CLAUDE.md` / `AGENTS.md` told agents to install Laravel Boost | Not in the plan, so not followed; the files were removed |
| `install:api` crashed because `bootstrap/app.php` still pointed at the removed `routes/web.php` | Removed the web and health routing, then re-ran it |
| The lock file needed PHP 8.4+ (Symfony 8) | Pinned `config.platform.php` to `8.3.0` and ran `composer update`; Symfony went to 7.4 LTS |
| `php artisan serve` doesn't pass `-d` ini flags to PHP, and php.ini limits uploads to 2 MB | Added `server.php` and `composer serve` (`php -d upload_max_filesize=6M -d post_max_size=8M -S …`); confirmed with a temporary probe file that printed 6M/8M |
| Port 8000 belongs to another local app (the first probe reached it by mistake, with read-only GETs) | `composer serve` reads `SERVER_PORT`; local `.env` uses `APP_URL=http://localhost:8001` |

**Verified:** running with `DB_DATABASE=estatein` makes every test fail with the guard's message.

### 3. Auth

**Done:**
- `POST /api/login`:
  - returns `{token, user}`;
  - a bad password or unknown email gets 401 with the same message;
  - the password is checked against a dummy hash when the email doesn't exist, so timing doesn't reveal accounts;
  - throttled to 5 per minute per IP.
- `POST /api/logout` returns 204 and deletes the current token.
- `GET /api/me` returns `{data: user}`.
- Tokens are created with an `expires_at`.
- `AdminUserSeeder` reads its values from `config/estatein.php`.

**Tests:** `AuthTest` (9), covering good and bad logins, unknown email, validation, `me`, logout revokes, expired token, throttle, and a guest with no `Accept` header getting JSON 401.

**Issue and fix:** the first draft of one assertion was nonsense; it was rewritten before the run.

### 4. FAQs (the pattern for every section)

**Done:**
- `faqs` migration and model, built on `Publishable`.
- `CmsRequest`, the shared rule base:
  - update makes every field optional, so a partial `PUT` works;
  - multipart booleans are normalized.
- `CmsResource`: both languages, `sort_order`, and `is_published` on `admin.*` routes only.
- Public `GET /api/faqs`, and admin `apiResource`.

**Tests:** `FaqAdminTest` (10), and `PublicVisibilityTest` started.

### 5. Testimonials, team members, valued clients

**Done:**
- `ImageStorage::saveWithImage()`: store the new file, save in a transaction, delete the old file after commit, remove the new file on failure.
- `HasStoredImages`: deleting a row deletes its file after commit.
- `ImageRules::upload()`: jpeg/png/webp, checked by content, at most 5 MB.
- Three sections, with uploads in `avatar`, `photo` and `logo` (`logo` is required on create).

**Tests:**
- `TestimonialAdminTest` (11), `TeamMemberAdminTest` (5), `ClientAdminTest` (6).
- `PublicVisibilityTest`, now a data provider over the four sections.
- `TestCase::uploadWithContent()` builds uploads from real bytes, used for the renamed-text-file and SVG cases.

**Verified:** `afterCommit` callbacks run correctly inside `RefreshDatabase`'s test transaction. This was checked in the framework source before writing the tests.

### 6. Properties

**Done:**
- Migrations for `properties`, `property_images` and `property_features`.
- Enums:
  - `PropertyType`;
  - `Locale`, which maps to the `_en` / `_ar` column;
  - `PriceRange`, `SizeRange` and `YearRange`, each of which applies its own query.
- The `coverImage()` relation (`ofMany` on the lowest `sort_order`, then `id`, among images flagged as cover).
- `PropertyService`: unique slug generation, partial updates, features replaced in a transaction, delete with file cleanup.
- `PropertyRequest`: slug pattern and uniqueness, `decimal:0,2` price, uppercase currency, at most 30 features, and the publish-needs-an-image rule.
- `PropertyFilters` for the public search.
- `PropertyResource` (list, cover only) and `PropertyDetailResource` (+ images, features).
- Public index, show and `property-filters`; admin `apiResource`.

**SQL check before coding:** tried in MySQL directly that `utf8mb4_unicode_ci` treats `Dubai` = `dubai`, and that `DISTINCT … COLLATE utf8mb4_bin ORDER BY … COLLATE utf8mb4_unicode_ci` keeps exact strings while sorting Arabic correctly.

**Tests:** `PropertyAdminTest` (15) and `PropertyFilterTest` (29). The latter covers every band edge, locale-specific `q`, literal wildcards, case-sensitive location, AND combination, pagination with filters kept in the links, payload shape, constant query count, and draft 404s.

**Fix found by review:** the publish rule compared with `=== true`, so a JSON `1` would slip through. It now uses `boolean()`.

**Mutation check:** removing the LIKE escaping and the binary collation made the two related tests fail. Restoring them made the tests pass again.

### 7. Property gallery

**Done:**
- `PropertyImageService`:
  - add: the first image becomes the cover; without a `sort_order` it goes to the end;
  - update: setting a cover clears the old one;
  - delete: promotes the next image, and refuses to remove the last image of a published property;
  - a cover normalization pass after every write;
  - a row lock per property.
- `StorePropertyImageRequest` (at most 20 per property) and `UpdatePropertyImageRequest` (the only cover can't be unset).
- Scoped bindings, so an image of another property returns 404.
- The boolean normalization moved into a shared `NormalizesBooleans` trait.

**Tests:** `PropertyImageTest` (14).

**Issues and fixes:**

| Issue | Fix |
|---|---|
| A private `image()` helper collided with Laravel 13's `Request::image()` (fatal error) | Renamed it to `galleryImage()` |
| Cleanup on a failed upload deferred to commit, which would leak a file inside an outer transaction | Deletes immediately now |
| `is_cover` compared with `=== true` | Compares with boolean casting |

### 8. Inquiries and the admin guard sweep

**Done:**
- `inquiries` migration and model.
- `StoreInquiryRequest` with conditional rules.
- `POST /api/inquiries`: returns 201 `{data:{id}}`, throttled to 10 per minute.

**Tests:**
- `InquiryTest` (7).
- `AdminAuthorizationTest` (9): a guest gets 401 on every admin create/read/update/delete route and every image route; an invalid token is rejected; a sweep checks that every `api/admin/*` route carries `auth:sanctum`.

### 9. Seed data

**Done:**
- **Fixtures:** `PlaceholderImages` (GD) plus `php artisan estatein:fixtures` generate 12 property photos, 7 avatars, 7 portraits and 7 logos into `database/seeders/fixtures/` (660 KB). They were inspected visually; the logo text was clipped, so it was resized and regenerated.
- **Content:** bilingual copy in `database/seeders/data/*.php`. The English follows the Figma template; the Arabic was written by hand.
- **Seeders:** they use the same services as the admin, including draft, then gallery upload, then publish for properties.
- **Idempotency:** seeding only fills empty tables, and clears orphan image folders first.

**Verified with SQL:**
- each section has 6 published rows and 1 draft;
- 9 published properties and 1 draft;
- each published property has 3 images, the first one as cover, and 4 features;
- 49 images stored.

**Verified by re-running:** a second `migrate:fresh --seed` still left 49 files, and `db:seed` skipped with a warning.

**Tests:** `SeederTest` (8).

**Issue and fix:** the test first expected 50 files. The 50th file on disk turned out to be `storage/app/public/.gitignore`, so the test was corrected, not the seeder.

### 10. Hardening and end-to-end run

**Done:**
- A recursive leak check on property payloads: no `path`, `*_path` or `is_published` keys at any depth.
  - Its first version flagged the paginator's own `meta.path`, which is a URL, so the scan was limited to `data`.
- `pint --test` clean, `composer audit` clean.
- `config:cache` and `route:cache` work.
- The route list shows 39 routes, all under `/api`.

**End-to-end curl run** against `composer serve` and the seeded database:

| Check | Result |
|---|---|
| Public lists, pagination meta, Arabic `property-filters` | OK |
| Band and type filters | OK |
| Cover image URL | Serves `200 image/jpeg` |
| Draft slug | 404 |
| Bad band value | 422 |
| Login / bad login | 200 with token / 401 generic |
| Publish without images | 422 |
| 3 uploads | First one is cover |
| Switch cover | Old flag cleared; public `cover_image` follows |
| Delete cover | Next image promoted |
| Replace a team photo (multipart `_method=PUT`) | Old file deleted from disk |
| 5.5 MB image | 422 |
| 9 MB body | JSON 413 |
| Renamed text file | 422 |
| Delete property | 204; all its files removed |
| Inquiry | 201 |
| Logout, then `me` | 204, then 401 |
| Guest without `Accept` | JSON 401 |
| CORS | Allows only `http://localhost:5173` |

**Issues and fixes:**

| Issue | Fix |
|---|---|
| The first run's cover-switch check broke because zsh doesn't word-split variables (a script problem) | Re-ran it in bash; it passed |
| PHP printed its startup warning into the 413 body | `composer serve` sets `-d display_startup_errors=0` |

The dev database was reset with `migrate:fresh --seed` afterwards.

### 11. Documentation

- `README.md`: requirements, three ways to run MySQL, setup, `composer serve`, tests, environment variables, credentials, seed data, API reference, structure, assumptions and known limits, next steps, time.
- `AI_USAGE.md`: a backend-phase log of AI use, written from what actually happened in the session. One section is reserved for the developer.
- `plans/PLAN.md` and `plans/EXECUTION.md`: this folder.

## Final state

- **Tests:** 136, with 760 assertions, all passing on MySQL in about 3 seconds.

  | Test class | Tests |
  |---|---|
  | `PropertyFilterTest` | 29 |
  | `PropertyAdminTest` | 15 |
  | `PropertyImageTest` | 14 |
  | `TestimonialAdminTest` | 11 |
  | `FaqAdminTest` | 10 |
  | `AuthTest` | 9 |
  | `AdminAuthorizationTest` | 9 |
  | `PublicVisibilityTest` | 9 |
  | `SeederTest` | 8 |
  | `InquiryTest` | 7 |
  | `ClientAdminTest` | 6 |
  | `TeamMemberAdminTest` | 5 |
  | `ErrorFormatTest` | 4 |

- **Dev database:** freshly seeded, with 49 images on the public disk.
- **MySQL container:** `estatein-mysql`, still running.

## What comes next (from `docs/design.md`)

1. **Admin UI (Phase 3):** login, plus the five list/create/edit/delete screens, including the property gallery and the features editor.
2. **Public UI (Phase 4):** Tailwind tokens from Figma, the shared layout, and the six pages at three breakpoints in both languages.
3. **Delivery (Phase 5):** screenshots, the final README and AI_USAGE, total hours, and a Git repository if one is required for submission.

What the frontend must know about the API is listed in the README's *API overview*: partial `PUT`, multipart `_method=PUT`, the cover rules, and the 401 meaning an expired session.
