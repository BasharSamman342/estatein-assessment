# Estatein design

Date: 2026-10-13

Source brief: `estatein-fullstack-assessment-en.md`

Figma file: `zcqSgygqsy7K6iY3oFT6iz`, page `45:2` (Design). Six screens, each drawn at desktop 1920, laptop 1440, and mobile 390.

This spec is the contract for one multiphase implementation plan. The public site, API, and admin share one database and ship as one repository, so they stay in one plan with five phases.

## Locked decisions

- One Git repository at the workspace root. `backend/` is a Laravel API. `frontend/` is a Vue 3 SPA that contains the public site and the admin dashboard.
- PHP 8.3 is installed on the host. Composer is already installed. Node 24 is already installed. Docker is available and is not used.
- Laravel 13 (current stable at the time of this spec; PHP 8.3 minimum). MySQL from XAMPP for local development and for PHPUnit. No Blade UI.
- Laravel Sanctum bearer tokens. The SPA stores the token in `localStorage`.
- CMS copy is stored as paired `*_en` and `*_ar` columns. The API returns both languages. Switching language does not refetch CMS data.
- Navigation, heroes, buttons, footer, services copy, and other static chrome live in Vue i18n JSON files.
- Tailwind CSS 4, with color, type, spacing, and radius tokens taken from the Figma file. The default Tailwind scale is not the visual source of truth.
- The admin UI is English and LTR. Arabic is not required in the admin.
- Automated tests are Laravel feature tests. This delivery has no Vitest suite.

## Architecture

```text
browser (Vue SPA, :5173)
  → HTTP JSON  VITE_API_URL=http://localhost:8000/api
  → Laravel 13 (:8000)
      → Form Requests, API Resources, Eloquent
      → MySQL from XAMPP (database estatein, charset utf8mb4)
      → files in storage/app/public, exposed by php artisan storage:link
```

MySQL is the server installed with XAMPP. The app does not install a second database server. Start MySQL from the XAMPP control panel before migrations, seeders, or tests.

`.env` and `.env.example` use:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=estatein`
- `DB_USERNAME=root`
- `DB_PASSWORD=` empty, which is the XAMPP default. Set the local root password here if one is configured.
- `DB_CHARSET=utf8mb4`
- `DB_COLLATION=utf8mb4_unicode_ci`

Create database `estatein` before the first migrate. Create database `estatein_testing` before the first test run. Both use `utf8mb4`.

`phpunit.xml` sets `DB_DATABASE=estatein_testing` and the same host, port, username, and password. The test case fails immediately if the connected database name is `estatein`, so `RefreshDatabase` cannot wipe development data.

`backend/` exposes only `/api/*`. `frontend/` is a Vite app with Vue Router, Pinia, vue-i18n, and Tailwind 4.

Public GET routes require no authentication. Admin routes require `Authorization: Bearer <token>` and the `auth:sanctum` middleware. There is one admin role. `auth:sanctum` is the only admin gate. There is no public registration and no role table.

A 401 from any admin call clears the token and sends the browser to `/admin/login` with a session-expired message.

Locale (`en` or `ar`) is stored in Pinia and in `localStorage` under `estatein.locale`. The default is `en`. The SPA sets `document.documentElement.lang` and `document.documentElement.dir` (`ltr` or `rtl`) and keeps both across refresh. The vue-i18n locale stays in sync with that value.

Image URLs in JSON are absolute URLs built from `APP_URL`, so a page on port 5173 can load a file served on port 8000.

Local development is two processes: `php artisan serve` and `npm run dev`.

One Git history exists at the workspace root. Creating the Laravel app must not leave a nested `.git` directory.

## Data model

MySQL tables: `users`, `testimonials`, `faqs`, `team_members`, `clients`, `properties`, `property_images`, `property_features`, `inquiries`.

Every CMS table has `sort_order` (integer, minimum 0, default 0) and `is_published` (boolean, default false). Public queries return rows where `is_published` is true, ordered by `sort_order` ascending, then `id` ascending. The client does not re-sort.

The API never returns a filesystem path. Image fields in JSON are public URLs or null.

### users

Standard Laravel user columns. The seeder creates one user:

- name: `Estatein Admin`
- email: `admin@estatein.test`
- password: `password` (hashed with `Hash::make`)

The README lists these sample credentials. `.env` is gitignored. `.env.example` is committed. `APP_DEBUG=false` in `.env.example`.

### testimonials

| Column | Rules |
|---|---|
| quote_en, quote_ar | required string, max 2000 |
| author_name_en, author_name_ar | required string, max 120 |
| role_en, role_ar | nullable string, max 120 |
| rating | nullable integer, 1 through 5 |
| avatar_path | nullable file, jpeg/png/webp, max 5 MB |

A null rating means the public card hides the stars. A missing avatar uses a neutral placeholder in the UI.

### faqs

| Column | Rules |
|---|---|
| question_en, question_ar | required string, max 500 |
| answer_en, answer_ar | required string, max 5000 |

### team_members

| Column | Rules |
|---|---|
| name_en, name_ar | required string, max 120 |
| role_en, role_ar | required string, max 120 |
| bio_en, bio_ar | nullable string, max 2000 |
| photo_path | nullable file, jpeg/png/webp, max 5 MB |

A missing photo uses a neutral placeholder.

### clients

Valued-client logos.

| Column | Rules |
|---|---|
| name_en, name_ar | required string, max 120, used as the image alt text |
| logo_path | required file on create, jpeg/png/webp, max 5 MB |

### properties

| Column | Rules |
|---|---|
| slug | required, unique, max 160, pattern `[a-z0-9]+(?:-[a-z0-9]+)*` |
| title_en, title_ar | required string, max 200 |
| description_en, description_ar | required string, max 10000 |
| price | required decimal(12,2), minimum 0. The API returns a string with two decimal places. |
| currency | required char(3), default `USD`, stored uppercase |
| location_en, location_ar | required string, max 200 |
| bedrooms, bathrooms, area_sqft | required integers, minimum 0 |
| year_built | required integer, 1800 through 2100 |
| property_type | required enum: `villa`, `apartment`, `commercial` |
| is_published, sort_order | same rules as the other CMS tables |

`bedrooms`, `bathrooms`, and `area_sqft` are required because the property card always shows those three figures. `year_built` is required because the properties search filters on it.

If the client omits `slug` on create, the server builds it with `Str::slug(title_en)`. A collision appends `-2`, then `-3`, increasing by one until the slug is unique. The admin may send a slug.

A property may be saved as a draft with zero images. Setting `is_published` to true when the property has zero images returns 422. The server enforces this. Unpublishing a property does not require an image.

### property_images

| Column | Rules |
|---|---|
| property_id | required foreign key, cascade on delete |
| path | required stored file |
| alt_en, alt_ar | nullable string, max 200 |
| is_cover | boolean |
| sort_order | integer, minimum 0 |

Allowed types are jpeg, png, and webp. Maximum size is 5 MB. Laravel validates the real MIME type, not the extension alone.

Exactly one image per property has `is_cover = true` when the property has at least one image. The first upload becomes the cover. A PATCH that sets `is_cover` true clears the flag on the sibling rows in the same transaction. If more than one cover is ever stored, the row with the lowest `sort_order`, then lowest `id`, is treated as the cover and the others are cleared on the next write.

Deleting an image deletes the file after the database row is removed. Deleting a property deletes its image files after the database delete commits. Replacing a team photo, testimonial avatar, or client logo deletes the previous file.

### property_features

| Column | Rules |
|---|---|
| property_id | required foreign key, cascade on delete |
| label_en, label_ar | required string, max 120 |
| sort_order | integer, minimum 0 |

A property accepts at most 30 features. Create and update send the full feature array. The server replaces the previous rows inside a transaction.

### inquiries

Stored from the contact form and the property inquiry form. There is no admin screen for this table.

| Column | Rules |
|---|---|
| type | required enum: `contact`, `property` |
| property_id | required when `type` is `property`; must reference a published property. Must be null when `type` is `contact`. |
| name | required string, max 120 |
| email | required email, max 255 |
| phone | nullable string, max 40 |
| message | required string, max 5000 |

### Seed data

Committed fixture images live in `backend/database/seeders/fixtures/`. They are exported from the Figma file (property photos, team portraits, client logos) so the pages can be reviewed offline. Decorative marketing art that is not CMS content (hero textures, icons, the contact gallery) is exported into `frontend/src/assets/` and is not stored in the database.

Seed counts:

- 6 published and 1 unpublished row for testimonials, faqs, team members, and clients.
- 9 published properties and 1 unpublished property.
- Those 9 include at least one of each `property_type`, at least one price in each price band, at least one `area_sqft` in each size band, at least one `year_built` in each year band, and at least two different `location_en` values.
- Each published property has 3 images (the first is the cover) and 4 key features.
- At least one published property uses an Arabic location string that also contains a Latin numeral and a price, so mixed-direction rendering can be checked by hand.

## Pages and components

### Public routes

All public routes use one layout: announcement bar, navigation, language switcher, and footer.

| Route | CMS data on the page | Static copy |
|---|---|---|
| `/` | 3 published properties (`per_page=3`), testimonials, faqs | hero, stats, and section chrome |
| `/about` | team members, clients | story, values, and other about chrome |
| `/properties` | published properties, 9 per page, plus the search controls | search labels |
| `/properties/:slug` | that property (gallery, description, counts, key features) and the same published FAQ list | inquiry form chrome |
| `/services` | none | the whole page |
| `/contact` | none | copy, office gallery from `src/assets`, contact form |

The property details page does not include the Comprehensive Pricing Details section from the Figma frame. `year_built` is editable in the admin and is returned by the API. The public card shows the fields drawn on the Figma card. The details page shows `year_built` only when that frame already has a year slot. The page does not invent an extra fact row.

The home hero buttons go to existing routes: Learn More goes to `/about`, Browse Properties goes to `/properties`. The three stats (200+, 10k+, 16+) are static i18n strings. The four hero cards link to `/services`.

The properties hero has a text field ("Search For A Property"), a Find Property button, and five dropdowns: Location, Property Type, Pricing Range, Property Size, and Build Year. Submitting the form sets the query parameters below and loads page 1. Dropdown labels are vue-i18n strings.

- Location options come from `GET /api/property-filters?locale=`. The first option means any location.
- Property Type options are any, `villa`, `apartment`, `commercial`.
- Pricing Range options are any, under $500,000, $500,000 to $1,000,000, and over $1,000,000.
- Property Size options are any, under 1,500 sq ft, 1,500 to 3,000 sq ft, and over 3,000 sq ft.
- Build Year options are any, before 2000, 2000 to 2015, and after 2015.

Breakpoints:

- 1440px and wider follow the desktop and laptop frames. The layout is fluid between 1440 and 1920.
- 768px through 1439px reflows the laptop layout. The Figma file has no tablet frame.
- Under 768px follows the 390px mobile frames.

### Shared Vue pieces

- `AppHeader`, `AppFooter`, `LanguageSwitcher`
- `PropertyCard`
- `TestimonialList`, `FaqList`, `TeamGrid`, `ClientLogos`
- `LoadingState`, `EmptyState`, `ErrorState`
- `FormField` (label, control, and a slot for the server error)

RTL uses Tailwind logical properties (`ms-*`, `me-*`, `ps-*`, `pe-*`, `text-start`, `text-end`). Directional icons flip under `[dir=rtl]`.

The Latin font family is the family named by the Figma text styles, loaded from a font host and copied into the Tailwind theme during phase 4. Arabic text uses IBM Plex Sans Arabic. Prices and other Latin fragments inside Arabic text are wrapped in `<bdi>`. Prices are formatted with `Intl.NumberFormat('en-US', { style: 'currency', currency })`, so digits stay Latin in both locales.

CMS text is rendered with mustache interpolation and `whitespace-pre-line`. The SPA does not use `v-html` for CMS content.

### Admin routes

English, LTR, functional layout. It does not have to match the Figma file.

| Route | Behavior |
|---|---|
| `/admin/login` | email and password. A visitor who already has a token is sent to `/admin`. |
| `/admin` | counts and links to the five CMS sections |
| `/admin/testimonials` | list, create, edit, delete |
| `/admin/faqs` | list, create, edit, delete |
| `/admin/team` | list, create, edit, delete |
| `/admin/clients` | list, create, edit, delete |
| `/admin/properties` | list, create, edit, delete, gallery, features |

Every admin route except login requires a token. The guard sends a missing token to `/admin/login`.

Each CMS form shows English and Arabic fields, `sort_order`, and `is_published`. The property form also edits `year_built`, uploads images, previews them, chooses the cover, deletes a single image, and edits the feature list as rows. Delete uses `window.confirm` before the request. The submit button is disabled while a request is in flight.

List screens include a control that toggles `is_published` through the update endpoint.

## API and data flow

Base path: `/api`. Success bodies use Laravel's `{ "data": ... }` shape. Paginated bodies also include `links` and `meta`. The SPA reads `meta.current_page`, `meta.last_page`, `meta.per_page`, `meta.total`, and `links.next`.

Validation failures use Laravel's 422 body: `{ "message", "errors" }`.

### Auth

| Method and path | Auth | Result |
|---|---|---|
| POST `/api/login` | public | `{ "token", "user": { "id", "name", "email" } }` |
| POST `/api/logout` | sanctum | 204, current token deleted |
| GET `/api/me` | sanctum | `{ "data": { "id", "name", "email" } }` |

Login accepts `email` and `password`. Failure returns 401 with the message `These credentials do not match our records.` Login is throttled to 5 requests per minute per IP. A throttled login returns 429.

### Public reads

| Method and path | Notes |
|---|---|
| GET `/api/testimonials` | published only |
| GET `/api/faqs` | published only |
| GET `/api/team-members` | published only |
| GET `/api/clients` | published only |
| GET `/api/properties` | published only, paginated |
| GET `/api/properties/{slug}` | published only; unknown or unpublished slug returns 404 |
| GET `/api/property-filters` | distinct published locations for one locale |

`GET /api/property-filters` accepts `locale` (`en` or `ar`, default `en`) and returns `{ "data": { "locations": ["..."] } }`, sorted by that location string ascending.

`GET /api/properties` query parameters:

- `page` (default 1)
- `per_page` (default 9, maximum 50). Home calls this with `per_page=3` and sends no search parameters.
- `locale` optional, `en` or `ar`, default `en`. It chooses the title and location columns used by `q` and `location`.
- `q` optional string, max 100, case-insensitive substring of `title_en` or `title_ar`.
- `location` optional case-sensitive exact match on `location_en` or `location_ar`.
- `type` optional, one of `villa`, `apartment`, `commercial`.
- `price_range` optional, one of `lt500000`, `500000to1000000`, `gt1000000`. The middle band includes both ends.
- `size_range` optional, one of `lt1500`, `1500to3000`, `gt3000`, compared to `area_sqft`. The middle band includes both ends.
- `year_range` optional, one of `pre2000`, `2000to2015`, `post2015`, compared to `year_built`. The middle band includes both ends.

An unknown value for `locale`, `type`, `price_range`, `size_range`, or `year_range` returns 422. Filters combine with AND.

The list payload for a property includes `id`, `slug`, both titles, both descriptions, `price`, `currency`, both locations, `bedrooms`, `bathrooms`, `area_sqft`, `year_built`, `property_type`, `sort_order`, and `cover_image` (`id`, `url`, `alt_en`, `alt_ar`, or null). It omits `is_published`, the gallery, and features. The list query eager-loads the cover image only.

The detail payload adds `images` (`id`, `url`, `alt_en`, `alt_ar`, `is_cover`, `sort_order`) and `features` (`id`, `label_en`, `label_ar`, `sort_order`). It eager-loads images and features. It omits `is_published`.

Other public resources return both languages and their public image URL (`avatar_url`, `photo_url`, or `logo_url`), plus `rating` for testimonials and `sort_order`. They omit `is_published`.

### Inquiries

POST `/api/inquiries` is public. A valid body returns 201 and `{ "data": { "id" } }`. An unpublished or missing `property_id` on a property inquiry returns 422.

### Admin

Prefix `/api/admin`, middleware `auth:sanctum`. A guest receives 401.

Resources: `testimonials`, `faqs`, `team-members`, `clients`, `properties`.

Each resource supports:

- GET collection (published and unpublished, same order, no pagination)
- POST create
- GET one
- PUT update
- DELETE (204)

Every admin CMS object includes `is_published` and `sort_order`. The admin property detail object includes `images` and `features`. The admin property list includes `cover_image` and `year_built`, and does not include the full gallery.

Property images:

- POST `/api/admin/properties/{property}/images` multipart field `image`, optional `alt_en`, `alt_ar`, `sort_order`
- PATCH `/api/admin/properties/{property}/images/{image}` for `is_cover`, `sort_order`, `alt_en`, `alt_ar`
- DELETE `/api/admin/properties/{property}/images/{image}`

An image id that does not belong to the property returns 404.

Team, testimonial, and client uploads use multipart fields `photo`, `avatar`, and `logo` on create and update. Sending a new file deletes the previous file.

Feature arrays travel on property create and update, not on their own endpoint.

CORS allows only `FRONTEND_URL` (default `http://localhost:5173`). Credentialed cookies are off. Bearer tokens are the only credential.

API validation messages stay in English for this delivery.

## Error handling

Public CMS blocks render `LoadingState`, then the data, an `EmptyState` when the published list is empty, or `ErrorState` with a retry action when the request fails.

Forms disable the submit control while the request is running. A 422 maps `errors` onto the matching `FormField`. A network failure or a 5xx shows a banner. A successful inquiry clears the form and shows a success message.

The client may warn before upload when the file type or size is outside the rules. The server response is authoritative.

An unknown or unpublished property slug renders a not-found view inside the public layout, with the header, footer, and language switcher still available.

A 500 response is JSON `{ "message" }` and does not include a stack trace.

Models declare `$fillable`. Controllers do not read request input except through a Form Request. Files are deleted after the database change commits.

## Testing

PHPUnit feature tests use the MySQL database `estatein_testing` on the XAMPP server. Storage uses `Storage::fake`. XAMPP MySQL must be running.

| Test file | Behavior |
|---|---|
| `tests/Feature/AuthTest.php` | bad login is 401 with the generic message and no token; good login returns a token; `/api/me` works with that token; logout then `/api/me` is 401; the sixth login in a minute is 429 |
| `tests/Feature/AdminAuthorizationTest.php` | a guest receives 401 for GET, POST, PUT, and DELETE on each admin resource collection, and for POST, PATCH, and DELETE on the property image routes |
| `tests/Feature/TestimonialAdminTest.php` | create, update, delete, and one validation error (missing `quote_en`) |
| `tests/Feature/FaqAdminTest.php` | create, update, delete, and missing `question_en` |
| `tests/Feature/TeamMemberAdminTest.php` | create, update, delete, and missing `name_en` |
| `tests/Feature/ClientAdminTest.php` | create, update, delete, and missing `logo` |
| `tests/Feature/PropertyAdminTest.php` | create, update, delete, missing `title_en`, publish with zero images is 422, feature array replaces the previous rows, public index follows `sort_order` |
| `tests/Feature/PropertyImageTest.php` | a real PNG is stored; a text file renamed to `.jpg` is 422; delete removes the database row and the file from the fake disk; setting a new cover clears the previous cover |
| `tests/Feature/PropertyFilterTest.php` | with `locale=en`, `q` matches `title_en` and ignores `title_ar`; `type`, `location`, `price_range`, `size_range`, and `year_range` each drop a non-matching published property; an unknown `price_range` is 422 |
| `tests/Feature/PublicVisibilityTest.php` | each unpublished fixture is absent from its public index; the unpublished property slug is 404; a published property payload includes `title_en` and `title_ar` |
| `tests/Feature/InquiryTest.php` | missing email is 422; a property inquiry against an unpublished property is 422; a valid contact inquiry is 201 |

The public UI is checked by hand at desktop and mobile widths, in Arabic and English. That check includes mixed strings (Arabic text, English words, prices, and addresses), flipped directional icons, and form alignment in both directions.

## Delivery phases

Each phase leaves the repository in a state that can be installed and run.

1. **Foundation.** Install PHP 8.3 on the host if it is not on `PATH`. XAMPP's PHP is acceptable when it is 8.3 or newer. Initialize Git at the workspace root. Create the Laravel 13 app in `backend/` and the Vite Vue 3 app in `frontend/`. Point `.env` at the XAMPP MySQL server and create databases `estatein` and `estatein_testing`. Add Sanctum, CORS, the locale store, `dir` on `<html>`, empty public and admin layouts, and a login request path. Both processes start, with MySQL already running in XAMPP.
2. **API.** Migrations, models, seeders, fixture images, public and admin endpoints, image rules, and the PHPUnit files in the testing section. `php artisan test` passes.
3. **Admin UI.** English login and the five CRUD screens, including property gallery and features, wired to the API.
4. **Public UI.** Tailwind tokens from Figma, shared chrome, and the six pages at the three breakpoints, in both languages, reading CMS data from the API. Priority if this phase runs out of time: Home, Properties, and Property Details, then About, then Services and Contact.
5. **Delivery.** Root `README.md`, `AI_USAGE.md`, screenshots, and the assumptions list. The README records the actual hours spent.

### README contents

Requirements (PHP 8.3, Composer, Node 24, XAMPP MySQL), installation, how to create `estatein` and `estatein_testing`, `.env` variables, `php artisan migrate --seed`, `php artisan storage:link`, how to run the API and the SPA, how to run tests, sample admin credentials, hours spent, and a section named "Assumptions and known limits".

### AI_USAGE.md contents

Tool name and purpose, tasks it was used for, short prompt examples, how the output was reviewed, at least one human edit or rejected suggestion, and the parts designed without substantial AI help. This file is written in phase 5 from the real session, not invented in advance.

### Screenshots

Saved under `docs/screenshots/`. Required set: Home desktop English, Home desktop Arabic, Home mobile English, Home mobile Arabic. Add the properties list in both languages when that page is done.

### Assumptions and known limits

The README section states all of the following:

- The admin token is stored in `localStorage`.
- The admin UI is English only.
- The Figma file has no tablet frame. Widths from 768px to 1439px reflow the laptop layout.
- The Comprehensive Pricing Details block is omitted.
- Inquiries are stored and have no admin screen.
- Copy outside the five CMS sections is Vue i18n and is not editable in the admin.
- API validation messages are English.
- Property search price, size, and year cutoffs are the bands in this spec. The controls use the Figma dropdown names, with labels translated in vue-i18n.
- There are no Vue unit tests.
- Anything skipped under the phase 4 priority rule is listed by name.

## Out of scope

- A second admin role, registration, password reset, and email delivery.
- Drag-and-drop ordering. `sort_order` is a number field.
- Cookie-based Sanctum sessions and TypeScript.
- Making services, heroes, navigation, or footer editable in the admin.
- An admin screen for inquiries.
- Vitest, end-to-end browser automation, and visual regression tests.
- Laravel Sail or any other Docker-based runtime.
