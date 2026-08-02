#!/usr/bin/env bash
# Pull the latest main and make it live. Runs on the server, as the deploy user.
#
#   ./scripts/deploy.sh
#
# Called by .github/workflows/deploy.yml over SSH, and safe to run by hand.
# Idempotent, and leaves the site serving the old code if the pull fails.

set -euo pipefail

PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT"

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

say "Fetching"
BEFORE="$(git rev-parse HEAD)"
git fetch --quiet origin main
git reset --hard --quiet origin/main
AFTER="$(git rev-parse HEAD)"

if [[ "$BEFORE" == "$AFTER" ]]; then
    echo "already at $(git log --oneline -1)"
else
    echo "$(git rev-parse --short "$BEFORE") -> $(git rev-parse --short "$AFTER")"
    git log --oneline "$BEFORE..$AFTER" | sed 's/^/  /'
fi

# The three config.php files are committed and credential-free, so a reset is
# safe. env.local.php is gitignored and survives untouched.

say "Clearing caches"
# OpenCart caches compiled Twig templates and query results under storage/.
# Stale entries after a deploy show up as old markup or missing new fields.
find storage/cache storage/cache-seller \
     public_html/system/storage/cache public_html/system/storage/cache-seller \
     -mindepth 1 ! -name 'index.html' -delete 2>/dev/null || true
echo "template and data caches cleared"

# image/cache is expensive to rebuild (thousands of thumbnails) and is not
# affected by code changes, so it deliberately survives deploys.

say "Permissions"
# PHP-FPM writes cache, sessions, logs and uploaded images.
chgrp -R www-data storage public_html/system/storage public_html/image 2>/dev/null || true
chmod -R g+rwX    storage public_html/system/storage public_html/image 2>/dev/null || true
chmod +x scripts/*.sh

say "Reloading PHP-FPM"
# Drops the opcache so the new code is actually served. Needs the sudoers entry
# from .deploy/sudoers-deploy.
sudo -n systemctl reload php8.3-fpm

say "Verifying"
sleep 2

# Full public URL, e.g. https://new.ewmarket.sa/. Hitting 127.0.0.1 instead
# would land on whichever vhost Apache considers default, not necessarily this
# site, so a green check there would mean nothing.
URL="${DEPLOY_HEALTHCHECK_URL:-}"
if [[ -z "$URL" ]]; then
    echo "DEPLOY_HEALTHCHECK_URL not set — deployed, but not verified"
    exit 0
fi

CODE="$(curl -sS -o /tmp/deploy-check.html -w '%{http_code}' --max-time 30 "$URL" || echo 000)"

if [[ "$CODE" != 200 ]]; then
    echo "storefront returned HTTP $CODE — check storage/logs/error.log"
    exit 1
fi
if grep -qE '<b>(Warning|Fatal error)</b>' /tmp/deploy-check.html; then
    echo "PHP errors rendered into the page — check storage/logs/error.log"
    exit 1
fi
if grep -qi 'install/index.php' /tmp/deploy-check.html; then
    echo "redirected to the installer — the vhost is not passing DB settings"
    exit 1
fi

echo "storefront OK at $(git log --oneline -1)"
