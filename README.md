# HealthTrack

A small, private health tracker for personal use: food log with AI calorie estimation, blood sugar log with target ranges, a private gym log, travel-mode banner, dietitian-published diet plans, and daily AI dashboard insights. Two roles: **Admin** (the patient/owner) and **Dietitian**.

| Role     | Food        | Blood sugar | Gym (private) | Travel | Diet plans | User mgmt | Export CSV | Dashboard |
|----------|-------------|-------------|---------------|--------|------------|-----------|------------|-----------|
| Admin    | read/write  | read/write  | read/write    | read/write | read      | yes       | all data   | AI insight |
| Dietitian| read + comment | read     | —             | —      | create/edit/publish | —    | food+sugar | read-only |

## Tech stack

- Laravel 12 on PHP 8.5, Blade + Alpine.js + Tailwind, Chart.js (bundled via Vite)
- MySQL (designed for shared **cPanel** hosting, PHP/MySQL only)
- AI calorie estimation & insights via Google Gemini (REST, JSON mode) with an automatic heuristic fallback when no API key is set
- Scheduler: daily AI insight + nightly database backup (`backup:database`, JSON.gz snapshots pruned to last 14)

## Local setup

```bash
git clone <repo> && cd HealthApp
cp .env.example .env
php artisan key:generate
composer install
npm install && npm run build   # or npm run dev
```

Create a MySQL database (dev machine uses a private instance on `127.0.0.1:3307`):

```bash
php artisan migrate --seed
php artisan serve --port=8000
# optional AI estimation/insights:
#   GOOGLE_API_KEY=your_key_here  (kept blank it uses heuristic fallback)
```

First visit → **Register** (only allowed while no account exists; the first account becomes Admin).

Seeded admin (if no user exists): `admin@example.com` / `ChangeMe123!` — change these via `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`).

### Dev helper

`dev/restart.sh` restarts the private MySQL instance + the Laravel dev server. Note: MySQL data lives in `/tmp` (wiped on reboot), so after a reboot re-run migrations/seeding.

## Tests

```bash
php artisan test
```

The suite uses a dedicated `healthapp_test` database (port 3307) — `phpunit.xml` is already wired for it.

## Deploying to cPanel

Shared cPanel hosting is "PHP only" (no artisan on the CLI), so the app is **fully database-driven and portable — no SQLite**:

1. Upload the whole project to your document root (e.g. `public_html/healthtrack`).
2. `config/app.php` → set `'url' => env('APP_URL')`. Point your domain's document root at **`public/`** (cPanel "Document Root" setting) so `storage/`, `.env`, and `vendor/` are not web-reachable.
3. In cPanel → **MySQL Databases**: create a database + user, grant all privileges.
4. Copy `.env.example` → `.env` and set:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain`
   - `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `GOOGLE_API_KEY` (optional, for real AI estimates/insights)
   - `MAIL_MAILER=smtp` + SMTP creds (optional, for diet-plan email notifications), else keep `log`.
5. `php artisan key:generate` output → `APP_KEY`; run migrations. On cPanel without shell, do this locally against a copy of the DB, or temporarily on the server if SSH is available.
6. **Scheduler**: add a cron job in cPanel that runs every minute:
   ```cron
   * * * * * /usr/local/bin/php /path/to/HealthApp/artisan schedule:run >> /dev/null 2>&1
   ```
   This fires the daily insight + nightly backup. Backups land in `storage/app/backups/` (keep that directory synced/downloaded monthly).
7. If you don't want a first-visit registration, keep the Admin user safe: the first registered account becomes Admin, afterwards registration is disabled.

## Configuration

Health goal targets live in `config/health.php` and are env-overridable (`TARGET_FASTING_MIN/MAX`, `TARGET_POSTMEAL_MIN/MAX`, `TARGET_CALORIES`). Units are stored per-reading (`mg/dL` or `mmol/L`).

AI providers are injected via `app/Contracts/AiProvider.php`; swap in the container in `app/Providers/AiServiceProvider.php`.

## Onboarding / backup notes

- The daily AI insight is generated each morning and additionally on demand from the dashboard (cached once per day).
- Dietitian plan publishes send an in-app notification to the Admin (email is sent too when SMTP is configured and not in local/log mode).