<?php
// ---------------------------------------------------------------------------
// Admin panel configuration. See ../config.php for how paths/env work.
// ---------------------------------------------------------------------------

require_once(dirname(__DIR__) . '/env.php');

$root = dirname(__DIR__, 2) . '/';        // .../ewmarket.sa/
$base = dirname(__DIR__) . '/';           // .../ewmarket.sa/public_html/

$url = oc_env('OC_URL', 'https://ewmarket.sa/');

// HTTP
define('HTTP_SERVER', $url . 'admin/');
define('HTTP_CATALOG', $url);

// HTTPS
define('HTTPS_SERVER', $url . 'admin/');
define('HTTPS_CATALOG', $url);

// DIR
define('DIR_APPLICATION', $base . 'admin/');
define('DIR_APPLICATION_SELLER', $base . 'seller-cp/');
define('DIR_SYSTEM', $base . 'system/');
define('DIR_IMAGE', $base . 'image/');
define('DIR_STORAGE', $root . 'storage/');
define('DIR_CATALOG', $base . 'catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
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

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
