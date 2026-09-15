# Deploying NutriPulse to a VPS

Full DevOps runbook for installing the Laravel 13 health-tracker on a bare
**VPS** (Ubuntu/Debian assumed; adapt package names for other distros).
The app needs **PHP ≥ 8.3** and a **MySQL/MariaDB** database (the app is
MySQL-only — no SQLite).

Two ways to get the code on the box:

- **Recommended — Git:** `git clone` from GitHub and deploy from tagged releases.
- **Zip:** upload `nutripulse-vX.Y.Z.zip` (ships pre-built `vendor/` + `public/build/`).

---

## 1. Server prerequisites

Budget a small VPS (1 vCPU / 1–2 GB RAM is plenty for this app).

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.4 + extensions (Laravel needs pdo_mysql, openssl, mbstring, xml, ctype, json, curl, zip, gd, bz2)
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl \
    composer nginx mysql-server git unzip curl

# Node only needed if you will rebuild frontend assets
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
```

Notes:
- If your distro only offers PHP 8.3, use that — it satisfies the `^8.3` constraint.
- Create a non-root user for the app:
  ```bash
  sudo useradd -r -s /usr/sbin/nologin deploy   # or +m giving a home, then sudo -u deploy
  ```

---

## 2. Get the code — Git path

```bash
export APP_USER=deploy            # change if you used another name
export APP_DIR=/var/www/nutripulse
export APP_PATH="$APP_DIR/public"
export GIT_USER=MustafaiJ

sudo mkdir -p "$APP_DIR"
sudo chown "$APP_USER":"$APP_USER" "$APP_DIR"

# Fix ownership of /var/www for clone + build
sudo chown "$APP_USER":"$APP_USER" /var/www

sudo -u "$APP_USER" git clone https://github.com/$GIT_USER/NutriPulse.git "$APP_DIR"
cd "$APP_DIR"

# Deploy an exact release
sudo -u "$APP_USER" git fetch --tags
sudo -u "$APP_USER" git checkout v0.1.2

# (Uncommitted zip users skip straight to Section 3)
```

Prefer SSH over HTTPS for cloning by adding your deploy key once:

```bash
ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519            # on the VPS
# Add ~/.ssh/id_ed25519.pub to GitHub -> Settings -> SSH keys
```

---

## 3. Database (MySQL/MariaDB)

Create the database + user, granting only what's needed:

```bash
sudo mysql
```

```sql
CREATE DATABASE nutripulse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nutripulse'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON nutripulse.* TO 'nutripulse'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Keep `<strong-password>`, database name, and user for the `.env` step.

---

## 4. `.env`

```bash
cd /var/www/nutripulse
cp .env.production.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

| Setting | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://<your-domain>` |
| `APP_KEY` | already generated above |
| `DB_DATABASE` | `nutripulse` |
| `DB_USERNAME` | `nutripulse` |
| `DB_PASSWORD` | `<strong-password>` |
| `GOOGLE_API_KEY` | your Gemini key, or **empty** → heuristic fallback |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | the admin seeded on `migrate --seed` |
| `MAIL_MAILER` | `log` default (in-app notifications only); use `smtp` to send email |

These defaults are already correct: `SESSION_DRIVER=database`,
`CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`.

---

## 5. Install dependencies + build assets

```bash
cd /var/www/nutripulse

# Production deps, optimized autoloader
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction

# Frontend (skip if deploying from the prepared zip — it ships public/build/)
npm ci && npm run build        # or: npm install && npm run build

# File permissions — web server must be able to write runtime dirs
sudo chown -R deploy:deploy /var/www/nutripulse
sudo -u deploy php artisan storage:link || true
sudo -u deploy php artisan migrate --seed --force
```

`migrate --seed` creates all tables and the initial admin from `ADMIN_EMAIL`
/ `ADMIN_PASSWORD`. Registration is then disabled (first user = admin), so run
it before anyone hits the site.

---

## 6. Nginx + PHP-FPM

The site root must be the Laravel **`public/`** folder. Create
`/etc/nginx/sites-available/nutripulse`:

```nginx
server {
    listen 80;
    server_name <your-domain>;

    root /var/www/nutripulse/public;
    index index.php;

    charset utf-8;

    client_max_body_size 8M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param APP_ENV production;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/nutripulse /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

(If you prefer Apache: use the shipped `public/.htaccess` + `mod_rewrite`, and
set `DirectoryIndex index.php`/`AllowOverride All` for the site root.)

---

## 7. HTTPS (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d <your-domain>
sudo certbot renew --dry-run            # verify auto-renewal cron is installed
```

`certbot --nginx` picks up the server block above and enables redirects.

---

## 8. Caches

Run after `.env` is final (re-run after **any** `.env` edit):

```bash
cd /var/www/nutripulse
sudo -u deploy php artisan config:cache
sudo -u deploy php artisan route:cache
sudo -u deploy php artisan view:cache
```

---

## 9. Scheduler + queue (cron)

Laravel needs one cron line (runs every minute; the daily 06:00 AI insight,
02:30 backup, and the mine queue worker are dispatched from it):

```bash
sudo crontab -u deploy -e
```

```cron
* * * * * cd /var/www/nutripulse && php artisan schedule:run >> /dev/null 2>&1
```

No separate supervisor is required — `routes/console.php` already schedules a
self-terminating `queue:work` every minute. Verify after 24h that
`storage/app/backups/` has a dated `db-*.json.gz`.

---

## 10. Post-deployment verification

```bash
curl -sI https://<your-domain>/up | head -1        # 200
curl -s https://<your-domain>/dashboard -o /dev/null -w '%{http_code}\n'  # 302 (login)
```

Then, in the browser:
- [ ] Homepage loads over HTTPS, no mixed content.
- [ ] Admin login → dashboard works; AI estimate appears on a food entry
      (or heuristic values if `GOOGLE_API_KEY` is empty).
- [ ] Create a dietitian under User Management; log in as them → gym/travel
      hidden, `/workouts` returns 403.
- [ ] Log a reading, reload — data persisted (MySQL writes confirmed).
- [ ] After 24h: scheduler log + `db-*.json.gz` backup exist.

---

## 11. Rollback & backups

- **Rollback** (git path): `git fetch --tags && git checkout <previous-tag>`,
  then re-run the cache commands. Zip path: keep the previous zip; stop nginx,
  swap the folder, restart.
- **Database backups:**
  ```bash
  # Nightly, via crontab:
  0 2 * * * mysqldump -u nutripulse -p'<password>' nutripulse | gzip > /var/backups/nutripulse-$(date +\%F).sql.gz
  # keep 14: find /var/backups -name 'nutripulse-*.sql.gz' -mtime +14 -delete
  ```
  Plus the app's own nightly `backup:database` (JSON snapshot in
  `storage/app/backups/`).
- Keep `/var/backups` on the same box as a first line of defense, and download
  a copy off-site periodically.

---

## 12. Troubleshooting quick reference

| Symptom | Check |
|---|---|
| 502 Bad Gateway | PHP-FPM running? `sudo systemctl status php8.4-fpm`; socket path matches nginx config |
| 500 error | `storage/logs/laravel.log`; usually storage permissions (Section 5) or missing `APP_KEY` |
| 403 | Site root not `…/public` in nginx (Section 6); or file ownership |
| Blank page / 521 | `APP_DEBUG=true` temporarily to read the error, then set back |
| Composer dies with memory error | `COMPOSER_MEMORY_LIMIT=-1 php /usr/local/bin/composer install …` |
| AI estimates missing | `GOOGLE_API_KEY` empty or outbound HTTPS blocked: `curl -sI https://generativelanguage.googleapis.com/` |
| Changes to `.env` don't apply | re-run `php artisan config:cache` |

---

## 13. Security reminders

- Run composer/npm/artisan as `deploy`, never `root` (or use `sudo -u deploy`).
- Keep the web root = `public/` so `.env`, `vendor/`, `storage/` are unreachable.
- `sudo ufw allow OpenSSH; sudo ufw allow 'Nginx Full'; sudo ufw enable`.
- Disable password SSH login and use keys on the VPS.
- Keep the DB password out of git; it lives only in `.env` (already gitignored).