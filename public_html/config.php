<?php
// ---------------------------------------------------------------------------
// Catalog (storefront) configuration.
//
// Paths are derived from this file's location, so the project runs from any
// directory. URL and DB credentials come from the vhost / environment — see
// env.php for the lookup order.
// ---------------------------------------------------------------------------

require_once(__DIR__ . '/env.php');

$root = dirname(__DIR__) . '/';          // .../ewmarket.sa/
$base = __DIR__ . '/';                    // .../ewmarket.sa/public_html/

$url = oc_env('OC_URL', 'https://ewmarket.sa/');

// HTTP
define('HTTP_SERVER', $url);

// HTTPS
define('HTTPS_SERVER', $url);

// DIR
define('DIR_APPLICATION', $base . 'catalog/');
define('DIR_SYSTEM', $base . 'system/');
define('DIR_IMAGE', $base . 'image/');
define('DIR_STORAGE', $root . 'storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', oc_env('DB_HOSTNAME', 'localhost'));
define('DB_USERNAME', oc_env('DB_USERNAME', 'ewmarket'));
define('DB_PASSWORD', oc_env('DB_PASSWORD', ''));
define('DB_DATABASE', oc_env('DB_DATABASE', 'ewmarket'));
define('DB_PORT', oc_env('DB_PORT', '3306'));
define('DB_PREFIX', 'oc_');
