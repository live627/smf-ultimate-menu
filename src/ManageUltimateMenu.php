<?php
/**
 * @package Ultimate Menu mod
 * @version   1.0.5
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

class ManageUltimateMenu
{
	private $um;

	function __construct()
	{
		global $context, $sourcedir, $txt;

		isAllowedTo('admin_forum');

		$context['page_title'] = $txt['admin_menu_title'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['admin_menu'],
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
		loadTemplate('ManageUltimateMenu');
		require_once $sourcedir . '/Class-UltimateMenu.php';
		$this->um = new UltimateMenu;

		$subActions = array(
			'manmenu' => 'ManageUltimateMenu',
			'addbutton' => 'PrepareContext',
			'savebutton' => 'SaveButton',
		);
		if (!isset($_GET['sa']) || !isset($subActions[$_GET['sa']]))
			$_GET['sa'] = 'manmenu';
		$this->$subActions[$_GET['sa']]();
	}

	function ManageUltimateMenu()
	{
		global $context, $txt, $scripturl;

		// Get rid of all of em!
		if (!empty($_POST['removeAll']))
		{
			checkSession();
			$this->um->deleteallButtons();
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// User pressed the 'remove selection button'.
		elseif (!empty($_POST['removeButtons']) && !empty($_POST['remove']) && is_array($_POST['remove']))
		{
			checkSession();

			// Make sure every entry is a proper integer.
			foreach ($_POST['remove'] as $index => $page_id)
				$_POST['remove'][(int) $index] = (int) $page_id;

			// Delete the page(s)!
			$this->um->deleteButton($_POST['remove']);
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// Changing the status?
		elseif (isset($_POST['save']))
		{
			checkSession();
			$this->um->updateButton($_POST);
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// New item?
		elseif (isset($_POST['new']))
			redirectexit('action=admin;area=umen;sa=addbutton');

		$button_names = $this->um->getButtonNames();
		$listOptions = array(
			'id' => 'menu_list',
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
			'default_sort_col' => 'name',
			'default_sort_dir' => 'desc',
			'get_items' => array(
				'function' => array($this->um, 'list_getMenu'),
			),
			'get_count' => array(
				'function' => array($this->um, 'list_getNumButtons'),
			),
			'no_items_label' => $txt['um_menu_no_buttons'],
			'columns' => array(
				'name' => array(
					'header' => array(
						'value' => $txt['um_menu_button_name'],
					),
					'data' => array(
						'db_htmlsafe' => 'name',
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'name',
						'reverse' => 'name DESC',
					),
				),
				'type' => array(
					'header' => array(
						'value' => $txt['um_menu_button_type'],
					),
					'data' => array(
						'function' => function($rowData) use ($txt)
						{
							return $txt[$rowData['type'] . '_link'];
						},
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'type',
						'reverse' => 'type DESC',
					),
				),
				'position' => array(
					'header' => array(
						'value' => $txt['um_menu_button_position'],
					),
					'data' => array(
						'function' => function($rowData) use ($txt, $button_names)
						{
							return $txt['mboards_order_' . $rowData['position']] . ' ' . (isset($button_names[$rowData['parent']]) ? $button_names[$rowData['parent']] : ucwords($rowData['parent']));
						},
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'position',
						'reverse' => 'position DESC',
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
						'default' => 'link',
						'reverse' => 'link DESC',
					),
				),
				'status' => array(
					'header' => array(
						'value' => $txt['um_menu_button_active'],
					),
					'data' => array(
						'function' => function($rowData) use ($txt)
						{
							return sprintf('<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />', $rowData['id_button'], $rowData['status'] == 'inactive' ? '' : ' checked="checked"');
						},
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'status',
						'reverse' => 'status DESC',
					),
				),
				'actions' => array(
					'header' => array(
						'value' => $txt['um_menu_actions'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . strtr($scripturl, array('%' => '%%')) . '?action=admin;area=umen;sa=addbutton;edit;in=%1$d">' . $txt['modify'] . '</a>',
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
		require_once $sourcedir . '/Subs-List.php';
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'menu_list';
	}

	function SaveButton()
	{
		global $context, $txt;

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
			$check = $this->um->checkButton($menu_entry['id'], $menu_entry['name']);
			if ($check > 0)
				$post_errors['name'] = 'um_menu_mysql';

			// I see you made it to the final stage, my young padawan.
			if (empty($post_errors))
			{
				$this->um->saveButton($menu_entry);
				$this->um->rebuildMenu();


				// Before we leave, we must clear the cache. See, SMF
				// caches its menu at level 2 or higher.
				clean_cache('menu_buttons');

				redirectexit('action=admin;area=umen');
			}
			else
			{
				$context['page_title'] = $txt['um_menu_edit_title'];
				$context['post_error'] = $post_errors;
				$context['error_title'] = empty($menu_entry['id'])
					? 'um_menu_errors_create'
					: 'um_menu_errors_modify';
				$context['button_data'] = array(
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'parent' => $menu_entry['parent'],
					'permissions' => $this->um->list_groups(
						implode(',', array_filter($menu_entry['permissions'], 'strlen')),
						1
					),
					'status' => $menu_entry['status'],
					'id' => $menu_entry['id'],
				);
			}
		}
	}

	function PrepareContext()
	{
		global $context, $txt;

		if (isset($_GET['in']))
		{
			$row = $this->um->fetchButton($_GET['in']);

			$context['button_data'] = array(
				'id' => $_GET['in'],
				'name' => $row['name'],
				'target' => $row['target'],
				'type' => $row['type'],
				'position' => $row['position'],
				'permissions' => $this->um->list_groups($row['permissions'], 1),
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
				'status' => 'active',
				'permissions' => $this->um->list_groups('-3', 1),
				'parent' => 'home',
				'id' => 0,
			);

			$context['page_title'] = $txt['um_menu_add_title'];
		}
	}
}
