<?php
// Router for `php -S`, mirroring public_html/.htaccess.
// Used by scripts/serve.sh — irrelevant under Apache or nginx.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public_html' . $path;

// Serve real files directly (images, css, js, and the three index.php entries).
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
	return false;
}

// Every index.php looks for its config.php by relative path, which under
// Apache resolves against the script's own directory. `php -S` keeps the
// launch directory instead, so chdir before handing over — otherwise the
// config is never found and OpenCart redirects to the installer.

// Admin and seller panel are separate applications with their own front
// controllers; hand them their own index.php rather than the storefront's.
foreach (array('/admin', '/seller-cp') as $app) {
	if ($path === $app || strpos($path, $app . '/') === 0) {
		$_SERVER['SCRIPT_NAME'] = $app . '/index.php';
		chdir(__DIR__ . '/../public_html' . $app);
		require 'index.php';
		return true;
	}
}

// Everything else is an SEO route for the storefront.
if ($path !== '/' && !isset($_GET['route'])) {
	$_GET['_route_'] = ltrim($path, '/');
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir(__DIR__ . '/../public_html');
require 'index.php';
return true;
