<?php

declare(strict_types=1);

/**
 * @package Ultimate Menu mod
 * @version   2.0.3
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and SMF isn't defined...
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF')) {
	require_once __DIR__ . '/SSI.php';
} elseif (!defined('SMF')) {
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

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
				'type' => 'text',
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
			[
				'name' => 'icon',
				'type' => 'varchar',
				'size' => 191,
				'default' => '',
			],
			[
				'name' => 'sprite',
				'type' => 'tinyint',
				'size' => 1,
				'default' => 0,
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

foreach ($tables as $table) {
	$smcFunc['db_create_table'](
		'{db_prefix}' . $table['name'],
		$table['columns'],
		$table['indexes'],
		[],
		'ignore'
	);

	if (isset($table['default'])) {
		$smcFunc['db_insert'](
			'ignore',
			'{db_prefix}' . $table['name'],
			$table['default']['columns'],
			$table['default']['values'],
			$table['default']['keys']
		);
	}
}

if (!checkFieldExistsUMInstaller('um_menu', 'icon')) {
	$smcFunc['db_add_column']('{db_prefix}um_menu', [
			'name' => 'icon',
			'type' => 'varchar',
			'size' => 191,
			'default' => '',
		],
		[],
		false
	);
}

if (!checkFieldExistsUMInstaller('um_menu', 'sprite')) {
	$smcFunc['db_add_column']('{db_prefix}um_menu', [
			'name' => 'sprite',
			'type' => 'tinyint',
			'size' => 1,
			'default' => 0,
		],
		[],
		false
	);
}

// update link column to text to facilitate elongated hyperlinks
if (checkFieldExistsUMInstaller('um_menu', 'link')) {
	$checkUmTable = $smcFunc['db_list_columns']('{db_prefix}um_menu', true);
	if (!empty($checkUmTable) && !empty($checkUmTable['link']) && $checkUmTable['link']['type'] != 'text') {
		$adjust = array(
			'name' => 'link',
			'type' => 'text',
			'default' => null,
		);

		$smcFunc['db_change_column']('{db_prefix}um_menu', 'link', $adjust);
	}
}

$buttons = [];
$request = $smcFunc['db_query']('', '
	SELECT
		id_button, name, target, type, position, link, status, permissions, parent, icon, sprite
	FROM {db_prefix}um_menu'
);

while ($row = $smcFunc['db_fetch_assoc']($request)) {
	$buttons['um_button_' . $row['id_button']] = json_encode([
		'name' => $row['name'],
		'target' => $row['target'],
		'type' => $row['type'],
		'position' => $row['position'],
		'groups' => array_map('intval', explode(',', $row['permissions'])),
		'link' => $row['link'],
		'active' => $row['status'] == 'active',
		'parent' => $row['parent'],
		'icon' => !empty($row['icon']) ? $row['icon'] : '',
		'sprite' => !empty($row['sprite']) ? 1 : 0,
	]);
}
$smcFunc['db_free_result']($request);

if (!empty($buttons)) {
	$request = $smcFunc['db_query']('', '
		SELECT MAX(id_button)
		FROM {db_prefix}um_menu'
	);
	[$max] = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}settings
		WHERE variable LIKE {string:settings_search}
			AND variable NOT IN ({array_string:settings})',
		[
			'settings_search' => 'um_button%',
			'settings' => array_keys($buttons),
		]
	);
	updateSettings(['um_count' => $max] + $buttons);
}

// Now presenting... *drumroll*
updateSettings(['um_fingerprint' => mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8')]);
add_integration_function('integrate_pre_include', '$sourcedir/Subs-UltimateMenu.php');
add_integration_function('integrate_menu_buttons', 'um_load_menu');
add_integration_function('integrate_admin_areas', 'um_admin_areas');

function check_table_existsUMInstaller($table)
{
	global $db_prefix, $smcFunc;

	if ($smcFunc['db_list_tables'](false, $db_prefix . $table)) {
		return true;
	}

	return false;
}

function checkFieldExistsUMInstaller($tableName, $columnName)
{
	global $smcFunc;
	if (check_table_existsUMInstaller($tableName)) {
		$check = $smcFunc['db_list_columns'] ('{db_prefix}' . $tableName, false, []);
		if (in_array($columnName, $check)) {
			return true;
		}
	}

	return false;
}
