<?php

/**
 * @package Ultimate Menu mod
 * @version 1.0.2
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function Menu()
{
	global $context, $txt;

	loadTemplate('ManageUltimateMenu');

	$subActions = array(
		'manmenu' => 'ManageUltimateMenu',
		'addbutton' => 'PrepareContext',
		'savebutton' => 'SaveButton',
	);

	// Default to sub action 'manmenu'
	if (!isset($_GET['sa']) || !isset($subActions[$_GET['sa']]))
		$_GET['sa'] = 'manmenu';

	// Have you got the proper permissions?
	isAllowedTo('admin_forum');

	$context['page_title'] = $txt['admin_menu_title'];

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => &$txt['admin_menu'],
		'description' => $txt['admin_menu_desc'],
		'tabs' => array(
			'manmenu' => array(
				'description' => $txt['admin_manage_menu_desc'],
			),
			'addbutton' => array(
				'description' => $txt['admin_menu_add_button_desc'],
			),
		),
	);

	// Call the right function for this sub-acton.
	$subActions[$_GET['sa']]();

}

function ManageUltimateMenu()
{
	global $context, $txt, $modSettings, $scripturl, $sourcedir, $smcFunc;

	// Get rid of all of em!
	if (!empty($_POST['removeAll']))
	{
		checkSession();

		$smcFunc['db_query']('truncate_table', '
				TRUNCATE {db_prefix}um_menu');

		rebuild_um_menu();
		redirectexit('action=admin;area=umen');
	}

	// User pressed the 'remove selection button'.
	if (!empty($_POST['removeButtons']) && !empty($_POST['remove']) && is_array($_POST['remove']))
	{
		checkSession();

		// Make sure every entry is a proper integer.
		foreach ($_POST['remove'] as $index => $page_id)
			$_POST['remove'][(int) $index] = (int) $page_id;

		// Delete the page!
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}um_menu
			WHERE id_button IN ({array_int:button_list})',
			array(
				'button_list' => $_POST['remove'],
			)
		);
		rebuild_um_menu();
		redirectexit('action=admin;area=umen');
	}

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getMenu() as $item)
		{
			$status = !empty($_POST['status'][$item['id_button']]) ? 'active' : 'inactive';
			if ($status != $item['status'])
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}um_menu
					SET status = {string:status}
					WHERE id_button = {int:item}',
					array(
						'status' => $status,
						'item' => $item['id_button'],
					)
				);
		}
		rebuild_um_menu();
		redirectexit('action=admin;area=umen');
	}

	// New item?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=umen;sa=addbutton');

	loadLanguage('ManageBoards');

	// Our options for our list.
	$listOptions = array(
		'id' => 'menu_list',
		'items_per_page' => 20,
		'base_href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
		'default_sort_col' => 'id_button',
		'default_sort_dir' => 'desc',
		'get_items' => array(
			'function' => 'list_getMenu',
		),
		'get_count' => array(
			'function' => 'list_getNumButtons',
		),
		'no_items_label' => $txt['um_menu_no_buttons'],
		'columns' => array(
			'id_button' => array(
				'header' => array(
					'value' => $txt['um_menu_button_id'],
				),
				'data' => array(
					'db' => 'id_button',
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.id_button',
					'reverse' => 'men.id_button DESC',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['um_menu_button_name'],
				),
				'data' => array(
					'db_htmlsafe' => 'name',
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.name',
					'reverse' => 'men.name DESC',
				),
			),
			'type' => array(
				'header' => array(
					'value' => $txt['um_menu_button_type'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						return $txt[$rowData[\'type\'] . \'_link\'];
					'),
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.type',
					'reverse' => 'men.type DESC',
				),
			),
			'poition' => array(
				'header' => array(
					'value' => $txt['um_menu_button_position'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						return $txt[\'mboards_order_\' . $rowData[\'position\']] . \' \' . ucwords($rowData[\'parent\']);
					'),
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.position',
					'reverse' => 'men.position DESC',
				),
			),
			'link' => array(
				'header' => array(
					'value' => $txt['um_menu_button_link'],
				),
				'data' => array(
					'db_htmlsafe' => 'link',
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.link',
					'reverse' => 'men.link DESC',
				),
			),
			'status' => array(
				'header' => array(
					'value' => $txt['um_menu_button_active'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'status\'] == \'inactive\' ? \'\' : \' checked="checked"\';
						return sprintf(\'<span>%3$s</span>&nbsp;<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />\', $rowData[\'id_button\'], $isChecked, $txt[$rowData[\'status\']], $rowData[\'status\']);
					'),
					'class' => 'centertext',
				),
				'sort' => array(
					'default' => 'men.status',
					'reverse' => 'men.status DESC',
				),
			),
			'actions' => array(
				'header' => array(
					'value' => $txt['um_menu_actions'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?action=admin;area=umen;sa=addbutton;edit;in=%1$d">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_button' => false,
						),
					),
					'class' => 'centertext',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="remove[]" value="%1$d" class="input_check" />',
						'params' => array(
							'id_button' => false,
						),
					),
					'class' => 'centertext',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '
					<input type="submit" name="removeButtons" value="' . $txt['um_menu_remove_selected'] . '" onclick="return confirm(\'' . $txt['um_menu_remove_confirm'] . '\');" class="button_submit" />
					<input type="submit" name="removeAll" value="' . $txt['um_menu_remove_all'] . '" onclick="return confirm(\'' . $txt['um_menu_remove_all_confirm'] . '\');" class="button_submit" />
					<input type="submit" name="new" value="' . $txt['um_admin_add_button'] . '" class="button_submit" />
					<input type="submit" name="save" value="' . $txt['save'] . '" class="button_submit" />',
				'class' => 'righttext',
			),
		),
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	$context['sub_template'] = 'show_list';
	$context['default_list'] = 'menu_list';
}

function SaveButton()
{
	global $context, $smcFunc, $txt, $sourcedir;

	// It's expected to be present.
	$context['user']['unread_messages'] = 0;

	// Load SMF's default menu context
	setupMenuContext();

	if (isset($_REQUEST['submit']))
	{
		$post_errors = array();
		$required_fields = array(
			'name',
			'link',
			'parent',
		);

		// Make sure we grab all of the content
		$id = isset($_REQUEST['in']) ? (int) $_REQUEST['in'] : 0;
		$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
		$position = isset($_REQUEST['position']) ? $_REQUEST['position'] : 'before';
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'forum';
		$link = isset($_REQUEST['link']) ? $_REQUEST['link'] : '';
		$permissions = isset($_REQUEST['permissions']) ? implode(',', array_intersect($_REQUEST['permissions'], array_keys(list_groups(-3, 1)))) : '1';
		$status = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'active';
		$parent = isset($_REQUEST['parent']) ? $_REQUEST['parent'] : 'home';
		$target = isset($_REQUEST['target']) ? $_REQUEST['target'] : '_self';

		// These fields are required!
		foreach ($required_fields as $required_field)
			if ($_POST[$required_field] == '')
				$post_errors[$required_field] = 'um_menu_empty_' . $required_field;

		// Stop making numeric names!
		if (is_numeric($name))
			$post_errors['name'] = 'um_menu_numeric';

		// Let's make sure you're not trying to make a name that's already taken.
		$request = $smcFunc['db_query']('', '
			SELECT id_button
			FROM {db_prefix}um_menu
			WHERE name = {string:name}
			AND id_button != {int:id}',
			array(
				'name' => $name,
				'id' => $id,
			)
		);

		$check = $smcFunc['db_num_rows']($request);

		$smcFunc['db_free_result']($request);

		if ($check > 0)
			$post_errors['name'] = 'um_menu_mysql';

		if (empty($post_errors))
		{
			// I see you made it to the final stage, my young padawan.
			if (!empty($id))
				$smcFunc['db_query']('','
					UPDATE {db_prefix}um_menu
					SET name = {string:name}, type = {string:type}, target = {string:target}, position = {string:position}, link = {string:link}, status = {string:status}, permissions = {string:permissions}, parent = {string:parent}
					WHERE id_button = {int:id}',
					array(
						'id' => $id,
						'name' => $name,
						'type' => $type,
						'target' => $target,
						'position' => $position,
						'link' => $link,
						'status' => $status,
						'permissions' => $permissions,
						'parent' => $parent,
					)
				);
			else
				$smcFunc['db_insert']('insert',
					'{db_prefix}um_menu',
						array(
							'slug' => 'string', 'name' => 'string', 'type' => 'string', 'target' => 'string', 'position' => 'string', 'link' => 'string', 'status' => 'string', 'permissions' => 'string', 'parent' => 'string',
						),
						array(
							md5($name) . '-' . time(), $name, $type, $target, $position, $link, $status, $permissions, $parent,
						),
						array('id_button')
					);

			rebuild_um_menu();

			// Before we leave, we must clear the cache. See, SMF
			// caches its menu at level 2 or higher.
			clean_cache('menu_buttons');

			redirectexit('action=admin;area=umen');
		}
		else
		{
			$context['post_error'] = $post_errors;
			$context['error_title'] = empty($id) ? 'um_menu_errors_create' : 'um_menu_errors_modify';

			$context['button_data'] = array(
				'name' => $name,
				'type' => $type,
				'target' => $target,
				'position' => $position,
				'link' => $link,
				'parent' => $parent,
				'permissions' => list_groups($permissions, 1),
				'status' => $status,
				'id' => $id,
			);

			$context['page_title'] = $txt['um_menu_edit_title'];
		}
	}
}
/**
 * Prepares theme context for the template.
 *
 * @since 1.0
 */
function PrepareContext()
{
	global $context, $smcFunc, $txt, $sourcedir;

	// It's expected to be present.
	$context['user']['unread_messages'] = 0;

	// Load SMF's default menu context
	setupMenuContext();

	if (isset($_GET['in']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT name, target, type, position, link, status, permissions, parent
			FROM {db_prefix}um_menu
			WHERE id_button = {int:button}
			LIMIT 1',
			array(
				'button' => (int) $_GET['in'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);

		$context['button_data'] = array(
			'id' => $_GET['in'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => list_groups($row['permissions'], 1),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
		);
	}
	else
	{
		$context['button_data'] = array(
			'name' => '',
			'link' => '',
			'target' => '_self',
			'type' => 'forum',
			'position' => 'before',
			'status' => '1',
			'permissions' => list_groups('-3', 1),
			'parent' => 'home',
			'id' => 0,
		);

		$context['page_title'] = $txt['um_menu_add_title'];
	}
}

function total_getMenu()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT id_button, name, target, type, position, link, status, permissions, parent
		FROM {db_prefix}um_menu');

	$um_menu = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$um_menu[] = $row;

	return $um_menu;
}

function list_getMenu($start, $items_per_page, $sort)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT id_button, name, target, type, position, link, status, permissions, parent
		FROM {db_prefix}um_menu AS men
		ORDER BY {raw:sort}
		LIMIT {int:offset}, {int:limit}',
		array(
			'sort' => $sort,
			'offset' => $start,
			'limit' => $items_per_page,
		)
	);

	$um_menu = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$um_menu[] = $row;

	return $um_menu;
}

function list_getNumButtons()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}um_menu');

	list ($numButtons) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $numButtons;
}

function rebuild_um_menu()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}um_menu');

	$db_buttons = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$db_buttons[$row['id_button']] = $row;

	$smcFunc['db_free_result']($request);
	updateSettings(
		array(
			'um_menu' => serialize($db_buttons),
		)
	);
}

?>