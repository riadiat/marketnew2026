#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu 24.04 droplet for ewmarket.sa.
# Run as root on the droplet, from inside the cloned project.
#
#   sudo DOMAIN=new.ewmarket.sa \
#        DB_HOST=... DB_PORT=25060 DB_USER=doadmin DB_PASS=... DB_NAME=ewmarket \
#        DB_SSL_CA=/etc/ssl/certs/do-mysql-ca.crt \
#        ./scripts/server-bootstrap.sh
#
# Idempotent. Does not touch the database contents or the images — those are
# uploaded separately (see docs/DIGITALOCEAN.md).

set -euo pipefail

PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOMAIN="${DOMAIN:?set DOMAIN}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DB_HOST="${DB_HOST:?set DB_HOST}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:?set DB_USER}"
DB_PASS="${DB_PASS:?set DB_PASS}"
DB_NAME="${DB_NAME:?set DB_NAME}"
DB_SSL_CA="${DB_SSL_CA:-}"
SCHEME="${SCHEME:-http}"   # certbot flips this to https later

[[ $EUID -eq 0 ]] || { echo "run with sudo"; exit 1; }

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

# ---------------------------------------------------------------- packages
say "Packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y software-properties-common curl unzip rsync fail2ban ufw
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
apt-get update -qq
apt-get install -y \
    apache2 \
    php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl php8.3-zip \
    php8.3-mbstring php8.3-intl php8.3-xml php8.3-soap php8.3-opcache \
    mysql-client certbot python3-certbot-apache

a2enmod -q rewrite proxy_fcgi setenvif headers expires ssl
a2dissite -q 000-default 2>/dev/null || true

# ---------------------------------------------------------------- php
say "PHP settings"
for sapi in fpm cli; do
    install -m 644 "$PROJECT/.deploy/php/99-opencart.ini" "/etc/php/8.3/$sapi/conf.d/99-opencart.ini"
done
# Production: never render errors to visitors. The store's own
# config_error_display setting is handled separately, in the database.
sed -i 's/^display_errors.*/display_errors = Off/' /etc/php/8.3/fpm/conf.d/99-opencart.ini

# ---------------------------------------------------------------- deploy user
say "Deploy user"
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG www-data "$DEPLOY_USER"

# Lets the deploy script reload PHP-FPM without a password, and nothing else.
cat > /etc/sudoers.d/deploy <<EOF
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm
EOF
chmod 440 /etc/sudoers.d/deploy
visudo -cf /etc/sudoers.d/deploy >/dev/null

# ---------------------------------------------------------------- db ca
if [[ -n "$DB_SSL_CA" && ! -f "$DB_SSL_CA" ]]; then
    echo "DB_SSL_CA points at $DB_SSL_CA which does not exist."
    echo "Download the CA certificate from the DigitalOcean database page first."
    exit 1
fi

# ---------------------------------------------------------------- vhost
say "Apache vhost"
CONF="/etc/apache2/sites-available/$DOMAIN.conf"
sed -e "s#__DOMAIN__#$DOMAIN#g" \
    -e "s#__PROJECT__#$PROJECT#g" \
    -e "s#__SCHEME__#$SCHEME#g" \
    -e "s#__DB_HOST__#$DB_HOST#g" \
    -e "s#__DB_PORT__#$DB_PORT#g" \
    -e "s#__DB_USER__#$DB_USER#g" \
    -e "s#__DB_PASS__#$DB_PASS#g" \
    -e "s#__DB_NAME__#$DB_NAME#g" \
    -e "s#__DB_SSL_CA__#$DB_SSL_CA#g" \
    "$PROJECT/.deploy/apache/production.conf.template" > "$CONF"
# The vhost holds the database password.
chmod 640 "$CONF"
a2ensite -q "$DOMAIN"
apache2ctl configtest

# ---------------------------------------------------------------- ownership
say "Ownership and permissions"
chown -R "$DEPLOY_USER:www-data" "$PROJECT"
find "$PROJECT" -type d -exec chmod 2750 {} \;
find "$PROJECT" -type f -exec chmod 640 {} \;
chmod +x "$PROJECT"/scripts/*.sh
# Only these need to be writable by the web server.
chmod -R 2770 "$PROJECT/storage" \
              "$PROJECT/public_html/system/storage" \
              "$PROJECT/public_html/image"

# ---------------------------------------------------------------- firewall
say "Firewall"
ufw allow OpenSSH >/dev/null
ufw allow 'Apache Full' >/dev/null
ufw --force enable >/dev/null
ufw status | head -6

systemctl enable --now fail2ban >/dev/null 2>&1 || true

# ---------------------------------------------------------------- start
say "Restarting services"
systemctl restart php8.3-fpm
systemctl reload apache2

say "Done"
echo "  vhost   $CONF  (chmod 640 — it holds the DB password)"
echo "  site    $SCHEME://$DOMAIN/"
echo
echo "Next:"
echo "  1. upload the database and public_html/image/catalog/"
echo "  2. sudo certbot --apache -d $DOMAIN"
echo "  3. add the GitHub deploy secrets"
