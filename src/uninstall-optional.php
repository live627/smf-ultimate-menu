<?php

declare(strict_types=1);

/**
 * @package Ultimate Menu mod
 * @version   2.0.3
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and SMF isn't defined...
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF'))
	require_once __DIR__ . '/SSI.php';

// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot uninstall - please verify you put this in the same place as SMF\'s index.php.');

global $settings, $smcFunc, $modSettings;

if (isset($modSettings['um_menu']))
	unset($modSettings['um_menu']);

if (isset($modSettings['um_count']))
	unset($modSettings['um_count']);

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable = {string:setting0}
		OR variable = {string:setting1}
		OR variable LIKE {string:setting2}',
	[
		'setting0' => 'um_menu',
		'setting1' => 'um_count',
		'setting2' => 'um_button%',
	]
);

um_deleteIconsPath($settings['default_theme_dir'] . '/images/um_icons');

function um_deleteIconsPath($directory)
{
	global $settings;

	clearstatcache();
	$um_icons_dir = rtrim(str_replace('\\', '/', $settings['default_theme_dir'] . '/images/um_icons'), '/\\');
	$directory = rtrim(str_replace('\\', '/', $directory), '/\\');
	if (!str_starts_with($directory, $um_icons_dir))
		return false;

	if (is_dir($directory))
	{
		$directoryHandle = opendir($directory);
		while ($contents = readdir($directoryHandle))
		{
			if(!in_array($contents, ['.', '..']))
			{
				$path = $directory . "/" . $contents;
				if (is_dir($path))
					um_deleteIconsPath($path);
				elseif (file_exists($path))
					@unlink($path);
			}
		}
		closedir($directoryHandle);
		clearstatcache();
		rmdir($directory);
	}
	elseif (file_exists($directory))
		@unlink($directory);
	else
		return false;

	clearstatcache();
	return true;
}
