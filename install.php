<?php

/**
 * @package Ultimate Menu mod
 * @version 1.0.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and SMF isn't defined...
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

$tables = array(
	array(
		'name' => 'um_menu',
		'columns' => array(
			array(
				'name' => 'id_button',
				'type' => 'smallint',
				'size' => 5,
				'unsigned' => true,
				'auto' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'size' => 65,
			),
			array(
				'name' => 'slug',
				'type' => 'varchar',
				'size' => 80,
			),
			array(
				'name' => 'type',
				'type' => 'enum(\'forum\',\'external\')',
				'default' => 'forum',
			),
			array(
				'name' => 'target',
				'type' => 'enum(\'_self\',\'_blank\')',
				'default' => '_self',
			),
			array(
				'name' => 'position',
				'type' => 'varchar',
				'size' => 65,
			),
			array(
				'name' => 'link',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'status',
				'type' => 'enum(\'active\',\'inactive\')',
				'default' => 'active',
			),
			array(
				'name' => 'permissions',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'parent',
				'type' => 'varchar',
				'size' => 65,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_button')
			),
		)
	)
);

db_extend('packages');

foreach ($tables as $table)
{
	$smcFunc['db_create_table']('{db_prefix}' . $table['name'], $table['columns'], $table['indexes'], array(), 'update');

	if (isset($table['default']))
		$smcFunc['db_insert']('ignore', '{db_prefix}' . $table['name'], $table['default']['columns'], $table['default']['values'], $table['default']['keys']);
}

// Now presenting... *drumroll*
add_integration_function('integrate_pre_include', '$sourcedir/Subs-UltimateMenu.php');
add_integration_function('integrate_menu_buttons', 'um_load_menu');
add_integration_function('integrate_admin_areas', 'um_admin_areas');

?>
