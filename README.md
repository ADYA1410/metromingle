# MetroMingle

Event-focused space discovery and booking management. Customers find and request event spaces, owners manage listings and requests, and administrators manage the platform.

Features: event-oriented space discovery, capacity and budget filtering, transparent hourly pricing, local venue photos, owner-controlled listings and centralised booking management.

## Requirements

- XAMPP with PHP 8.x (tested on 8.2.12), Apache on port 80, MariaDB on port **3307**
- PHP extensions `mysqli`, `fileinfo`, `mbstring` (enabled by default in XAMPP)
- Apache `AllowOverride All` for `htdocs` (XAMPP default), so the `.htaccess` rules apply

## Setup

1. Put the project in `C:\xampp\htdocs\metromingle` and start Apache and MySQL in the XAMPP Control Panel.
2. Database settings are in `db/connect.php` (host `127.0.0.1`, user `root`, empty password, database `metromingle`, port `3307`).
3. **Existing database:** run the safe, additive migration once:
   ```
   C:\xampp\php\php.exe C:\xampp\htdocs\metromingle\db\migrate.php
   ```
   **New database:** create `metromingle` (utf8mb4) in phpMyAdmin and import `db/setup.sql`.
4. Open <http://localhost/metromingle/>.

## Demo accounts (password `1234`)

| Role | Username |
|------|----------|
| Admin | `admin` |
| Owner | `owner1` |
| Customer | `user1` |

Accounts created before Phase 1 with plaintext passwords are automatically re-saved as secure hashes on their next successful login.

## Usage

- **Customer:** Register, browse and filter spaces (guests can browse without logging in), open a space, send a booking request, then track or cancel pending requests under *My Bookings*.
- **Owner:** Register with "List my spaces". Add or edit spaces, upload/replace/remove one photo per space, activate/deactivate listings, and confirm or reject requests.
- **Admin:** View statistics, activate/deactivate customers and owners, activate/deactivate listings, and change booking statuses.

## Structure

```
index.php, login.php, register.php, logout.php
includes/   bootstrap (session, CSRF, DB, auth, uploads), layout, shared partials
user/ owner/ admin/   role-specific pages
db/         connect.php, migrate.php, setup.sql
css/styles.css, js/app.js
uploads/spaces/   venue photos (only image files are served)
```

Photos are stored in `uploads/spaces/`. Only the relative path is saved in MySQL.
