# Deploying NutriPulse to Hepsia (ResellersPanel / CloudLogin)

Step-by-step guide for taking the Laravel 13 health-tracker app to shared
hosting managed via **https://uk.cloudlogin.co** (ResellersPanel, **Hepsia**
control panel). Your facts gathered at planning time:

| Question | Your answer | Consequence |
|---|---|---|
| SSH / Git on plan? | **File Manager / FTP only** | Zip upload + extract; no git deploy, no server-side artisan/terminal |
| PHP version | **8.2+ selectable** | Needs **8.3+** — Laravel 13 requires PHP ^8.3, pick the newest offered (8.3/8.4/8.5) |
| Document root | **unknown** | Guide covers both subfolder-root (preferred) and public_html fallback |
| Domain | **np.isiteguru.com** (temporary) | All URLs/SSL below use this hostname |

> **Two important corrections to the original brief, based on the built app:**
> 1. **Database is MySQL, not SQLite.** We abandoned SQLite during development
>    (the target host is PHP-only and `pdo_sqlite` isn't available); the app is
>    MySQL-only, tests included. So the "SQLite file permissions" worry is moot —
>    we use Hepsia's **MySQL Databases** manager instead.
> 2. **AI provider is Google Gemini**, not OpenAI/Anthropic. The outbound test
>    below therefore probes `generativelanguage.googleapis.com`. If outbound is
>    blocked, the app still runs (falls back to a built-in heuristic) — only the
>    AI calorie estimates and dashboard insights degrade.

---

## 1. Critical first step: verify outbound HTTPS (do this before anything)

Shared hosting sometimes blocks outbound connections. Run this check now.

1. Open `deploy/connectivity-check.php` in a local editor — it is a self-contained,
   framework-free PHP script.
2. Upload it via File Manager to your site root as `check.php`. If you aren't sure
   of the root yet, see Section 4 and use the same directory the app will live in.
3. Visit **https://np.isiteguru.com/check.php** (over HTTPS — the temp hostname
   ships with a working certificate).
4. Read the result:

```
[PASS] Internet reachability (google.com) => HTTP 200 (curl)
[PASS] Gemini AI API host ... => HTTP 200 (curl)
```

- **Both PASS** → great, continue to Section 2.
- **First FAIL, second PASS** → AI features only. Open a support ticket:
  *"Please whitelist outbound HTTPS to `generativelanguage.googleapis.com` for
  my account on host…"* Continue deploying meanwhile; everything except AI still works.
- **Google FAIL too** → fully blocked. Ask ResellersPanel how outbound HTTPS is
  enabled on your plan before continuing.

5. **Delete `check.php`** when done.

---

## 2. Server-side prerequisites to confirm in CloudLogin

These are all clicks in the Hepsia UI (not code):

- **PHP version:** Hepsia → *PHP Configuration / PHP Version* for the site. Set to the
  **newest 8.3+** available (8.3, 8.4, 8.5). Confirm `pdo_mysql` is listed (the
  connectivity check reports it). A wrong version shows a 500 + "PHP versions
  from PHP 8.3 are supported" style error.
- **Cron jobs:** Hepsia → *Cron Jobs*. Confirm the tool exists (needed in Section 8).
- **SSL:** Hepsia → *SSL Certificates*. Confirm free Let's Encrypt is orderable for
  `np.isiteguru.com` (Section 9).
- **MySQL:** Hepsia → *Website* → *MySQL / PostgreSQL*. Confirm you can create a
  database + user (Section 5).

---

## 3. Decide the folder layout (document root)

Folder layout determines two things in the guide: where the zip extracts, and
what you later set as the site's document root.

**Recommended — Option A: point the document root at the app's `public/` folder.**
In Hepsia (Website → *Document root* or similar for the hostname) you can usually
select any existing subfolder as the site root (e.g. `public_html/nutripulse/public`).
This keeps all Laravel internals **outside** the web-served folder — the safest setup,
zero file edits.

**Fallback — Option B: `public_html` is fixed and you cannot choose a subfolder root.**
Then:

1. Extract the app zip into a folder **outside** `public_html` — use the account home,
   e.g. (from File Manager breadcrumbs) `/home/YOURUSER/app_live`. The app root is this folder.
2. Move the **contents** of the app's `public/` into `public_html` (index.php, .htaccess,
   favicon, `build/`, `vendor/` stays in the app folder).
3. Edit `public_html/index.php` — replace the three `__DIR__.'/../` path fragments
   with `__DIR__.'/../app_live/`:
   ```php
   if (file_exists($maintenance = __DIR__.'/../app_live/storage/framework/maintenance.php')) {
   require __DIR__.'/../app_live/vendor/autoload.php';
   $app = require_once __DIR__.'/../app_live/bootstrap/app.php';
   ```
   (Adjust `app_live` to whatever the sibling folder is named.)

> Do the connectivity check against whichever root is live (Option A → `public/`,
> Option B → `public_html`) so you test the final entry point.

---

## 4. Local prep, before anything is uploaded

Run these **on your dev machine**:

```bash
cd /home/mj/HealthApp

# 1. Generate a production APP_KEY and note it down (used in .env on server)
php artisan key:generate --show

# 2. Build the deployable zip (composer --no-dev + npm build + excludes)
#    On this machine composer isn't on PATH, so:
COMPOSER_CMD="php /tmp/opencode/composer.phar" ./deploy/prepare-release.sh v0.1.1
```

The script produces `nutripulse-v0.1.1.zip` in the repo root. It **excludes** `.git`,
`node_modules`, `.env*`, `storage/app/backups`, and log files. It **includes**
`vendor/` (production deps, optimized autoloader) and `public/build/` (frontend assets).

The zip does **not** contain any database file — the schema is created by migrations
on the server (Section 7). There is nothing database-related to stage locally.

---

## 5. Upload via Hepsia File Manager

1. Log in at **https://uk.cloudlogin.co** → open your account → **File Manager**.
2. If using Option A, under the site root folder (e.g. `public_html`) create
   `nutripulse` (`public_html/nutripulse`).
3. Upload `nutripulse-v0.1.1.zip` there, then use the File Manager's **Extract**
   action (knowledgebase: "Unarchive") — much faster than transferring thousands of
   small files over FTP.
4. Verify `vendor/autoload.php` and `public/index.php` exist after extraction.
5. **Permissions** (File Manager → right-click → *Change permissions*). These must be
   writable by the PHP/webserver process — if Hepsia shows you group/other bits, set
   `755` on **directories** and `644` on **files**, and make these folders writable
   (`775` or Hepsia's "writable" tick, whichever the UI offers):
   - `storage/` (recursively — `storage/framework/`, `storage/logs/`, `storage/app/backups/`)
   - `bootstrap/cache/`
   - `database/` (not strictly needed for MySQL, but harmless)

   Do **not** upload anything under `public/` that contains secrets. There is none.

---

## 6. Create the MySQL database (Hepsia UI — no code)

Hepsia → *Website* → *MySQL* (Database Manager):

1. **Create database** → name it e.g. `youruser_nutripulse`.
2. **Create user** → strong password (this is your `DB_USERNAME`/`DB_PASSWORD`).
3. **Add user to database** with all privileges (usually automatic).
4. Note the exact values Hepsia shows for the host (`DB_HOST` is often `localhost`
   on Hepsia — use whatever the panel displays, sometimes it's a hostname like
   `db1.hepsia.info`).

Write these down for the `.env` step.

---

## 7. Create `.env` on the server (edit via File Manager)

1. File Manager → navigate to the app root (`public_html/nutripulse` or `app_live`).
2. Create a file named `.env` — best: open `.env.production.example` (included in the
   zip) with the editor, fill in your values, then **Save As** `.env`. Or create `.env`
   and paste the template contents. Key values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://np.isiteguru.com`
   - `APP_KEY=<the key you generated in Section 4>`
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_HOST` from Section 6
   - `GOOGLE_API_KEY=<your Gemini key>` (empty → heuristic fallback)
   - `ADMIN_EMAIL` / `ADMIN_PASSWORD` — the admin that gets seeded on first migrate
   - `DEPLOY_TOKEN=<long random string>` — generate locally:
     `php -r "echo bin2hex(random_bytes(24));"`
3. Ensure the file permissions are `600`/`644` — not world-writable.

---

## 8. Run migrations + caches (the one-time web runner)

Because you have no SSH, deploy `deploy/web_deploy.php` temporarily:

1. Upload it from the repo's `deploy/` folder to `public/` (Option A) or `public_html`
   (Option B) **as `_deploy.php`**.
2. In a browser, run these **in order**, each with your token:

   ```
   https://np.isiteguru.com/_deploy.php?token=YOUR_TOKEN&cmd=migrate
   https://np.isiteguru.com/_deploy.php?token=YOUR_TOKEN&cmd=config:cache
   https://np.isiteguru.com/_deploy.php?token=YOUR_TOKEN&cmd=route:cache
   https://np.isiteguru.com/_deploy.php?token=YOUR_TOKEN&cmd=view:cache
   ```
   - `migrate` creates the tables **and seeds the admin** from `ADMIN_EMAIL`/`ADMIN_PASSWORD`. If it says "nothing to migrate", you may have already run it.
   - Config/route/view cache bake env + routes for performance. **If you later edit `.env`, re-run `config:cache`** or the change won't take effect.
3. Test the site (Section 10), then **delete `public/_deploy.php`**. Keep `DEPLOY_TOKEN`
   in `.env` — harmless once the file is gone, and no other config reads it.

> Security note: `_deploy.php` is token-guarded and whitelists exactly these five
> commands, but it is still a web-facing admin surface — this is a no-SSH workaround,
> not a permanent feature. Delete it immediately after deployment.

---

## 9. Cron job (Hepsia UI) — scheduler + backups

Hepsia → *Cron Jobs* → add a job:

- **Command:** replace `PATH_TO_PROJECT` with the server path to the app root
  (you can grab it from the File Manager breadcrumb):
  ```
  * * * * * /usr/bin/php PATH_TO_PROJECT/artisan schedule:run >> PATH_TO_PROJECT/storage/logs/scheduler.log 2>&1
  ```
  If `pwd`-style paths are required and the actual PHP binary differs
  (`php8.3`, `php8.4`…), use `which php8.3`'s result. Blank `* * * * *` runs once a
  minute, which is what Laravel's scheduler wants — it only *fires* the daily
  insight (06:00) and nightly backup (02:30) items when their time comes.
- After 24h confirm `storage/logs/scheduler.log` exists and `storage/app/backups/`
  contains a dated `db-*.json.gz` (Section 10.4).

---

## 10. SSL + HTTPS (Hepsia UI — no code)

1. Hepsia → *SSL Certificates* → issue a **free Let's Encrypt** cert for
   `np.isiteguru.com` (auto-issued for temporary hostnames on most plans; if yours
   already shows a real cert, skip).
2. Hepsia usually force-redirects HTTPS in the .htaccess it generates. The zip's
   `public/.htaccess` is the standard Laravel one (see below) — add a redirect at the
   very top if the site loads over HTTP:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{HTTPS} off
       RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   </IfModule>
   ```
   The shipped `public/.htaccess` (also the one to keep in `public_html` for
   Option B) already contains the Laravel rewrites:
   ```apache
   <IfModule mod_rewrite.c>
       Options -MultiViews -Indexes
       RewriteEngine On
       # Handle Authorization / X-XSRF-Token headers
       # Redirect trailing slashes
       # Send all non-file/non-dir requests to index.php
   </IfModule>
   ```
3. Verify the padlock + `https://np.isiteguru.com` in the address bar.

---

## 11. Post-deployment verification checklist

1. **Homepage:** `https://np.isiteguru.com` (or `/dashboard`) loads over HTTPS, no
   padlock warning, no mixed-content (the bundled assets are same-origin).
2. **Login + roles:** log in as the seeded **Admin**, then the **Dietitian** account
   (create one under *User Management* as admin). On the dietitian:
   - Food log loads (read-only) and shows the last 90 days only.
   - Gym/travel are hidden from navigation.
   - `/workouts` returns 403.
   Re-verify the same privacy boundaries that hold locally.
3. **AI:** re-upload `check.php` transiently OR simply add a food entry and confirm a
   calorie estimate appears (Gemini key set) or heuristic values appear (empty key).
4. **Scheduler + backups:** after ~24h confirm `storage/logs/scheduler.log` grew and
   `storage/app/backups/` has a today-dated `db-*.json.gz` (download it to be sure).
5. **Persistence:** log a food entry and a blood-sugar reading, reload the page, confirm
   both persisted (proves MySQL writes + session tables).
6. **Registration is off:** since a user now exists, `/register` redirects — as designed
   (first account = admin).

---

## 12. Rollback & safety net

- **Before every deploy:** via File Manager, *Copy* the current live folder
  (`public_html/nutripulse`, or `public_html` + `app_live`) to
  `previous_build_<date>` in the account home, or download a zip of it. Restore =
  delete new folder, rename old back, extract previous zip's `public/` if Option B.
- **Database:** Hepsia's MySQL tool can export/import `.sql` backups — do a manual
  download of the DB after major changes. The in-app nightly `backup:database` job also
  writes a portable JSON snapshot to `storage/app/backups/`; keep a monthly copy
  downloaded elsewhere.
- **If data looks corrupt:** restore the DB from the Hepsia SQL export. The JSON
  backups are a flavor of last resort — there is no one-click import for them, so
  treat them as read-only history until the SQL export path is confirmed working.
- **App-level sign of trouble:** 500s usually mean storage permissions, a stale
  `bootstrap/cache/` (delete files there via File Manager) or a missing `APP_KEY`
  (Section 7). `storage/logs/laravel.log` holds details — it's inside the live folder.

---

## Deploy checklist — quick recap

- [ ] Connectivity check passed (and `check.php` deleted)
- [ ] PHP 8.3+ selected; `pdo_mysql` present
- [ ] `zip` built with `prepare-release.sh`; `APP_KEY` generated
- [ ] Zip uploaded + extracted at the chosen root; `storage/` + `bootstrap/cache/` writable
- [ ] MySQL DB + user created (not SQLite)
- [ ] `.env` created from `.env.production.example` with real values + `DEPLOY_TOKEN`
- [ ] `_deploy.php`: migrate (seeded admin) → config:cache → route:cache → view:cache
- [ ] **`_deploy.php` deleted**
- [ ] Cron job added for `artisan schedule:run`
- [ ] Let's Encrypt cert + HTTPS verify
- [ ] Post-deploy checklist (Section 11) signed off