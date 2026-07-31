# Deploying to a PHP 8.3 server

Read [PHP83-NOTES.md](PHP83-NOTES.md) first — this codebase only runs on 8.3
because of the fixes listed there. Do not overwrite them with an upstream copy.

**Before going live, resolve [SECURITY.md](SECURITY.md).** Five obfuscated PHP
files were found in the handover snapshot; two have been replaced, three still
need the vendor's confirmation.

## 1. Server packages

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl \
  php8.3-zip php8.3-mbstring php8.3-intl php8.3-xml php8.3-soap php8.3-opcache \
  nginx mysql-server
```

`php8.3-gd` is mandatory — OpenCart aborts on startup without it.

## 2. PHP settings

`/etc/php/8.3/fpm/conf.d/99-opencart.ini`:

```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
max_input_vars = 5000
display_errors = Off
log_errors = On
opcache.enable = 1
opcache.memory_consumption = 192
opcache.max_accelerated_files = 20000
```

`public_html/php.ini` in the repo is a leftover from a shared-hosting setup and
is ignored under nginx + PHP-FPM. It still names `magic_quotes_gpc`,
`register_globals`, and `safe_mode` — all removed from PHP years ago.

## 3. Code

```bash
git clone <repo-url> /var/www/ewmarket.sa
cd /var/www/ewmarket.sa
```

Then copy the two excluded pieces from the old server:

```bash
rsync -az old-server:/home/forge/ewmarket.sa/public_html/image/catalog/ \
          /var/www/ewmarket.sa/public_html/image/catalog/
scp old-server:/path/to/ewmarket_08_01_2024.sql ./database/
```

## 4. Database

```bash
mysql -e "CREATE DATABASE ewmarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'ewmarket'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"
mysql -e "GRANT ALL ON ewmarket.* TO 'ewmarket'@'localhost';"
mysql ewmarket < database/ewmarket_08_01_2024.sql
```

The dump is MySQL 8.0 and mostly **MyISAM** — no transactions, no foreign keys,
table-level locking under write load. Converting to InnoDB is worth doing, but
test it separately; it is not required to go live.

```sql
-- after conversion, one table at a time:
ALTER TABLE oc_order ENGINE=InnoDB;
```

## 5. Configuration

`config.php` is gitignored, so create the three files on the server, or supply
the values as environment variables (`fastcgi_param` / `pool.d` `env[]`):

```
OC_URL=https://ewmarket.sa/
DB_HOSTNAME=localhost
DB_USERNAME=ewmarket
DB_PASSWORD=STRONG_PASSWORD
DB_DATABASE=ewmarket
DB_PORT=3306
```

Then point the stored URLs at the new domain:

```sql
UPDATE oc_setting SET value='https://ewmarket.sa/' WHERE `key` IN ('config_url','config_ssl');
UPDATE oc_setting SET value='0' WHERE `key`='config_error_display';
```

`config_error_display` is currently **1** in the dump. Leave it on and PHP
warnings render into the storefront HTML for customers to see.

## 6. Permissions

```bash
sudo chown -R www-data:www-data /var/www/ewmarket.sa
sudo find /var/www/ewmarket.sa -type d -exec chmod 755 {} \;
sudo find /var/www/ewmarket.sa -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage public_html/system/storage public_html/image
```

The handover snapshot is `777` throughout. Do not carry that over.

## 7. nginx

```nginx
server {
    listen 443 ssl http2;
    server_name ewmarket.sa www.ewmarket.sa;
    root /var/www/ewmarket.sa/public_html;
    index index.php;

    client_max_body_size 64M;

    # storage lives outside the docroot, but block the in-tree copy too
    location ~ ^/system/storage/ { deny all; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(tpl|twig|ini|log)$ { deny all; }

    # seller vanity URLs: /username -> seller page  (from .htaccess)
    location ~ "^/([-a-zA-Z0-9\s]+)$" {
        try_files $uri $uri/ /index.php?route=seller/seller&username=$1&$args;
    }

    location / {
        try_files $uri $uri/ /index.php?_route_=$uri&$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 300;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2?)$ {
        expires 30d;
        access_log off;
    }
}
```

Staying on Apache instead? `public_html/.htaccess` already covers all of this —
just enable `mod_rewrite` and `AllowOverride All`.

## 8. Verify

```bash
curl -sI https://ewmarket.sa/ | head -1
curl -s https://ewmarket.sa/ | grep -c "Warning\|Fatal error"     # expect 0
curl -sI https://ewmarket.sa/image/cache/catalog/ -o /dev/null -w '%{http_code}\n'
tail -f storage/logs/error.log
```

Check in the admin panel that image thumbnails render — that is the fastest
signal that GD and the `image.php` fix are both live.

## Payment gateways — action required

`oc_extension` registers **`tamarapay`** (Tamara) and **`hyperpay_tabby`**
(HyperPay / Tabby) as installed payment methods, but **neither extension's PHP
files exist anywhere in this snapshot** — only `admin/view/image/payment/hyperpay.png`
survived. Checkout will not offer them, and the admin extension page will error
if you open their settings.

Either obtain those extension packages from whoever installed them, or remove
the rows and reinstall:

```sql
DELETE FROM oc_extension WHERE code IN ('tamarapay','hyperpay_tabby');
```

`payfort_fort*` (Amazon Payment Services) controllers **are** present in the
code but are not registered in `oc_extension` — a leftover from an earlier
integration. `storage/logs/payfort_fort.log` last recorded activity in 2022.

Registered and present, so these work as-is: `bank_transfer`, `pp_standard`,
`free_checkout`, `cod_fee`, and the custom `riyadh` shipping method.
