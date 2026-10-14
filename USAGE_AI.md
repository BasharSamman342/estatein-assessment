# AI Tool Usage and Contribution Disclosure

## Tools used

| Tool | Use in this project |
|---|---|
| Claude Code (Claude Opus 5.5, as recorded in the backend notes) | Backend planning and implementation assistance, Laravel API, migrations/models/requests/resources/controllers/services, seeders, feature tests, documentation and backend verification. It was also used as an implementation assistant during the project. |
| A second Claude agent | Independent review of the backend plan and Laravel-specific implementation considerations. |
| Grok | Used during the frontend design phase. The exact prompts and individual suggestions were not retained in the supplied project notes, so this document does not attribute specific components or code lines to Grok. |

The project was primarily AI-generated, with the developer making architecture and product decisions, reviewing outputs, and correcting or accepting implementation choices. The developer should be prepared to explain the code and the design decisions during review.

## Design contract origin

`docs/design.md` originated from assessment material provided to the developer. It was used as the binding contract for API routes, data fields, validation, seed data and frontend/backend integration. The duplicate root copy `2026-10-13-estatein-design.md` has been removed so there is one canonical copy in `docs/design.md`.

## Developer decisions and contribution

The developer's decisions included the overall implementation sequence, the paired English/Arabic fields and bilingual API responses, Sanctum bearer-token authentication, MySQL for local development and tests, and using generated placeholder images rather than exporting photography from Figma. The developer reviewed the AI-generated work and made decisions about scope and trade-offs. The supplied project notes state that no code was written entirely by hand; this is disclosed rather than presented as independent manual authorship.

## Verification performed

### Backend checks recorded in the supplied development notes

The prior development notes report that the Laravel feature suite ran against MySQL and passed with 136 tests and 760 assertions, with additional API smoke checks, upload/cover-image checks, seed-data checks, `pint --test`, `composer audit`, and route/config cache checks. These are historical claims from the supplied notes, not checks rerun while preparing this submission archive.

### Frontend checks and manual testing

The developer reports having run the application and manually tested the main public and admin flows. The project includes Vitest unit tests and Playwright E2E specs. However, the screenshot E2E run included in the supplied archive had failed with `Too Many Attempts`, consistent with repeated logins hitting the API's five-per-minute login throttle. The failed `frontend/test-results/` output has been removed from the submission archive.

**Not independently verified during archive preparation:** `php artisan test`, `npm run test:run`, `npm run build`, `npm run lint`, and the complete Playwright suite were not rerun in a clean clone as part of preparing this archive. Run the commands in the root README before submitting, and update this section with their actual results.

## Known limitations

- Placeholder seed images were generated rather than exported from Figma.
- Figma was not used directly to validate visual fidelity.
- No dedicated tablet frame/layout was implemented.
- The Comprehensive Pricing Details block was omitted as allowed by the brief.
- The admin UI is English-only as allowed by the brief.
- Inquiries are stored, but there is no admin screen for reviewing them.
- The planned admin dashboard and property-editor screenshots have not been captured.

## Time spent

The developer reports **9 hours total** for the project. This is the overall reported time across backend, frontend, planning, review and testing; a more precise phase-by-phase breakdown was not provided and is therefore not estimated here.
