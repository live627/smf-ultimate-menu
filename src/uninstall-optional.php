<?php

declare(strict_types=1);

/**
 * @package Ultimate Menu mod
 * @version   2.0.5
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and SMF isn't defined...
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF')) {
	require_once __DIR__ . '/SSI.php';
} elseif (!defined('SMF')) {
	die('<b>Error:</b> Cannot uninstall - please verify you put this in the same place as SMF\'s index.php.');
}
global $settings, $smcFunc, $modSettings;

list($where, $umButtons, $allUmModSettings) = [
	'',
	array_keys(array_filter($modSettings, fn($key) => str_starts_with($key, 'um_button_'), ARRAY_FILTER_USE_KEY)),
	[
		'setting0' => 'um_menu',
		'setting1' => 'um_count',
		'setting2' => 'um_settings',
		'setting3' => 'um_button%',
	]
];
array_walk($allUmModSettings, function($value, $key) use (&$where, &$modSettings) {
	$where .= (!$where ? ' ' : ' OR ') . 'variable ' . (strpos($value, '%') === false ? '=' : 'LIKE') . ' {string:' . $key . '}';
	if (isset($modSettings[$value]) && strpos($value, '%') === false) {
		unset($modSettings[$value]);
	}
});
array_walk($umButtons, function($value, $key) use (&$modSettings) {
	unset($modSettings[$key]);
});

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE' . $where,
	$allUmModSettings
);

um_deleteIconsPath($settings['default_theme_dir'] . '/images/um_icons');
foreach (['ultimate-menu-buttons.css', 'ultimate-menu-buttons.min.css'] as $file) {
	if (file_exists($settings['default_theme_dir'] . '/css/' . $file)) {
		unlink($settings['default_theme_dir'] . '/css/' . $file);
	}
}
clearstatcache();

function um_deleteIconsPath($directory)
{
	global $settings;

	clearstatcache();
	$um_icons_dir = rtrim(str_replace('\\', '/', $settings['default_theme_dir'] . '/images/um_icons'), '/\\');
	$directory = rtrim(str_replace('\\', '/', $directory), '/\\');
	if (!str_starts_with($directory, $um_icons_dir)) {
		return false;
	}

	if (is_dir($directory)) {
		$directoryHandle = opendir($directory);
		while ($contents = readdir($directoryHandle)) {
			if (!in_array($contents, ['.', '..'])) {
				$path = $directory . "/" . $contents;
				if (is_dir($path)) {
					um_deleteIconsPath($path);
				} elseif (file_exists($path)) {
					unlink($path);
				}
			}
		}
		closedir($directoryHandle);
		clearstatcache();
		rmdir($directory);
	} elseif (file_exists($directory)) {
		unlink($directory);
	} else {
		return false;
	}

	clearstatcache();
	return true;
}
