<?php
// ---------------------------------------------------------------------------
// Seller control panel configuration. See ../config.php for how paths/env work.
//
// Note: unlike catalog/admin, the seller panel keeps its storage under
// public_html/system/storage/ (separate cache/session/log dirs, "-seller"
// suffixed), which is how this install has always been wired.
// ---------------------------------------------------------------------------

require_once(dirname(__DIR__) . '/env.php');

$root = dirname(__DIR__, 2) . '/';        // .../ewmarket.sa/
$base = dirname(__DIR__) . '/';           // .../ewmarket.sa/public_html/

$url = oc_env('OC_URL', 'https://ewmarket.sa/');

// HTTP
define('HTTP_SERVER', $url . 'seller-cp/');
define('HTTP_CATALOG', $url);

// HTTPS
define('HTTPS_SERVER', $url . 'seller-cp/');
define('HTTPS_CATALOG', $url);

// DIR
define('DIR_APPLICATION', $base . 'seller-cp/');
define('DIR_SYSTEM', $base . 'system/');
define('DIR_IMAGE', $base . 'image/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_CATALOG', $base . 'catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache-seller/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download-seller/');
define('DIR_LOGS', DIR_STORAGE . 'logs-seller/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification-seller/');
define('DIR_SESSION', DIR_STORAGE . 'session-seller/');
define('DIR_UPLOAD', DIR_STORAGE . 'public_html/');

define('DIR_UPLOAD_DIR', $root . 'storage/upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', oc_env('DB_HOSTNAME', 'localhost'));
define('DB_USERNAME', oc_env('DB_USERNAME', 'ewmarket'));
define('DB_PASSWORD', oc_env('DB_PASSWORD', ''));
define('DB_DATABASE', oc_env('DB_DATABASE', 'ewmarket'));
define('DB_PORT', oc_env('DB_PORT', '3306'));
define('DB_PREFIX', 'oc_');

// Path to a CA bundle, when the database requires a verified TLS connection
// (DigitalOcean Managed MySQL and friends). Empty means plain connection.
define('DB_SSL_CA', oc_env('DB_SSL_CA', ''));
