# MetroMingle – Test Checklist

Environment: XAMPP, PHP 8.2.12, MariaDB 10.4.32 (port 3307), Apache (port 80).

## Automated checks performed

- `php -l` on all project PHP files: no syntax errors.
- `db/migrate.php` applied, then re-run to confirm it is idempotent ("Schema already up to date").
- HTTP end-to-end script (curl with session cookies, against `http://localhost/metromingle/`), using throwaway accounts that were removed afterwards: **83 passed, 0 failed.**

| Area | Checks (all PASS) |
|---|---|
| Connectivity & assets | Public pages return 200; CSS/JS load under `/metromingle/` |
| Discovery | Capacity, max price and amenity filters return the correct spaces |
| Access blocking | `db/`, `includes/`, `metromingle-main/` and the ZIP return 403; a `.php` file placed in `uploads/spaces/` returns 403 |
| Registration/login | Register; duplicate username rejected; password stored as bcrypt; wrong password rejected; role-based redirects; legacy plaintext admin login is upgraded to a hash; POST without CSRF token returns 403 |
| Role permissions | Guests are sent to login; a customer cannot open admin or owner pages; an owner cannot open admin or customer booking pages; every role page returns 200 for its own role |
| Owner spaces & photos | Fake image (PHP content as `.jpg`) rejected; `.php` extension rejected; invalid capacity rejected; space created with PNG; relative path stored; photo served and shown on browse and admin pages; editing and replacing a photo deletes the old file; another owner's space cannot be opened or toggled; deactivating hides a space from browse and reactivating shows it again |
| Booking | Past date, over-capacity and end-before-start are rejected; a valid booking is created with server total 3.5 h × ₹2000 = ₹7000.00 and notes saved; it is listed in My Bookings |
| Confirm/reject | Another owner cannot see or confirm the booking; the owning owner confirms; an overlapping booking cannot be confirmed; a new request that overlaps a confirmed booking is rejected; a customer cannot cancel a confirmed booking but can cancel a pending one; the owner rejects a booking; the owner dashboard shows confirmed value |
| Admin | Cancels a booking; cannot revive a cancelled one; deactivates a listing; cannot deactivate an admin; deactivating a customer ends their active session and blocks login |
| Logout | POST logout ends the session |

The browse page (public, desktop width) was also rendered in a browser for a visual check.

## Remaining manual tests (not executed)

- [ ] Visual review of every page at phone width (~375 px) and tablet width, including the stacked table layout and the horizontal sidebar.
- [ ] Upload a real JPG and WebP photo (over 1 MB) through the browser form; check the preview and the 5 MB limit message with a larger file.
- [ ] Remove a photo with the "Remove Photo" button and the edit-form checkbox.
- [ ] Check the live cost estimate on the booking form in the browser.
- [ ] Check theme/amenity suggestion chips on the add/edit space form.
- [ ] Log in once with each legacy account (`user1`, `adya`, `adya123`, `adya1234`) to confirm the password upgrade.
- [ ] Check the 5-failed-attempt login lockout (60 seconds).
- [ ] Check the flow from the landing page hero search to filtered browse results.
