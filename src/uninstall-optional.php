<?php

declare(strict_types=1);

/**
 * @package Ultimate Menu mod
 * @version   1.1.0
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