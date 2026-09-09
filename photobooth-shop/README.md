# Kislap Booth ✨ — Cute Photobooth Shop System

A full PHP + MySQL photobooth shop: customers pick a session (Solo, Duo, Couple,
Group, Barkada), get guided through a live camera capture with face-detection +
countdown, design a cute photo-strip carousel, check out with Cash/GCash/Card,
and get a downloadable keepsake. Includes a staff admin dashboard.

## Setup (XAMPP / local server)

1. Copy the whole `photobooth-shop` folder into your server's web root
   (e.g. `C:\xampp\htdocs\...\photobooth-shop`).
   **Important:** copy *all* files and folders — `config/`, `includes/`,
   `assets/`, `admin/`, `auth/`, `api/`, `uploads/` — not just `index.php`.
2. Start Apache and MySQL in XAMPP.
3. In phpMyAdmin, create a new database (or let the SQL file create it) and
   import `database/kislap_booth.sql`. It will create the `kislap_booth`
   database automatically along with sample session types and frame designs.
4. Make sure `uploads/orders/` is writable by PHP (this is where captured
   photos and composed strips are saved).
5. Visit `index.php` in your browser through your local server, e.g.
   `http://localhost/your-folder-path/photobooth-shop/index.php`.

`SITE_URL` is auto-detected from the request, so it works no matter what
subfolder you place the project in — no manual editing needed.

## Default Admin Login

- URL: `/admin/login.php`
- Username: `admin`
- Password: `Kislap@123`

**Change this password after your first login** (directly in the `admins`
table for now, or add a "change password" screen later).

## How the flow works

1. **`index.php`** — cute shop landing page, pick a session type.
2. **`booth.php?type=slug`** — turns on the webcam, tries to load face
   detection (via face-api.js from a CDN, with a graceful "manual mode"
   fallback if that fails to load), counts down 3-2-1 and captures the
   required number of shots. Users can also upload photos instead of using
   the camera.
3. **`carousel-maker.php`** — composes the captured shots into a photo strip
   on an HTML canvas, lets the customer choose a themed frame design, and
   shows a live price summary.
4. **`checkout.php`** — customer picks Cash / GCash / Credit Card and
   confirms.
5. **`order-success.php`** — confetti, order summary, and a download link
   for the final photo strip.
6. **`admin/dashboard.php`** — total sales, total orders, pending orders,
   a 7-day sales chart, and a breakdown by session type.
7. **`admin/orders.php`** — full order list with status filters and inline
   payment/order status updates.

## Notes on the camera & face detection

- Camera access requires the site to be served over `http://localhost` or
  HTTPS — browsers block camera access on plain HTTP for non-localhost
  addresses.
- Face detection loads `face-api.js` and its model weights from a public CDN
  at runtime, in the visitor's own browser (not from this server). If that
  network request fails or is blocked, the booth automatically switches to
  **manual mode**: the visitor just poses and taps the shutter button
  themselves — the whole flow still works end to end.
- No payment gateway is wired up (Cash/Credit Card are marked "pay at
  counter"; GCash asks for a reference number for staff to verify) — this
  keeps the demo safe and avoids collecting sensitive card data.

## Customizing prices & frame designs

Edit the `photo_types` and `carousel_designs` tables directly in phpMyAdmin,
or extend `admin/` with a settings page later.