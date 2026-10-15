#!/usr/bin/env bash
#
# NutriPulse VPS diagnostic report.
#
# Run on the Ubuntu server from the app directory:
#   bash deploy/diagnose.sh               # auto-detects app dir from ./artisan
#   bash deploy/diagnose.sh /var/www/nutripulse
#
# Output is SAFE to paste into a chat: secrets in .env are masked
# (only booleans/lengths shown for keys, passwords, tokens).
#
# What it checks: OS/PHP, required PHP extensions, app dir + git tag,
# relevant .env values, DB connection + migration status, storage perms,
# nginx/php-fpm status, cron, outbound HTTPS, and the last errors from
# storage/logs/laravel.log.

set -uo pipefail

APP_DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$APP_DIR" 2>/dev/null || { echo "!! Cannot cd to app dir: $APP_DIR"; exit 1; }

line() { printf '\n===== %s =====\n' "$1"; }
mask() { if [ -n "${1:-}" ]; then printf '<set,len=%s>' "${#1}"; else printf '<empty>'; fi; }
envget() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- ; }

line "OS / KERNEL"
(uname -a; . /etc/os-release 2>/dev/null && echo "$PRETTY_NAME") 2>/dev/null

line "APP DIR + GIT"
echo "app_dir: $APP_DIR"
command -v git >/dev/null && { echo -n "git_tag: "; git -C "$APP_DIR" describe --tags --always 2>/dev/null || echo "(not a git checkout)"; echo -n "branch: "; git -C "$APP_DIR" rev-parse --abbrev-ref HEAD 2>/dev/null; }
echo "artisan: $([ -f artisan ] && echo yes || echo NO)"

line "PHP"
(php -v 2>&1 | head -1) || echo "!! php CLI not found"
echo "note: these are the CLI SAPI's extensions; PHP-FPM (the web SAPI) can differ."
echo "      check /etc/php/*/fpm/conf.d for what FPM actually loads."
php -r '
$needed = ["pdo_mysql","mbstring","openssl","curl","xml","dom","ctype","json","zip","gd","bcmath","intl","fileinfo","tokenizer","filter","hash","pcre","pdo","session"];
foreach ($needed as $e) {
    printf("  ext %-10s %s\n", $e, extension_loaded($e) ? "OK" : "MISSING");
}
' 2>/dev/null || echo "!! php -r failed"

line ".env PRESENT?"
[ -f .env ] && echo ".env: yes" || echo ".env: NO  <-- fatal"
echo "APP_ENV=$(envget APP_ENV)"
echo "APP_DEBUG=$(envget APP_DEBUG)"
echo "APP_URL=$(envget APP_URL)"
echo "APP_KEY=$(mask "$(envget APP_KEY)")"
echo "DB_CONNECTION=$(envget DB_CONNECTION)"
echo "DB_HOST=$(envget DB_HOST)"
echo "DB_PORT=$(envget DB_PORT)"
echo "DB_DATABASE=$(envget DB_DATABASE)"
echo "DB_USERNAME=$(envget DB_USERNAME)"
echo "DB_PASSWORD=$(mask "$(envget DB_PASSWORD)")"
echo "SESSION_DRIVER=$(envget SESSION_DRIVER)"
echo "CACHE_STORE=$(envget CACHE_STORE)"
echo "QUEUE_CONNECTION=$(envget QUEUE_CONNECTION)"
echo "MAIL_MAILER=$(envget MAIL_MAILER)"
echo "AI_PROVIDER=$(envget AI_PROVIDER)"
echo "GOOGLE_API_KEY=$(mask "$(envget GOOGLE_API_KEY)")"

line "LARAVEL COMMANDS"
php artisan --version 2>&1 | head -1
echo "-- migrate:status --"
php artisan migrate:status 2>&1 | tail -15
echo "-- about (cache drivers) --"
php artisan about --only=environment,cache,drivers 2>&1 | head -20

line "PERMISSIONS"
ls -ld storage bootstrap/cache 2>&1
find storage bootstrap/cache -not -perm -u=w 2>/dev/null | head -5 && echo "(items above NOT writable by owner — if any)"

line "WEB SERVER"
(command -v nginx >/dev/null && nginx -t 2>&1) || echo "nginx: not installed"
systemctl is-active nginx php*-fpm 2>/dev/null | paste -sd' '
(systemctl --no-pager status 'php*-fpm' 2>/dev/null | head -3)

line "CRON"
crontab -l 2>/dev/null || echo "(no crontab for $(whoami))"

line "OUTBOUND HTTPS"
if command -v curl >/dev/null; then
  curl -s -o /dev/null -w 'google: %{http_code}\n' --max-time 10 https://www.google.com/ 2>&1
  curl -s -o /dev/null -w 'gemini: %{http_code}\n' --max-time 10 https://generativelanguage.googleapis.com/ 2>&1
else
  echo "curl not installed"
fi

line "PHP ERROR LOG (last 60 lines of storage/logs/laravel.log)"
if [ -f storage/logs/laravel.log ]; then
  tail -n 60 storage/logs/laravel.log
else
  echo "(no storage/logs/laravel.log)"
fi

line "DONE"
echo "Paste this whole output back, plus a short description of each broken screen."