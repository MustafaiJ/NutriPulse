#!/usr/bin/env bash
#
# Build a deployable zip of NutriPulse for Hepsia (File Manager / FTP deploy,
# no SSH). Produdes vendor/ (composer --no-dev) and public/build/ (npm) inside
# a fresh staging copy, then zips everything EXCEPT anything server-specific:
#   .env / .env.* , node_modules/ , .git/ , storage/app/backups/ , logs
#
# Usage:
#   ./deploy/prepare-release.sh v0.1.1
#
# Customise (optional):
#   COMPOSER_CMD   e.g. "php /tmp/opencode/composer.phar"  (no composer on PATH?)
#   PHP            override php binary (default: php)
#   ZIP_DIR        where to write the zip (default: repo root)
#
# After the zip is produced, upload it via Hepsia File Manager, extract, and
# follow docs/DEPLOYMENT_HEPSIA.md for .env + database + _deploy.php steps.

set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:?usage: ./deploy/prepare-release.sh <version>  e.g. ./deploy/prepare-release.sh v0.1.1}"
PHP="${PHP:-php}"
COMPOSER_CMD="${COMPOSER_CMD:-composer}"
ZIP_DIR="${ZIP_DIR:-$REPO}"
STAGING="/tmp/nutripulse-release"
ZIP="$ZIP_DIR/nutripulse-$VERSION.zip"

echo ">> Building NutriPulse release $VERSION"
echo "   Source        : $REPO"
echo "   Staging       : $STAGING"
echo "   Output zip    : $ZIP"
echo "   Composer      : $COMPOSER_CMD"

# 1. Fresh staging copy (exclude heavy / machine-specific folders).
rm -rf "$STAGING"
mkdir -p "$STAGING"
# Note: only /\.env$/ is excluded — the `.env.production.example` template
# IS deliberately shipped so it can be saved-as `.env` on the server.
tar -cf - \
    --exclude='./.git' \
    --exclude='./node_modules' \
    --exclude='./.env' \
    --exclude='./storage/app/backups' \
    --exclude='./storage/logs/*.log' \
    -C "$REPO" . | tar -xf - -C "$STAGING"

cd "$STAGING"

# 2. Production dependencies (no dev), optimised autoloader.
echo ">> composer install --no-dev --optimize-autoloader"
$COMPOSER_CMD install --no-interaction --no-dev --prefer-dist --no-scripts --optimize-autoloader
$COMPOSER_CMD dump-autoload --no-dev --optimize

# 3. Frontend build (with dev deps, then ship only public/build/).
echo ">> npm install + npm run build"
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi
npm run build

# 4. Build the zip.
echo ">> Packaging (excluding .env, node_modules, .git, backups)"
rm -f "$ZIP"
zip -r -q "$ZIP" . \
    -x 'node_modules/*' \
    -x '.env' \
    -x '.git/*' \
    -x 'storage/app/backups/*' \
    -x 'storage/logs/*'
touch "$ZIP"

echo
echo ">> Done: $ZIP ($(du -h "$ZIP" | cut -f1))"
echo "   Next: upload via Hepsia File Manager and see docs/DEPLOYMENT_HEPSIA.md"