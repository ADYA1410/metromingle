# PlotSphere – Phase 1 Audit Report

## Starting state (issues found)

- SQL built by string concatenation on every page (SQL injection risk) and plaintext password storage/comparison.
- No CSRF protection, no session hardening, no check of the account's current status.
- Root-absolute links (`/css/styles.css`, `/login.php`, `/admin/...`) broke under `http://localhost/metromingle/`.
- Fake landing statistics ("500+ venues", "4.8★"), no space details page, no photos, no booking notes or server-side date/time/capacity validation, and no reject status.
- An outdated duplicate copy of the project in `metromingle-main/` and four unused placeholder CSS files.

## Changes made

- **Core:** `includes/bootstrap.php` adds prepared-statement DB helpers, a hardened session (strict mode, HttpOnly, SameSite=Lax, ID regeneration on login), CSRF tokens, flash messages, role guards, output escaping and secure photo upload helpers. The base URL is detected automatically, so all links work under `/metromingle/`.
- **Layout:** Pages share `includes/header.php` and `includes/footer.php`, with a role-aware sidebar and topbar. Logout uses a POST form with CSRF protection.
- **Auth:** `password_hash` and `password_verify` are now used. Legacy plaintext passwords are upgraded to a hash on the next successful login. Registration is validated, deactivated users are blocked from logging in, and a short lockout applies after 5 failed attempts.
- **Customer:** Guests can browse spaces with filters for keyword, location, guests, maximum hourly price, theme, amenity and sort order. New `user/space.php` details page. Booking requests take an event type, date, times, guests, theme and notes. The server validates that the date is not in the past and is within one year, that the end time is after the start, a minimum of 1 hour, guests between 1 and capacity, and no overlap with confirmed bookings. The total is calculated on the server as hourly price × duration. Customers have a filterable history and can cancel pending bookings. The dashboard shows real counts.
- **Owner:** A shared add/edit form with validation and theme/amenity suggestion chips. Owners can upload, replace or remove a photo, activate or deactivate listings, and confirm or reject requests (confirmation is blocked when it overlaps another confirmed booking). Every query is scoped to `owner_id`.
- **Admin:** Live statistics. Users can be searched and filtered and activated or deactivated (admins are protected; a deactivated owner's spaces are hidden). All listings can be activated or deactivated. Booking status changes follow allowed transitions only.
- **Uploads:** JPG, JPEG, PNG and WebP up to 5 MB. Both extension and MIME type (finfo) are checked, plus `getimagesize`. Filenames are random 32-character hex strings and the stored extension comes from the MIME type. Deletion is limited to paths matching `uploads/spaces/<hex>.<ext>`. `uploads/spaces/.htaccess` only serves those image names. Every page falls back to a placeholder when a space has no photo.
- **Hardening:** `.htaccess` denies web access to `includes/`, `db/`, `auth/`, `metromingle-main/`, and to `*.zip`, `*.sql`, `*.md`, `*.log` and `*.bak` files. Directory listing is disabled.
- **UI:** `css/styles.css` was extended with the existing branding (colours, Inter font, navy/blue palette). It now has venue cards with photos, filter bars, status tabs, empty states, alerts, and a responsive sidebar and tables (tables become stacked cards on phones). `js/app.js` adds a live cost estimate, confirmation prompts, suggestion chips and photo preview.

## Database changes (additive, via `db/migrate.php`)

A backup was taken first: `db/backup_before_phase1.sql`. No tables, columns or rows were dropped.

| Change | Purpose |
|---|---|
| `users.status ENUM('active','inactive') DEFAULT 'active'` | Admin activation/deactivation |
| `spaces.photo VARCHAR(255) NULL` | Relative path of the primary photo |
| `bookings.notes TEXT NULL` | Customer notes |
| `bookings.status` adds `'rejected'` (NOT NULL, default `pending`) | Owner rejection |

`db/setup.sql` was updated for fresh installs and its demo passwords are now hashed. Legacy columns `bookings.addons` and `bookings.budget` are kept for existing data but are no longer used in the UI.

Data note: during testing, `admin` and `owner1` logged in and their passwords were upgraded to hashes (still `1234`). All test-created users, spaces, bookings and photos were removed afterwards. Row counts are back to 6 users, 4 spaces and 4 bookings.

## Files removed

- `css/styles1.css`, `css/php.css`, `css/style-reg.css`, `css/events_style.css`: placeholder stylesheets that no page referenced.

## Files added

`includes/` (bootstrap, header, footer, space_card, space_form), `user/space.php`, `db/migrate.php`, `db/backup_before_phase1.sql`, `.htaccess` files, `uploads/spaces/`, and the three docs.

## Kept – needs your decision

- `metromingle-main/`: an older duplicate of the whole project (vulnerable code, now blocked by `.htaccess`). It is safe to delete once you confirm it isn't needed.
- `AD (2).zip` and `metromingle-main/AD (2).zip`: an older static HTML/PHP prototype archive. Kept as requested, and blocked from web download.
- `db/backup_before_phase1.sql`: the pre-migration backup. It contains the old plaintext passwords, so delete it once you no longer need it.
