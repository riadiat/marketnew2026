<?php
// ---------------------------------------------------------------------------
// Shared config helper for the three config.php files.
//
// Settings are looked up in this order:
//
//   1. $_SERVER  — Apache `SetEnv` / nginx `fastcgi_param`, per vhost.
//                  This is the one that works under PHP-FPM, whose default
//                  `clear_env = yes` wipes the process environment.
//   2. getenv()  — real process environment (Docker, FPM pool `env[]`).
//   3. env.local.php — a gitignored array, for when editing the vhost is not
//                  an option. Overridden by both of the above.
//   4. the default passed by the caller.
// ---------------------------------------------------------------------------

if (!function_exists('oc_env')) {
	function oc_env($key, $default = null) {
		static $local = null;

		if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
			return $_SERVER[$key];
		}

		$value = getenv($key);

		if ($value !== false && $value !== '') {
			return $value;
		}

		if ($local === null) {
			$file  = __DIR__ . '/env.local.php';
			$local = is_file($file) ? (array)include $file : array();
		}

		if (isset($local[$key]) && $local[$key] !== '') {
			return $local[$key];
		}

		return $default;
	}
}
