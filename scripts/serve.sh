#!/usr/bin/env bash
# Run the site on PHP's built-in server — no root, no Apache config.
#
#   ./scripts/serve.sh [port]
#
# Good enough for day-to-day work on the code. It is single-request-at-a-time
# and has no .htaccess support (see .deploy/router.php for the rewrite rules it
# reimplements), so use the Apache vhost from scripts/setup-local.sh when you
# need to test anything traffic-shaped.

set -euo pipefail

PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-8000}"
HOST="${HOST:-127.0.0.1}"

php8.3 -m | grep -qix gd || {
    echo "php8.3-gd is missing — OpenCart exits at startup without it:"
    echo "  sudo apt install php8.3-gd"
    exit 1
}

[[ -f "$PROJECT/public_html/env.local.php" ]] || {
    echo "public_html/env.local.php not found. Create it from the example:"
    echo "  cp public_html/env.local.php.example public_html/env.local.php"
    exit 1
}

# Asset and image URLs follow OC_URL: this store has no config_url row, and
# catalog/controller/startup/startup.php falls back to HTTP_SERVER without one.
# So set OC_URL in env.local.php to match the port you serve on.
BASE="http://$HOST:$PORT/"

echo "storefront  ${BASE}"
echo "admin       ${BASE}admin/"
echo "seller      ${BASE}seller-cp/"
echo

exec php8.3 -c "$PROJECT/.deploy/php/99-opencart.ini" \
    -S "$HOST:$PORT" \
    -t "$PROJECT/public_html" \
    "$PROJECT/.deploy/router.php"
