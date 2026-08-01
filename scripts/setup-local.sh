#!/usr/bin/env bash
# Set up ewmarket.sa to run natively on Apache + PHP-FPM 8.3 + MySQL.
# Idempotent — safe to re-run.
#
#   sudo ./scripts/setup-local.sh
#
# If the MySQL root account has a password rather than socket auth:
#   sudo MYSQL_ROOT_PASS=secret ./scripts/setup-local.sh
#
# Skip the database import (much faster on a re-run):
#   SKIP_DB=1 sudo ./scripts/setup-local.sh

set -euo pipefail

PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOMAIN="${DOMAIN:-ewmarket.localhost}"
DB_NAME="${DB_NAME:-ewmarket}"
DB_USER="${DB_USER:-ewmarket}"
# Satisfies MySQL's validate_password MEDIUM policy (8+ chars, mixed case,
# digit, symbol), which is on by default in several distro packages.
DB_PASS="${DB_PASS:-Ewm@rket2026}"
DUMP="${DUMP:-$PROJECT/database/ewmarket_08_01_2024.sql}"
MYSQL_ROOT_USER="${MYSQL_ROOT_USER:-root}"
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-}"

[[ $EUID -eq 0 ]] || { echo "run with sudo"; exit 1; }

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

# Admin connection. Ubuntu's default root@localhost is socket auth, but a root
# with a password is just as common — pass MYSQL_ROOT_PASS for that. Credentials
# go through a 0600 defaults-file so they never appear in `ps` output.
MYSQL_CNF=""
cleanup() { [[ -n "$MYSQL_CNF" ]] && rm -f "$MYSQL_CNF"; }
trap cleanup EXIT

if [[ -n "$MYSQL_ROOT_PASS" ]]; then
    MYSQL_CNF="$(mktemp)"
    chmod 600 "$MYSQL_CNF"
    printf '[client]\nuser=%s\npassword=%s\n' "$MYSQL_ROOT_USER" "$MYSQL_ROOT_PASS" > "$MYSQL_CNF"
    mysql_admin() { mysql --defaults-file="$MYSQL_CNF" "$@"; }
else
    mysql_admin() { mysql "$@"; }
fi

# ---------------------------------------------------------------- packages
say "PHP 8.3 extensions"
# gd is not optional: OpenCart exit()s at startup without it.
# intl and soap are unused by core but pulled in by some shipping extensions.
# Package name -> the name `php -m` actually prints for it.
declare -A EXT=(
    [gd]=gd  [mysql]=mysqli  [curl]=curl  [zip]=zip  [mbstring]=mbstring
    [xml]=xml  [intl]=intl  [soap]=soap  [opcache]="Zend OPcache"
)
MISSING=()
for pkg in "${!EXT[@]}"; do
    php8.3 -m | grep -qix "${EXT[$pkg]}" || MISSING+=("php8.3-$pkg")
done
if [[ ${#MISSING[@]} -gt 0 ]]; then
    echo "installing: ${MISSING[*]}"
    apt-get update -qq
    apt-get install -y "${MISSING[@]}"
else
    echo "all present"
fi

# ---------------------------------------------------------------- php.ini
say "PHP settings"
for sapi in fpm cli; do
    install -m 644 "$PROJECT/.deploy/php/99-opencart.ini" "/etc/php/8.3/$sapi/conf.d/99-opencart.ini"
done
echo "wrote /etc/php/8.3/{fpm,cli}/conf.d/99-opencart.ini"

# ---------------------------------------------------------------- database
if [[ "${SKIP_DB:-0}" != "1" ]]; then
    say "Database"
    if ! mysql_admin -e "SELECT 1" >/dev/null 2>&1; then
        echo "cannot connect to MySQL as '$MYSQL_ROOT_USER'."
        echo "if that account has a password, re-run with:"
        echo "  sudo MYSQL_ROOT_PASS=... $0"
        exit 1
    fi
    mysql_admin <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost', '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
    if [[ $(mysql_admin -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'") -lt 100 ]]; then
        [[ -f "$DUMP" ]] || { echo "dump not found: $DUMP"; exit 1; }
        echo "importing $(du -h "$DUMP" | cut -f1) — takes a few minutes"
        mysql_admin "$DB_NAME" < "$DUMP"
    else
        echo "already populated, skipping import"
    fi
    # No config_url/config_ssl rows exist in this store — startup.php falls
    # back to HTTP_SERVER, i.e. OC_URL from the vhost. Nothing to repoint.
    mysql_admin "$DB_NAME" -e "UPDATE oc_setting SET value='0' WHERE \`key\`='config_maintenance';"
fi

# ---------------------------------------------------------------- apache
say "Apache vhost"
CONF="/etc/apache2/sites-available/$DOMAIN.conf"
sed -e "s#/mnt/data/freelance/ewmarket.sa#$PROJECT#g" \
    -e "s#ewmarket\.localhost#$DOMAIN#g" \
    -e "s#SetEnv DB_USERNAME \"[^\"]*\"#SetEnv DB_USERNAME \"$DB_USER\"#" \
    -e "s#SetEnv DB_PASSWORD \"[^\"]*\"#SetEnv DB_PASSWORD \"$DB_PASS\"#" \
    -e "s#SetEnv DB_DATABASE \"[^\"]*\"#SetEnv DB_DATABASE \"$DB_NAME\"#" \
    "$PROJECT/.deploy/apache/ewmarket.localhost.conf" > "$CONF"
a2enmod -q rewrite proxy_fcgi setenvif
a2ensite -q "$DOMAIN"
grep -q "$DOMAIN" /etc/hosts || echo "127.0.0.1 $DOMAIN" >> /etc/hosts
echo "wrote $CONF"

# ---------------------------------------------------------------- writable
say "Permissions"
# Apache/FPM run as www-data and must write cache, sessions, logs and images.
chgrp -R www-data "$PROJECT/storage" \
                  "$PROJECT/public_html/system/storage" \
                  "$PROJECT/public_html/image"
chmod -R g+rwX    "$PROJECT/storage" \
                  "$PROJECT/public_html/system/storage" \
                  "$PROJECT/public_html/image"
echo "storage/ and image/ are group-writable by www-data"

# ---------------------------------------------------------------- restart
say "Restarting services"
systemctl restart php8.3-fpm
systemctl reload apache2

# ---------------------------------------------------------------- verify
say "Verifying"
sleep 2
BODY="$(curl -sS --max-time 30 "http://$DOMAIN/" || true)"
CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 30 "http://$DOMAIN/" || echo 000)"

fail() { printf '\033[1;31m  FAIL\033[0m  %s\n' "$1"; FAILED=1; }
ok()   { printf '\033[1;32m  ok\033[0m    %s\n' "$1"; }
FAILED=0

[[ "$CODE" == 200 ]] && ok "storefront returns 200" || fail "storefront returned HTTP $CODE"

# The installer redirect means config.php never saw the DB settings — most
# likely SetEnv is not reaching $_SERVER. Fall back to env.local.php.
if grep -qi 'install/index.php' <<<"$BODY"; then
    fail "redirected to the installer — vhost SetEnv did not reach PHP."
    echo "        cp public_html/env.local.php.example public_html/env.local.php"
    echo "        and put the DB settings there instead."
fi

grep -qE '<b>(Warning|Fatal error|Unknown)</b>' <<<"$BODY" \
    && fail "PHP warnings rendered into the page — check storage/logs/error.log" \
    || ok "no PHP warnings in the response"

THUMB="$(grep -oE "http://$DOMAIN/image/cache/[^\"]+\.(jpg|png|jpeg)" <<<"$BODY" | head -1)"
if [[ -n "$THUMB" ]]; then
    [[ "$(curl -sS -o /dev/null -w '%{http_code}' "$THUMB")" == 200 ]] \
        && ok "GD thumbnails generate" \
        || fail "thumbnail 404 — is php8.3-gd installed and FPM restarted?"
fi

[[ $FAILED -eq 0 ]] || { echo; echo "setup finished with problems, see above"; exit 1; }

say "Done — http://$DOMAIN/"
echo "  admin  http://$DOMAIN/admin/"
echo "  seller http://$DOMAIN/seller-cp/"
echo
echo "Set a local admin password with:"
echo "  ./scripts/set-admin-password.sh <password>"
