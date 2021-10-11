<?php

/**
 * @package Ultimate Menu mod
 * @version   1.1.4
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and SMF isn't defined...
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF'))
	require_once __DIR__ . '/SSI.php';

// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

$tables = [
	[
		'name' => 'um_menu',
		'columns' => [
			[
				'name' => 'id_button',
				'type' => 'smallint',
				'size' => 5,
				'unsigned' => true,
				'auto' => true,
			],
			[
				'name' => 'name',
				'type' => 'varchar',
				'size' => 65,
			],
			[
				'name' => 'type',
				'type' => 'enum(\'forum\',\'external\')',
				'default' => 'forum',
			],
			[
				'name' => 'target',
				'type' => 'enum(\'_self\',\'_blank\')',
				'default' => '_self',
			],
			[
				'name' => 'position',
				'type' => 'varchar',
				'size' => 65,
			],
			[
				'name' => 'link',
				'type' => 'varchar',
				'size' => 255,
			],
			[
				'name' => 'status',
				'type' => 'enum(\'active\',\'inactive\')',
				'default' => 'active',
			],
			[
				'name' => 'permissions',
				'type' => 'varchar',
				'size' => 255,
			],
			[
				'name' => 'parent',
				'type' => 'varchar',
				'size' => 65,
			],
		],
		'indexes' => [
			[
				'type' => 'primary',
				'columns' => ['id_button']
			],
		]
	]
];

foreach ($tables as $table)
{
	$smcFunc['db_create_table']('{db_prefix}' . $table['name'], $table['columns'], $table['indexes'], [], 'update');

	if (isset($table['default']))
		$smcFunc['db_insert']('ignore', '{db_prefix}' . $table['name'], $table['default']['columns'], $table['default']['values'], $table['default']['keys']);
}

$request = $smcFunc['db_query']('', '
	SELECT id_button, name, target, type, position, link, status, permissions, parent
	FROM {db_prefix}um_menu');
$buttons = [];

while ($row = $smcFunc['db_fetch_assoc']($request))
	$buttons['um_button_' . $row['id_button']] = json_encode($row);
$smcFunc['db_free_result']($request);
updateSettings(
	[
		'um_count' => count($buttons),
	] + $buttons
);

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable = {string:setting}',
	[
		'setting' => 'um_menu',
	]
);

// Now presenting... *drumroll*
add_integration_function('integrate_pre_include', '$sourcedir/Subs-UltimateMenu.php');
add_integration_function('integrate_menu_buttons', 'um_load_menu');
add_integration_function('integrate_admin_areas', 'um_admin_areas');