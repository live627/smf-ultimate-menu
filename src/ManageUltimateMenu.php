<?php
/**
 * @package   Ultimate Menu mod
 * @version   1.1
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

class ManageUltimateMenu
{
	private $um;

	public function __construct()
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
			'addbutton' => 'AddButton',
			'editbutton' => 'EditButton',
			'savebutton' => 'SaveButton',
		);
		if (!isset($_GET['sa']) || !isset($subActions[$_GET['sa']]))
			$_GET['sa'] = 'manmenu';
		$this->{$subActions[$_GET['sa']]}();
	}

	public function ManageUltimateMenu()
	{
		// Get rid of all of em!
		if (!empty($_POST['removeAll']))
		{
			checkSession();
			$this->um->deleteallButtons();
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// User pressed the 'remove selection button'.
		elseif (isset($_POST['removeButtons'], $_POST['remove']) && is_array($_POST['remove']))
		{
			checkSession();
			$this->um->deleteButton(array_filter($_POST['remove'], 'ctype_digit'));
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

		$this->ListButtons();
	}

	public function ListButtons()
	{
		global $context, $txt, $scripturl, $sourcedir;

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
							return $txt['um_menu_' . $rowData['type'] . '_link'];
						},
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
							return sprintf(
								'%s %s',
								$txt['um_menu_' . $rowData['position']],
								isset($button_names[$rowData['parent']])
									? $button_names[$rowData['parent']][1]
									: ucwords($rowData['parent'])
							);
						},
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
					),
					'sort' => array(
						'default' => 'link',
						'reverse' => 'link DESC',
					),
				),
				'status' => array(
					'header' => array(
						'value' => $txt['um_menu_button_active'],
						'class' => 'centertext',
					),
					'data' => array(
						'function' => function($rowData)
						{
							return sprintf(
								'<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />',
								$rowData['id_button'],
								$rowData['status'] == 'inactive' ? '' : ' checked="checked"'
							);
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
						'class' => 'centertext',
					),
					'data' => array(
						'function' => function($rowData) use ($scripturl, $txt)
						{
							return sprintf(
								'<a href="%s?action=admin;area=umen;sa=editbutton;in=%d">%s</a>',
								$scripturl,
								$rowData['id_button'],
								$txt['modify']
							);
						},
						'class' => 'centertext',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="remove[]" value="%d" class="input_check" />',
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
					'value' => sprintf('
						<input type="submit" name="removeButtons" value="%s" onclick="return confirm(\'%s\');" class="button_submit" />
						<input type="submit" name="removeAll" value="%s" onclick="return confirm(\'%s\');" class="button_submit" />
						<input type="submit" name="new" value="%s" class="button_submit" />
						<input type="submit" name="save" value="%s" class="button_submit" />',
						$txt['um_menu_remove_selected'],
						$txt['um_menu_remove_confirm'],
						$txt['um_menu_remove_all'],
						$txt['um_menu_remove_all_confirm'],
						$txt['um_admin_add_button'],
						$txt['save']
					),
					'class' => 'righttext',
				),
			),
		);
		require_once $sourcedir . '/Subs-List.php';
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'menu_list';
	}

	public function SaveButton()
	{
		global $context, $txt;

		if (isset($_POST['submit']))
		{
			checkSession();
			$post_errors = array();
			$required_fields = array(
				'name',
				'link',
				'parent',
			);
			$member_groups = $this->um->listGroups([-3]);
			$button_names = $this->um->getButtonNames();
			$args = array(
				'in' => FILTER_VALIDATE_INT,
				'name' => FILTER_UNSAFE_RAW,
				'position' => array(
					'filter' => FILTER_CALLBACK,
					'options' => function ($v)
					{
						return in_array($v, ['before', 'child_of', 'after']) ? $v : false;
					},
				),
				'parent' => array(
					'filter' => FILTER_CALLBACK,
					'options' => function ($v) use ($button_names)
					{
						return isset($button_names[$v]) ? $v : false;
					},
				),
				'type' => array(
					'filter' => FILTER_CALLBACK,
					'options' => function ($v)
					{
						return in_array($v, ['forum', 'external']) ? $v : false;
					},
				),
				'link' => FILTER_UNSAFE_RAW,
				'permissions' => array(
					'filter' => FILTER_CALLBACK,
					'flags' => FILTER_REQUIRE_ARRAY,
					'options' => function ($v) use ($member_groups)
					{
						return isset($member_groups[$v]) ? $v : false;
					},
				),
				'status' => array(
					'filter' => FILTER_CALLBACK,
					'options' => function ($v)
					{
						return in_array($v, ['active', 'inactive']) ? $v : false;
					},
				),
				'target' => array(
					'filter' => FILTER_CALLBACK,
					'options' => function ($v)
					{
						return in_array($v, ['_self', '_blank']) ? $v : false;
					},
				),
			);

			// Make sure we grab all of the content
			$menu_entry = array_replace(
				array(
					'target' => '_self',
					'type' => 'forum',
					'position' => 'before',
					'status' => 'active',
					'parent' => 'home',
				),
				filter_input_array(INPUT_POST, $args)
			);

			// These fields are required!
			foreach ($required_fields as $required_field)
				if (empty($menu_entry[$required_field]))
					$post_errors[$required_field] = 'um_menu_empty_' . $required_field;

			// Stop making numeric names!
			if (is_numeric($menu_entry['name']))
				$post_errors['name'] = 'um_menu_numeric';

			// Let's make sure you're not trying to make a name that's already taken.
			if (!empty($this->um->checkButton($menu_entry['in'], $menu_entry['name'])))
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
				$context['button_names'] = $button_names;
				$context['post_error'] = $post_errors;
				$context['error_title'] = empty($menu_entry['in'])
					? 'um_menu_errors_create'
					: 'um_menu_errors_modify';
				$context['button_data'] = array(
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'parent' => $menu_entry['parent'],
					'permissions' => $this->um->listGroups(
						array_filter($menu_entry['permissions'], 'strlen')
					),
					'status' => $menu_entry['status'],
					'id' => $menu_entry['in'],
				);
				$context['all_groups_checked'] = empty(array_diff_key(
					$context['button_data']['permissions'],
					array_flip(array_filter($menu_entry['permissions'], 'strlen'))
				));
				$context['template_layers'][] = 'form';
				$context['template_layers'][] = 'errors';
			}
		}
		else
			fatal_lang_error('no_access', false);
	}

	public function EditButton()
	{
		global $context, $txt;

		if (isset($_GET['in']))
			$row = $this->um->fetchButton($_GET['in']);
		elseif (empty($row))
			fatal_lang_error('no_access', false);

		$context['button_data'] = array(
			'id' => $row['id'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => $this->um->listGroups($row['permissions']),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
		);
		$context['all_groups_checked'] = empty(array_diff_key(
			$context['button_data']['permissions'],
			array_flip($row['permissions'])
		));
		$context['page_title'] = $txt['um_menu_edit_title'];
		$context['button_names'] = $this->um->getButtonNames();
		$context['template_layers'][] = 'form';
	}

	public function AddButton()
	{
		global $context, $txt;

		$context['button_data'] = array(
			'name' => '',
			'link' => '',
			'target' => '_self',
			'type' => 'forum',
			'position' => 'before',
			'status' => 'active',
			'permissions' => $this->um->listGroups([-3]),
			'parent' => 'home',
			'id' => 0,
		);
		$context['all_groups_checked'] = true;
		$context['page_title'] = $txt['um_menu_add_title'];
		$context['button_names'] = $this->um->getButtonNames();
		$context['template_layers'][] = 'form';
	}
}
