# Running OpenCart 3.0.2.0 on PHP 8.3

OpenCart 3.0.2.0 shipped in 2017 and officially supports PHP 5.4–7.1. Upstream
never released a PHP 8 compatible 3.0.2.x. Everything below was needed to get
this install serving pages on 8.3.

Verified working on PHP 8.3.32 + MySQL 8.0: storefront, category, search, cart,
login, admin (dashboard/products/orders/customers/extensions/settings), seller
panel login, and GD thumbnail generation.

## Changes applied

### 1. GD images — `system/library/image.php`

The single most damaging break. PHP 8.0 turned GD resources into `GdImage`
objects, so `Image::save()`'s `is_resource($this->image)` guard was always
false and **no thumbnail was ever written**. Every product image 404s.

```php
if ($this->image instanceof \GdImage || is_resource($this->image)) {
```

### 2. Error handlers — `system/framework.php` + `{catalog,admin,seller-cp}/controller/startup/error.php`

All four handlers used `if (error_reporting() === 0)` to detect the `@`
operator. PHP 8 no longer zeroes the mask, so that check never fired and every
`E_DEPRECATED` was `echo`ed straight into the response — before the session
cookie and any redirect header. That produced `headers already sent` warnings
and broke logins.

```php
if (!(error_reporting() & $code)) {
    return false;
}
```

`E_DEPRECATED` / `E_USER_DEPRECATED` were also added to the label switch, which
previously reported them as `Unknown`.

### 3. Deprecation mask — `system/startup.php`

`error_reporting(E_ALL)` was hardcoded, overriding php.ini. A 2017 codebase on
8.3 emits hundreds of `E_DEPRECATED` per request (dynamic properties, null
passed to string parameters) — none of it actionable.

```php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
```

### 4. mysqli — `system/library/db/mysqli.php`

PHP 8.1 made mysqli throw `mysqli_sql_exception` by default. This driver checks
`errno` / `connect_error` by hand, so exceptions bypassed its error handling.

```php
mysqli_report(MYSQLI_REPORT_OFF);
```

### 5. scssphp — `storage/vendor/scss.inc.php` (and the copy under `public_html/system/storage/vendor/`)

`leafo/scssphp` 0.0.12 read `$key[1]` on positional args, where `$key` is null.
PHP 8 warns on that; the admin recompiles SCSS on every page load, so it fired
dozens of times per request.

```php
$key = is_array($key) ? $key[1] : null;
```

### 6. Portable paths — the three `config.php` files

Paths were hardcoded to `/home/forge/ewmarket.sa/...` (Laravel Forge). Now
derived from `__DIR__`, with URL and DB credentials read from the environment.

## Known-broken, not yet fixed

These do not affect the pages exercised so far, but will fatal if reached.

| File | Problem |
|---|---|
| `catalog/model/extension/payment/pp_express.php:330` | `{}` string offset — **fatal parse error** on PHP 8. PayPal Express is not enabled, so it is never loaded. |
| `storage/vendor/braintree/.../Digest.php:53` | same `{}` syntax. Braintree not enabled. |
| `storage/vendor/symfony/validator/Constraints/{True,False,Null}.php` | class names reserved since PHP 7. Pulled in by the Cardinity SDK only. |
| `storage/vendor/cardinity/.../Method/Void/Void.php` | `Void` reserved. Cardinity not enabled. |
| `catalog/controller/extension/credit_card/sagepay_*.php` | `strftime()` removed in PHP 8.1. SagePay not enabled. |

A full `php -l` sweep of all 4,627 PHP files found **no other syntax errors** —
the rest of the codebase parses cleanly on 8.3.

Twig 1.24.2 emits `E_DEPRECATED` for `Twig_Node::count()` / `getIterator()`
return types and for `Twig_Autoloader`. Masked, and harmless until PHP 9.

Do not run `composer install`. The pinned 2017 packages do not resolve against
PHP 8.3, and the committed `vendor/` trees carry the fixes above.

## Required PHP extensions

`gd` `mysqli` `curl` `zip` `mbstring` `intl` `xml` `dom` `simplexml` `openssl`
`json` `fileinfo` `exif` `soap` `opcache`

`gd` is not optional — OpenCart `exit()`s on startup without it.
