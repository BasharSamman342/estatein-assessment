# Estatein Frontend: End-to-End Verification & Delivery Plan

Plan for end-to-end verification of the Estatein Vue 3 SPA against the live Laravel 13 API, automated browser integration testing, backend warning fixes, screenshot captures, and delivery documentation.

## Objectives

1. **Dev Server Restart & Live API Integration**
   - Ensure `estatein-frontend/.env.local` is active with:
     ```env
     VITE_USE_MOCK_DATA=false
     VITE_API_URL=http://localhost:8000/api
     ```
   - Restart the Vite development server so all modules and fetch requests point to the Docker-hosted Laravel API on port 8000.
   - Verify `http://localhost:5173` loads cleanly.

2. **Public Flow Verification in Browser**
   - **Home Page**: Verify live data loading across sections (Featured Properties, Testimonials, FAQs, Team Members, Valued Clients).
   - **Property Search & Filters**: Test query filtering (`q`), band filters (Price, Size, Year built), Property Type, and distinct location list loaded dynamically from `/api/property-filters`.
   - **Pagination**: Test page transitions, query preservation in URL, and page limits.
   - **Property Detail**: Test navigation to slug (e.g. `seaside-serenity-villa`), gallery viewer with thumbnail navigation, property specs/features, and accordion FAQs.
   - **Inquiry Forms**: Verify client validation on empty/invalid inputs and successful submission (201 response) for general contact and property-specific inquiries.
   - **Localization & Bidi**: Verify English (LTR) and Arabic (RTL) toggling, font switches (Urbanist / IBM Plex Sans Arabic), and proper bidirectional rendering.

3. **Admin Flow Verification in Browser**
   - **Authentication**: Test login rejection on bad credentials and successful login with `admin@estatein.test` / `password`, storing Sanctum bearer token and redirecting to admin dashboard.
   - **CMS Record CRUD**: Test creating, editing, and publishing records in FAQs/Testimonials/Team/Clients.
   - **Image Upload & Replace**: Test image upload (JPEG/PNG/WebP), preview display, and replacing existing images (`_method=PUT` multipart).
   - **Property & Gallery Management**: Test creating a draft property, uploading gallery images, designating cover image, managing key features, and publishing.

4. **Backend Test Suite Polish**
   - Eliminate the console warning emitted during `SeederTest` when re-seeding empty tables (`Sample content already exists; skipped`).
   - Ensure `docker exec estatein-api php artisan test` and host test runs execute cleanly against `estatein_testing` with 0 failures, 0 errors, and 0 warnings.

5. **Browser-Level Integration Tests**
   - Install `@playwright/test` configured to use the existing local Chrome / Edge installation.
   - Implement `e2e/public-flow.spec.ts` covering all public interactions.
   - Implement `e2e/admin-flow.spec.ts` covering authentication and administrative operations.
   - Add `"test:e2e": "playwright test"` to `package.json`.

6. **Responsive & RTL Screenshots**
   - Automate screenshot generation to `docs/screenshots/`:
     - `home-desktop-en.png` (1440x900)
     - `home-desktop-ar.png` (1440x900, RTL)
     - `home-mobile-en.png` (390x844)
     - `home-mobile-ar.png` (390x844, RTL)
     - `properties-desktop-en.png`
     - `properties-desktop-ar.png`
     - `property-detail-desktop-en.png`
     - `admin-dashboard.png`
     - `admin-property-editor.png`

7. **Documentation Polish**
   - Update `estatein-README.md` with complete instructions, commands, architecture, and live API connection guide.
   - Update `README.md` and `AI_USAGE.md` to reflect the completed frontend, test results, and final delivery status.
