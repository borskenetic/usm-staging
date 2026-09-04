# TASK.MD

## Current Task

Track and execute the migration of `pantas-v2.5` toward a separated Attendance + Library architecture and a module-aware role system.

Primary objective:

- Adopt independent Library and Attendance domains.
- Keep Library patrons separate from Attendance patrons.
- Give Library its own independent attendance/visit scanner and logs.
- Keep school-wide Attendance separate and important.
- Add module-aware staff roles with `super_admin` access to all modules.
- Keep `/api/mobile` working by migrating it to the new Library domain tables.

Reference plan:

- `documentation/architecture-migration-plan.md`

## Active Work

- [x] Upgrade `/library/attendance/logs` with green attendance-logs UI, filters, `Lastname, Firstname` search, PDF/Excel export, and no Section column.
- [x] Split Library attendance logs into Students and Faculty & Staff tabs with tab-scoped filters/exports.
- [x] Align Faculty & Staff patrons table with Registered Students design; instant Students ↔ Faculty tab switch; support `Lastname, Firstname` search.
- [x] Serve role-dashboard card/grid CSS as a static `public/css/dashboard.css` asset so Library/Attendance dashboards render without a Vite build.
- [x] Replace Bootstrap public registration pages with a single direct `/register` page using the shared auth modal (Attendance ↔ Library slide).
- [x] Start Milestone 13: Developer-managed register modal customization on a dedicated Developer settings page.
- [x] Start Milestone 12: Developer-managed login modal customization on a dedicated Developer settings page.
- [x] Start Milestone 11: isolated Developer branding role and system-wide `pantas-default` customization.
- [x] Compare current `pantas-v2.5` behavior with the target separated-domain architecture.
- [x] Identify major feature overlap and architecture gaps.
- [x] Confirm separated Attendance + Library architecture direction.
- [x] Create detailed migration plan.
- [x] Create this task tracker.
- [x] Clarify remaining product/technical decisions before coding.
- [x] Start Milestone 1: auth/module foundation.
- [x] Start Milestone 3: route split.
- [x] Complete Milestone 3: route split.
- [x] Start Milestone 4: separated domain model foundation.
- [x] Complete Milestone 4: models/controllers/services foundation.
- [x] Complete Milestone 5: mobile API migration to Library domain tables.
- [x] Complete Milestone 6: dashboards and module switching.
- [x] Complete Milestone 7: Library attendance system.
- [x] Complete Milestone 8: public and patron workflow split.
- [x] Complete Milestone 9: admin activity/audit trail foundation.
- [x] Complete Milestone 10: focused verification and hardening pass.
- [x] Review Pantas-UI services against `/api/mobile` response contracts.
- [x] Add operational documentation for staff roles, seed accounts, and patron domain separation.
- [x] Align Super Admin navigation with active module switching.
- [x] Implement workflow-grouped sidebar navigation and role-specific dashboard pages.
- [x] Add richer demo workflow seed data for manual testing.

## Confirmed Decisions

- Existing `pantas-v2.5` data does not need to be preserved.
- Fresh migrations/schema changes are acceptable.
- Library patrons and Attendance patrons should be independent records.
- Library must have its own attendance/visit logs based on registered Library patrons.
- School Attendance must use separate Attendance patrons and logs.
- Add `super_admin` with access to all modules.
- `/api/mobile` must remain available and be migrated to the new Library domain tables.
- Plan first before implementation.

## Open Questions

- [x] Frontend direction: keep `pantas-v2.5` Blade + Tailwind/Flowbite/daisyUI for Milestone 1.
- [x] Module switching: staff with multiple roles can switch active modules from the application shell.
- [x] User roles: Spatie roles are authoritative; keep `users.role` temporarily as a compatibility bridge during migration.
- [x] Default `super_admin` seed credentials for local development: `super_admin@pantas.test` / `password`.
- [x] Route naming: preserve current `pantas-v2.5` route names where possible; use module-prefixed names for new module-only routes.
- [x] Milestone 1 scope: auth/module foundation only.

## Milestones

### Milestone 1: Auth And Module Foundation

- [x] Add or standardize Spatie role usage.
- [x] Seed roles:
  - `super_admin`
  - `library_admin`
  - `library_staff`
  - `attendance_admin`
  - `attendance_staff`
- [x] Seed a default `super_admin`.
- [x] Add `users.is_active`.
- [x] Add module access service.
- [x] Add module middleware:
  - `super-admin`
  - `library.access`
  - `library.admin`
  - `attendance.access`
  - `attendance.admin`
- [x] Add role-aware login redirect behavior.
- [x] Add dashboard routes:
  - Super Admin
  - Library Admin
  - Library Staff
  - Attendance Admin
  - Attendance Staff
- [x] Add tests for role access, login redirects, and inactive staff behavior.

### Milestone 2: Fresh Separated Domain Schema

- [x] Create Library domain tables.
- [x] Create Attendance domain tables.
- [x] Create or update shared/system tables.
- [x] Ensure Library attendance logs reference only Library patrons.
- [x] Ensure school Attendance logs reference only Attendance patrons.
- [x] Add migration/schema tests.

### Milestone 3: Route Split

- [x] Keep `routes/web.php` for public/shared routes.
- [x] Add `routes/library.php`.
- [x] Add `routes/attendance.php`.
- [x] Add `routes/super-admin.php`.
- [x] Keep `routes/api.php` for `/api/mobile`.
- [x] Verify public routes still resolve.
- [x] Verify role-gated routes return expected access behavior.

### Milestone 4: Models, Controllers, And Services

- [x] Move Library workflows to Library-prefixed models/tables.
- [x] Add Attendance-prefixed models/tables for school attendance.
- [x] Keep existing behavior where possible for route access and school attendance scanning.
- [x] Use services for attendance scanning and attendance reports where workflow complexity grows.
- [x] Add relationship and workflow tests for route split, Attendance scanner workflow, Attendance domain models, and Library domain model relationships.

### Milestone 5: Mobile API Migration

- [x] Update catalog endpoints to use Library books/e-books.
- [x] Update borrowing endpoints to use Library circulation logs.
- [x] Update room endpoints to use Library rooms/reservations.
- [x] Update feedback endpoints to use Library feedback records.
- [x] Confirm mobile user/profile linkage to Library patrons.
- [x] Run existing mobile API tests.
- [x] Review Pantas-UI API expectations before changing response shapes.

### Milestone 6: Dashboards And Module Switching

- [x] Build module-aware dashboards.
- [x] Implement module switching if confirmed.
- [x] Verify `super_admin` can access all modules.
- [x] Verify single-module users land on the correct dashboard.
- [x] Add dashboard/module-switch tests.
- [x] Add role-specific dashboard metrics, quick actions, recent activity, and charts.

### Milestone 7: Library Attendance System

- [x] Add Library visit scanner.
- [x] Resolve scans against `library_students` and `library_employees`.
- [x] Write Library visits to `library_attendance_logs`.
- [x] Add Library visit logs/reports.
- [x] Add Library visit feedback/settings.
- [x] Add tests proving Library visit logs and school Attendance logs remain isolated.

### Milestone 8: Public And Patron Workflows

- [x] Add `/register` module choice.
- [x] Add Library student/employee registration.
- [x] Add Attendance student/employee registration.
- [x] Add approval flows for both domains.
- [x] Add OPAC and Library reservation workflows against Library tables.
- [x] Add Library kiosk lookup.
- [x] Add patron profile edit requests.
- [x] Keep public room booking and schedule.
- [x] Add end-to-end workflow tests.

### Milestone 9: Admin Activity And Audit Trail

- [x] Add module-aware `admin_activities`.
- [x] Log staff account changes.
- [x] Log patron approval/rejection.
- [x] Log catalog actions.
- [x] Log room approval/rejection.
- [x] Log fine clearance.
- [x] Add tests for key audit events.

### Milestone 10: Verification And Hardening

- [x] Create role access tests.
- [x] Create login redirect tests.
- [x] Create patron module isolation tests.
- [x] Create registration flow tests.
- [x] Create Library kiosk lookup tests.
- [x] Create OPAC tests.
- [x] Add Attendance scanner tests.
- [x] Add Library attendance scanner tests.
- [x] Keep mobile API tests passing.
- [x] Run `php artisan route:list`.
- [x] Run focused PHPUnit tests after each milestone.
- [x] Run `npm run build` when frontend assets change.
- [x] Run Pint before finalizing larger PHP changes.

### Milestone 11: Developer Branding Role

Plan reference: `documentation/developer-branding-plan.md`

#### Task 1: Role And Access Isolation

- [x] Add the `developer` Spatie role and compatibility role handling.
- [x] Add exact-role `developer` middleware with no Super Admin bypass.
- [x] Add Developer login redirect and dashboard route.
- [x] Prevent Developer access to Super Admin, Library, and Attendance staff modules.
- [x] Ensure Super Admin and all other roles receive `403` on `/developer/*`.
- [x] Limit Developer navigation to Dashboard, Branding Settings, Profile, and Logout.
- [x] Hide Developer navigation from Super Admin and all other roles.
- [x] Remove the Library/Attendance module switcher from the Developer shell.

#### Task 2: Branding Defaults And Persistence

- [x] Define permanent original asset paths and colors in `config/branding.php`.
- [x] Create the `branding_settings` migration with nullable override fields and `updated_by`.
- [x] Add the `BrandingSetting` model and updater relationship.
- [x] Create `BrandingService` to merge defaults and overrides.
- [x] Add active-branding caching and cache invalidation.
- [x] Add missing-custom-file fallback to original assets.

#### Task 3: Developer Routes And Interface

- [x] Create and load `routes/developer.php`.
- [x] Add Developer dashboard controller and view.
- [x] Add Branding Settings controller, Form Request, and view.
- [x] Show current and original banner/logo previews.
- [x] Add color pickers and `#RRGGBB` inputs with current/original values.
- [x] Add live banner, sidebar, and palette preview.
- [x] Add Save Changes, per-field restore, and Restore to Default actions.
- [x] Add a confirmation dialog for full restoration.

#### Task 4: Upload And Color Safety

- [x] Validate banner and logo MIME types, sizes, dimensions, and image decoding.
- [x] Store generated filenames under public branding storage directories.
- [x] Make image replacement safe so the old custom file is deleted only after success.
- [x] Prevent original Pantas assets from being overwritten or deleted.
- [x] Validate and normalize colors to uppercase six-digit `#RRGGBB` values.
- [x] Reject CSS expressions, URLs, variables, named colors, alpha colors, and incomplete values.

#### Task 5: Application Branding Integration

- [x] Share active branding with Blade layouts through one centralized integration point.
- [x] Expose validated active colors as CSS custom properties.
- [x] Connect customizable colors only to `pantas-default`.
- [x] Preserve all other themes and per-user theme preferences.
- [x] Replace relevant hard-coded banner references in shared layouts.
- [x] Replace relevant hard-coded sidebar/application logo references.
- [x] Apply branding consistently to authenticated, public, guest, Library, Attendance, and scanner layouts.

#### Task 6: Restoration And Audit

- [x] Implement transactional full Restore to Default.
- [x] Implement partial restore for individual assets and colors.
- [x] Clear custom overrides and return to configuration defaults.
- [x] Delete only obsolete custom files after a successful restore.
- [x] Clear branding cache after update or restoration.
- [x] Log branding updates, previous/new values, and file paths.
- [x] Log full and partial restoration with the acting Developer.
- [x] Restrict Developer activity display to branding-related records.

#### Task 7: Tests And Verification

- [x] Test Developer-only route access and exact-role enforcement.
- [x] Test that Super Admin and other roles receive `403` on Developer routes.
- [x] Test that Developer receives `403` on other staff modules.
- [x] Test Developer login redirect, isolated navigation, and hidden module switcher.
- [x] Test valid and invalid image uploads, limits, and safe replacement.
- [x] Test valid and invalid color values and normalization.
- [x] Test `pantas-default` customization and isolation from other themes.
- [x] Test full and partial restoration of assets and colors.
- [x] Test that original Pantas assets are never deleted.
- [x] Test missing custom-file fallback and cache invalidation.
- [x] Test branding update and restoration activity records.
- [x] Run focused Branding, Developer Access, Theme Preference, and Module Access tests.
- [x] Run `php artisan route:list`, `npm run build`, and Pint.
- [ ] Visually verify responsive layouts, theme isolation, restoration, fallback, and color contrast.

### Milestone 12: Developer-Managed Login Modal

Goal: allow the isolated Developer role to customize the public login modal from a dedicated Login Modal Settings page, separate from the Developer Dashboard and existing Branding Settings page, without changing authentication, registration, or role-redirect behavior.

#### Task 1: Customization Scope And Defaults

- [x] Add permanent defaults for the login modal logo, welcome label, portal name, description, sign-in heading, and field placeholders.
- [x] Add permanent defaults for the left-panel background, modal background, modal text, and sign-in button colors.
- [x] Keep Attendance and Library registration wording and workflows unchanged unless separately approved.
- [x] Ensure every new setting falls back safely to its original Pantas value.

#### Task 2: Persistence And Branding Service

- [x] Add nullable login-modal override fields to `branding_settings` through a new migration.
- [x] Add the new fields to `BrandingSetting`, `config/branding.php`, and `BrandingService`.
- [x] Add the login modal logo to centralized asset URL, upload, replacement, missing-file fallback, and cleanup handling.
- [x] Include the new settings in branding caching and cache invalidation.
- [x] Include the new settings in individual and full restoration.
- [x] Record login-modal changes and restorations in the existing branding activity log.

#### Task 3: Validation And Upload Safety

- [x] Add text length limits and normalize submitted modal text.
- [x] Validate colors as strict uppercase six-digit `#RRGGBB` values.
- [x] Reject CSS expressions, URLs, variables, named colors, alpha colors, and incomplete color values.
- [x] Validate the modal logo MIME type, file size, dimensions, and image decoding.
- [x] Store the logo under generated public branding storage paths.
- [x] Delete an old custom logo only after its replacement or restoration succeeds.
- [x] Ensure original Pantas assets can never be overwritten or deleted.

#### Task 4: Separate Developer Login Modal Interface

- [x] Add a dedicated Developer route, controller action, and Login Modal Settings page separate from the Developer Dashboard and Branding Settings page.
- [x] Add a Login Modal Settings navigation link visible only to the exact Developer role.
- [x] Add text inputs, color controls, and a modal logo uploader with current and original values.
- [x] Add a responsive live preview for desktop and mobile modal layouts.
- [x] Preserve submitted values and show validation errors when saving fails.
- [x] Add per-field restore actions and a Restore Login Modal Defaults action.
- [x] Include the new fields in the existing full Restore to Default action and confirmation flow.

#### Task 5: Public Modal Integration

- [x] Replace hard-coded login logo and text in `resources/views/partials/auth-modal.blade.php` with active branding values.
- [x] Expose validated modal colors through CSS custom properties scoped to the authentication modal.
- [x] Update `public/css/auth-modal.css` to consume the scoped variables without affecting other application surfaces or themes.
- [x] Update `public/js/auth-modal.js` so Login, Attendance Registration, and Library Registration switching does not overwrite customized login content.
- [x] Preserve modal opening, closing, keyboard, focus, error, session-expiry, and responsive behavior.
- [x] Keep the login form action, CSRF protection, remember-me option, validation, inactive-account handling, and role-aware redirects unchanged.

#### Task 6: Direct Login Consistency

- [x] Replace or adapt the legacy `/login` page so it uses the same branded modal experience as the landing page.
- [x] Preserve direct `/login` access for password reset, expired sessions, authentication errors, and bookmarked links.
- [x] Verify the modal automatically opens with the correct login view and retains old email/remember values after validation errors.

#### Task 7: Tests And Verification

- [x] Test default login-modal values and customized rendering.
- [x] Test exact Developer-only update and restore authorization.
- [x] Test valid and invalid modal logo uploads, text limits, and color values.
- [x] Test safe logo replacement, missing-file fallback, and protection of original assets.
- [x] Test individual login-modal restoration and full branding restoration.
- [x] Test branding activity records for login-modal updates and restorations.
- [x] Test the landing-page modal and direct `/login` page for consistent customized values.
- [x] Keep existing authentication, inactive-account, registration, modal, and role-redirect tests passing.
- [x] Run focused `DeveloperBrandingSettingsTest`, `AuthenticationTest`, and `PublicRegistrationSplitTest` coverage.
- [x] Run `php artisan route:list`, `npm run build`, and Pint.
- [ ] Visually verify the landing modal, direct `/login`, registration switching, and responsive desktop/mobile layouts in the in-app browser (browser surface unavailable in the current session).

#### Acceptance Criteria

- [x] A Developer can customize and restore the login modal entirely from the dedicated Login Modal Settings page.
- [x] Customized values appear on both the landing-page modal and direct `/login` experience.
- [x] Attendance and Library registration content and behavior remain unchanged.
- [x] Authentication security, validation, account status checks, and role-based redirects remain unchanged.
- [x] Invalid or missing custom values safely fall back to permanent Pantas defaults.

### Milestone 13: Developer-Managed Register Modal

Goal: allow the isolated Developer role to customize the public Attendance and Library registration presentation from a dedicated Register Modal Settings page, separate from the Developer Dashboard, Branding Settings, and Login Modal Settings, without changing registration fields, validation, uploads, signatures, approval workflows, data ownership, or submission endpoints.

#### Task 1: Customization Scope And Defaults

- [x] Define permanent defaults for the shared Register heading, Login/back label, Attendance tab label, and Library tab label.
- [x] Define separate Attendance registration defaults for the welcome label, portal name, description, logo, service heading, Student label, Employee label, student submit label, and employee submit label.
- [x] Define separate Library registration defaults for the welcome label, portal name, description, logo, service heading, Student label, Faculty & Staff label, student submit label, and employee submit label.
- [x] Define separate Attendance and Library panel, text, accent, active-role, and submit-button colors.
- [x] Keep field labels, field names, required markers, field ordering, form actions, CSRF protection, validation rules, file inputs, signature pads, and approval behavior outside the customizable scope.
- [x] Ensure every register-modal setting safely falls back to its permanent Pantas default.

#### Task 2: Persistence And Branding Service

- [x] Add nullable register-modal override fields to `branding_settings` through a new migration.
- [x] Add register-modal defaults to `config/branding.php` and persistence fields to `BrandingSetting`.
- [x] Add centralized register-modal field groups to `BrandingService` for assets, text, and colors.
- [x] Add Attendance and Library registration logos to asset URL, upload, replacement, missing-file fallback, and cleanup handling.
- [x] Re-merge current register defaults on cached reads so older branding cache payloads remain upgrade-safe.
- [x] Include register-modal settings in cache invalidation, individual restoration, register-modal-only restoration, and full branding restoration.
- [x] Record register-modal updates and restorations in the existing branding activity log.

#### Task 3: Validation And Upload Safety

- [x] Add a Developer-only Form Request dedicated to register-modal settings.
- [x] Add text length limits and normalize submitted registration text.
- [x] Validate colors as strict uppercase six-digit `#RRGGBB` values.
- [x] Reject CSS expressions, URLs, variables, named colors, alpha colors, and incomplete color values.
- [x] Validate Attendance and Library logo MIME types, file sizes, dimensions, and image decoding.
- [x] Store uploaded logos under generated public branding storage paths.
- [x] Delete replaced custom logos only after a successful update or restoration.
- [x] Ensure original Pantas assets can never be overwritten or deleted.

#### Task 4: Separate Developer Register Modal Interface

- [x] Add dedicated Developer routes, controller actions, and a Register Modal Settings page.
- [x] Add a Register Modal Settings link visible only to the Developer role in the sidebar and command palette.
- [x] Add a separate Register Modal card and link on the Developer Dashboard.
- [x] Divide the page into Shared, Attendance Registration, and Library Registration settings.
- [x] Add text inputs, color controls, and separate Attendance/Library logo uploaders with current and original previews.
- [x] Add live preview controls for Attendance Student, Attendance Employee, Library Student, and Library Faculty & Staff states.
- [x] Add desktop and mobile preview modes.
- [x] Preserve submitted values and display validation errors when saving fails.
- [x] Add per-field restore actions and a Restore Register Modal Defaults action.
- [x] Include register-modal settings in the existing full Restore to Default confirmation flow.

#### Task 5: Public Register Modal Integration

- [x] Replace hard-coded shared registration headings and tab labels in `resources/views/partials/auth-modal.blade.php` with active register-modal values.
- [x] Replace hard-coded Attendance registration presentation text and logo with active Attendance values.
- [x] Replace hard-coded Library registration presentation text and logo with active Library values.
- [x] Expose validated Attendance and Library register colors through CSS variables scoped to registration mode only.
- [x] Update `public/css/auth-modal.css` without affecting login-mode branding or other application surfaces.
- [x] Update `public/js/auth-modal.js` so service and role switching applies the correct Attendance or Library registration branding.
- [x] Ensure returning to Login mode restores the independently configured login logo, text, and colors.
- [x] Apply register-modal settings consistently on both the landing page and direct `/login` experience.
- [x] Preserve registration scrolling, signature pads, file-name display, keyboard behavior, closing behavior, and responsive layout.

#### Task 6: Registration Workflow Protection

- [x] Keep Attendance student registration posting only to `attendance.pending.store`.
- [x] Keep Attendance employee registration posting only to `attendance.pendingEmployee.store`.
- [x] Keep Library student registration posting only to `library.pending.store`.
- [x] Keep Library employee registration posting only to `library.pendingEmployee.store`.
- [x] Preserve CSRF tokens, hidden service/type fields, model binding, old-input restoration, and validation-error panel selection.
- [x] Preserve profile/formal photo inputs and all four signature-pad workflows.
- [x] Confirm register customization cannot change patron-domain ownership or mix Attendance and Library records.

#### Task 7: Restoration And Audit

- [x] Restore an individual shared, Attendance, or Library register value without affecting other modal settings.
- [x] Restore the entire register-modal group without affecting Login Modal Settings or unrelated branding.
- [x] Include register-modal values in the system-wide Restore to Default action.
- [x] Delete only obsolete custom register logos after restoration succeeds.
- [x] Clear branding cache after every update or restoration.
- [x] Log changed register fields and link activity records back to Register Modal Settings.
- [x] Log individual, register-group, and full-branding restoration actions.

#### Task 8: Tests And Verification

- [x] Test register-modal defaults, schema fields, cache compatibility, and missing-file fallback.
- [x] Test exact Developer-only access to view, update, and restore routes.
- [x] Test valid and invalid Attendance/Library logo uploads, dimensions, sizes, and decoding.
- [x] Test text limits, trimming, strict colors, and unsafe CSS-value rejection.
- [x] Test safe logo replacement and protection of original assets.
- [x] Test individual, register-group, and full-branding restoration boundaries.
- [x] Test branding activity records for register updates and restorations.
- [x] Test customized Attendance and Library rendering on the landing page and direct `/login` page.
- [x] Test Login, Attendance Registration, and Library Registration switching keeps each mode's independent branding.
- [x] Keep existing public registration, domain-isolation, authentication, inactive-account, and role-redirect tests passing.
- [x] Run focused Register Modal, `PublicRegistrationSplitTest`, `AuthenticationTest`, Developer Branding, and Developer Access coverage.
- [x] Run `php artisan route:list`, `npm run build`, Pint, JavaScript syntax validation, and Blade compilation.
- [ ] Visually verify all four registration states at desktop and mobile sizes when the in-app browser is available.

#### Acceptance Criteria

- [x] A Developer can customize and restore the register modal from a dedicated Register Modal Settings page.
- [x] Attendance and Library registration branding can be configured independently.
- [x] Customized register values appear consistently on the landing page and direct `/login` experience.
- [x] Login Modal Settings remain independent and are restored when returning to Login mode.
- [x] Registration fields, validation, uploads, signatures, endpoints, approvals, and patron-domain ownership remain unchanged.
- [x] Invalid, missing, or stale custom values safely fall back to permanent Pantas defaults.

## Backlog

- [ ] Decide whether to keep a compatibility layer for old route names.
- [ ] Decide whether old Blade views should be renamed by domain or reused with new data contracts.
- [ ] Decide whether legacy root static files should be removed later.
- [ ] Decide whether Attendance patron management should expand beyond the new pending-registration index into full student/employee index pages.
- [x] Add demo seed coverage for dashboards, Attendance patrons/logs, Library visits, circulation, room reservations, feedback, pending approvals, and audit activity.
- [x] Review Pantas-UI services before mobile API migration.
- [x] Add documentation for staff role setup.
- [x] Add documentation for fresh local seed accounts.
- [x] Add documentation for Library vs Attendance patron separation.

## Discovered During Planning

- `pantas-v2.5` already has most target Library features.
- The largest gap is architectural separation, not feature count.
- Current `pantas-v2.5` uses shared `students` and `employees` tables.
- Current `pantas-v2.5` has `attendance_logs.student_id` as a string field.
- Current `pantas-v2.5` uses simple `users.role` values such as `admin`, `staff`, `student`, and `faculty`.
- `pantas-v2.5` owns `/api/mobile`, so schema changes must account for Pantas-UI.
- Library attendance and school Attendance must be explicitly isolated in schema, routes, controllers, and tests.
- Spatie permission config/migrations existed, but the package dependency was missing and the existing role migration was not Spatie-compatible.
- The test suite was configured to use the local MySQL database. It now uses SQLite in-memory for safer repeatable tests.
- Full-suite testing still exposes older Breeze/profile/password-reset tests for routes not currently registered by this app.
- Milestone 2 keeps legacy tables in place for compatibility, while adding fresh `library_*` and `attendance_*` domain tables.
- School attendance logs now reference `attendance_students` and `attendance_employees`.
- Library visit logs now live in `library_attendance_logs` and reference `library_students` and `library_employees`.
- Milestone 3 moved public/shared routes into `web.php`, Library routes into `routes/library.php`, Attendance routes into `routes/attendance.php`, and staff-management routes into `routes/super-admin.php`.
- Existing route names are preserved where possible so current Blade links and redirects continue to resolve.
- Milestone 4 now has separated Eloquent models for the new `attendance_*` and `library_*` tables.
- School attendance scanner, logs, reports, and feedback now use `AttendanceStudent` / `AttendanceEmployee` domain tables.
- Existing Library workflow model names now point to `library_*` tables so controllers keep their current route/view contracts while storage moves into the separated Library domain.
- Library validation rules and raw query joins now use Library-prefixed tables for books, circulation, programs, rooms, reservations, files, fines, holidays, patrons, e-books, and mobile feedback.
- The Library schema includes compatibility columns for current cataloging, prospectus, room reservation, file repository, fine, holiday, and e-book workflows.
- Mobile API catalog, borrowing, room reservation, and feedback flows now read/write Library domain tables while preserving response shapes.
- Dashboard navigation is module-aware: Attendance-only staff no longer see Library navigation, Library-only staff no longer see Attendance navigation, and Super Admin keeps system/module access.
- Library attendance now has its own public scanner, feedback endpoint, admin logs, reports, and feedback settings, all backed by `library_attendance_*` tables.
- Library scanner lookup accepts Library patron QR codes and ID numbers only; Attendance-only patrons are rejected by the Library scanner.
- Role dashboard card/grid CSS originally lived only in Vite-compiled `resources/css/app.css`. `public/build` is gitignored, so deployed dashboards rendered as unstyled stacked text unless `npm run build` had been run on the server. Those styles now load from `public/css/dashboard.css`.
- Public `/register` previously routed users to a module choice page; `/register/library` kept Library pending registration and `/register/attendance` wrote to Attendance pending registration.
- Attendance patron approval moves records from `attendance_pending_*` tables into `attendance_*` patron tables and writes module-aware activity logs.
- Admin activities now capture module, type, actor, optional subject, body, action URL, and icon. Staff changes, patron approval/rejection, catalog changes, room approval/rejection, and fine clearance write audit events.
- Laravel's default table naming required an explicit `$table` on `LibraryAttendanceFeedback` because the migration uses `library_attendance_feedbacks`.
- Focused M7-M10 tests were added for Library attendance isolation, public registration split, audit activity, and public Library workflow surfaces.
- Pantas-UI service endpoints still match the `/api/mobile` route/controller contracts after the backend moved mobile data sources to Library-domain tables.
- Mobile registration currently appears UI-local in Pantas-UI; there is no mobile registration API submission yet.
- Pantas-UI profile can parse QR fields, but `/api/mobile/profile` does not currently return a QR code field; the current profile screen only displays a placeholder QR icon plus student number.
- Pantas-UI `ApiConfig.androidEmulator` uses `127.0.0.1`; normal Android emulator testing may need `10.0.2.2` or an active tunnel/reverse proxy.
- Super Admin access remains cross-module, but sidebar navigation now shows only the active module menu: System, Library, or Attendance.
- Sidebar navigation is now grouped by workflow per active module. Staff sidebars intentionally show operational routes only, while admin/report/settings links remain admin-only.
- Role dashboards now render summary cards, quick actions, recent activity, and Chart.js-powered charts using existing Blade and Bootstrap styling.
- A read-only Super Admin activity index was added so the System Activity sidebar has a concrete audit-trail destination.
- Attendance Admin now has a read-only pending-registration page with existing approval actions; full Attendance student/employee management remains a future product decision.
- `DemoWorkflowSeeder` now fills manual-testing gaps left by the base sample seeders, including role accounts, dashboard metrics, Library circulation, Library visits, school Attendance records, pending approvals, room workflow examples, feedback, and audit activity.
- Developer Task 1 added an exact-role isolated dashboard and middleware. Super Admin cannot list, create, assign, edit, delete, or enter Developer accounts/features; Developer cannot enter other staff modules.
- A data migration now provisions `developer.admin@pantas.text` with the `developer` role on fresh databases while preserving the password of an existing matching account.
- `DeveloperAccessTest` passes all role-isolation checks. `RoleDashboardExperienceTest` still has five pre-existing label-visibility failures because global command-palette data contains routes hidden from the visible role sidebar.
- Developer Tasks 2 and 3 added permanent configuration defaults, singleton branding overrides, caching/fallback service behavior, four protected Developer routes, a current-state dashboard, and a functional settings interface with uploads, color controls, live previews, and restore actions.
- `DeveloperBrandingSettingsTest` passes seven tests covering original values, exact-role route isolation, normalized colors, uploads, dimension/input rejection, safe replacement, and partial/full restoration.
- Developer Task 4 hardened image type, size, dimension, and decoding rules; strict hexadecimal colors; generated storage paths; transactional updates; failed-upload cleanup; and post-commit replacement cleanup that cannot target original assets.
- Developer Task 5 now shares branding centrally with Blade, scopes dynamic color variables to `pantas-default`, and replaces relevant banner/sidebar-logo references across authenticated, public, guest, Library, Attendance, OPAC, kiosk, and scanner surfaces.
- Developer table branding now includes header background, header text, border, and row-hover colors, with defaults, live preview, persistence, restoration, and `pantas-default`-only CSS integration.
- Developer branding now includes a dedicated Sidebar Logout Area Background color for the footer section containing Logout; the button retains its standard danger styling. The setting appears in the live preview and supports individual/full restoration under `pantas-default`.
- Developer branding now includes Sidebar Hover Background and Sidebar Hover Text colors for non-active navigation links, with a small movement effect, live preview, persistence, and restoration under `pantas-default`.
- Developer branding now allows editing the sidebar brand name (`Pantas`), subtitle (`Admin Portal`), and brand text color alongside the existing logo upload, with live preview and individual/full restoration.
- Milestone 12 uses a dedicated Developer Login Modal Settings page, separate from the Developer Dashboard and general Branding Settings page. Tasks 1 and 2 added permanent modal defaults, nullable persistence fields, centralized logo handling, cache/fallback behavior, restoration, audit logging, and focused foundation coverage.
- Milestone 12 Tasks 3 and 4 added strict login-modal text/color/image validation, generated and safely replaced logo storage, three exact-Developer routes, a separate settings page, sidebar/command/dashboard entry points, desktop/mobile live preview, per-field restoration, modal-only restoration, and focused authorization and safety tests.
- Milestone 12 Task 5 connects active Developer settings to the landing-page login panel through scoped CSS variables and dynamic login copy/logo data. Registration mode restores the original registration logo and retains its independent Attendance/Library copy, colors, forms, and endpoints.
- Milestone 12 Task 6 replaces the legacy direct `/login` card with the shared branded modal, supplies the same registration option data as the landing page, opens in Login mode automatically, returns to the landing page when closed, and retains login status, errors, email, and remember-me state. Task 7 automated verification is complete; interactive responsive verification remains open because the in-app browser surface was unavailable.
- Branding cache payloads created before Milestone 12 can omit the new login-modal keys. `BrandingService::active()` now re-merges current configuration defaults on every cache read, and the Developer dashboard has defensive text fallbacks, preventing stale forever-cached payloads from causing undefined-key errors after deployment.
- Added documentation:
  - `documentation/mobile-api-contract-review.md`
  - `documentation/staff-role-setup.md`
  - `documentation/local-seed-accounts.md`
  - `documentation/library-vs-attendance-patrons.md`
