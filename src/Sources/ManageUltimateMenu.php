<?php

declare(strict_types=1);
/**
 * @package   Ultimate Menu mod
 * @version   2.0.3
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */
class ManageUltimateMenu
{
	private UltimateMenu $um;

	public function __construct(string $sa)
	{
		global $settings, $modSettings, $context, $txt;

		isAllowedTo('admin_forum');

		$context['html_headers'] .= '
		<link rel="stylesheet" href="' . $settings['default_theme_url'] . '/css/ultimate-menu.css">
		<script src="' . $settings['default_theme_url'] . '/scripts/ultimate-menu.js" defer></script>';
		$context['page_title'] = $txt['admin_menu_title'];
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['admin_menu'],
			'description' => $txt['admin_menu_desc'],
			'tabs' => [
				'manmenu' => [
					'description' => $txt['admin_manage_menu_desc'],
				],
				'fileslist' => [
					'description' => $txt['admin_manage_icons_desc'],
				],
				'addbutton' => [
					'description' => $txt['admin_menu_add_button_desc'],
				],
			],
		];
		$this->um = new UltimateMenu();

		$subActions = [
			'manmenu' => 'ManageMenu',
			'fileslist' => 'FilesList',
			'addbutton' => 'AddButton',
			'editbutton' => 'EditButton',
			'savebutton' => 'SaveButton',
			'uploadicon' => 'UmUploadIcon',
		];
		call_user_func([$this, $subActions[$sa] ?? current($subActions)]);
	}

	public function ManageMenu(): void
	{
		// Get rid of all of em!
		if (isset($_POST['removeAll']))
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

		$this->listButtons();
	}

	public function FilesList(): void
	{
		if (isset($_POST['removeAll']))
		{
			checkSession();
			$this->um->deleteIcons('all', []);
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen;sa=fileslist');
		}
		if (isset($_POST['removeUnassigned']))
		{
			checkSession();
			$this->um->deleteIcons('unassigned', []);
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen;sa=fileslist');
		}
		elseif (isset($_POST['removeSelected'], $_POST['remove']) && is_array($_POST['remove']))
		{
			checkSession();
			$this->um->deleteIcons('selected', array_filter($_POST['remove']));
			$this->um->rebuildMenu();
			redirectexit('action=admin;area=umen;sa=fileslist');
		}

		$this->listFiles();
	}

	private function listButtons(): void
	{
		global $context, $txt, $scripturl, $sourcedir;

		$button_names = $this->um->getButtonNames();
		$listOptions = [
			'id' => 'menu_list',
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
			'default_sort_col' => 'name',
			'default_sort_dir' => 'desc',
			'get_items' => [
				'function' => [$this->um, 'list_getMenu'],
			],
			'get_count' => [
				'function' => [$this->um, 'list_getNumButtons'],
			],
			'no_items_label' => $txt['um_menu_no_buttons'],
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['um_menu_button_name'],
					],
					'data' => [
						'db_htmlsafe' => 'name',
					],
					'sort' => [
						'default' => 'name',
						'reverse' => 'name DESC',
					],
				],
				'type' => [
					'header' => [
						'value' => $txt['um_menu_button_type'],
					],
					'data' => [
						'function' => fn($rowData): string => $txt['um_menu_' . $rowData['type'] . '_link'],
					],
					'sort' => [
						'default' => 'type',
						'reverse' => 'type DESC',
					],
				],
				'position' => [
					'header' => [
						'value' => $txt['um_menu_button_position'],
					],
					'data' => [
						'function' => fn(array $rowData) : string =>
							sprintf(
								'%s %s',
								$txt['um_menu_' . $rowData['position']],
								isset($button_names[$rowData['parent']])
									? $button_names[$rowData['parent']][1]
									: ucwords($rowData['parent'])
							),
					],
					'sort' => [
						'default' => 'position',
						'reverse' => 'position DESC',
					],
				],
				'status' => [
					'header' => [
						'value' => $txt['um_menu_button_active'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => fn(array $rowData) : string =>
							sprintf(
								'<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />',
								$rowData['id_button'],
								$rowData['status'] == 'inactive' ? '' : ' checked="checked"'
							),
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'status',
						'reverse' => 'status DESC',
					],
				],
				'actions' => [
					'header' => [
						'value' => $txt['um_menu_actions'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => fn(array $rowData) : string =>
							sprintf(
								'<a href="%s?action=admin;area=umen;sa=editbutton;in=%d">%s</a>',
								$scripturl,
								$rowData['id_button'],
								$txt['modify']
							),
						'class' => 'centertext',
					],
				],
				'check' => [
					'header' => [
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<input type="checkbox" name="remove[]" value="%d" class="input_check" />',
							'params' => [
								'id_button' => false,
							],
						],
						'class' => 'centertext',
					],
				],
			],
			'form' => [
				'href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
			],
			'additional_rows' => [
				[
					'position' => 'below_table_data',
					'value' => sprintf(
						'
						<input type="submit" name="removeButtons" value="%s" onclick="return confirm(\'%s\');" class="button" />
						<input type="submit" name="removeAll" value="%s" onclick="return confirm(\'%s\');" class="button" />
						<input type="submit" name="new" value="%s" class="button" />
						<input type="submit" name="save" value="%s" class="button" />',
						$txt['um_menu_remove_selected'],
						$txt['um_menu_remove_confirm'],
						$txt['um_menu_remove_all'],
						$txt['um_menu_remove_all_confirm'],
						$txt['um_admin_add_button'],
						$txt['save']
					),
					'class' => 'righttext',
				],
			],
		];
		require_once $sourcedir . '/Subs-List.php';
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'menu_list';
	}

	private function listFiles(): void
	{
		global $context, $txt, $scripturl, $sourcedir, $settings;

		$listOptions = [
			'id' => 'files_list',
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=umen;sa=fileslist',
			'default_sort_col' => 'name',
			'default_sort_dir' => 'asc',
			'get_items' => [
				'function' => [$this->um, 'listIconPathContents'],
			],
			'get_count' => [
				'function' => [$this->um, 'list_getNumIcons'],
			],
			'no_items_label' => $txt['um_menu_no_icons'],
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['um_menu_icon_name'],
					],
					'data' => [
						'function' => fn($rowData): string => $rowData['name'],
					],
					'sort' => [
						'default' => 'name',
						'reverse' => 'name DESC',
					],
				],
				'assigned' => [
					'header' => [
						'value' => $txt['um_menu_icon_assigned'],
					],
					'data' => [
						'function' => fn($rowData): string => $rowData['assigned'],
					],
					'sort' => [
						'default' => 'assigned',
						'reverse' => 'assigned DESC',
					],
				],
				'check' => [
					'header' => [
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<input type="checkbox" name="remove[]" value="%s" class="input_check" />',
							'params' => [
								'name' => false,
							],
						],
						'class' => 'centertext',
					],
				],
			],
			'form' => [
				'href' => $scripturl . '?action=admin;area=umen;sa=fileslist',
			],
			'additional_rows' => [
				[
					'position' => 'below_table_data',
					'value' => sprintf(
						'
						<input type="submit" name="removeSelected" value="%s" onclick="return confirm(\'%s\');" class="button" />
						<input type="submit" name="removeUnassigned" value="%s" onclick="return confirm(\'%s\');" class="button" />
						<input type="submit" name="removeAll" value="%s" onclick="return confirm(\'%s\');" class="button" />',
						$txt['um_menu_delete_selected'],
						$txt['um_menu_delete_selected_confirm'],
						$txt['um_menu_delete_unassigned'],
						$txt['um_menu_delete_unassigned_confirm'],
						$txt['um_menu_delete_all'],
						$txt['um_menu_delete_all_confirm'],
					),
					'class' => 'righttext',
				],
			],
		];
		require_once $sourcedir . '/Subs-List.php';
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'files_list';
	}

	private function getInput(): array
	{
		$member_groups = $this->um->listGroups([-3]);
		$button_names = $this->um->getButtonNames();
		$args = [
			'in' => FILTER_VALIDATE_INT,
			'name' => FILTER_UNSAFE_RAW,
			'icon' => FILTER_UNSAFE_RAW,
			'position' => [
				'filter' => FILTER_CALLBACK,
				'options' => fn($v) => in_array($v, ['before', 'child_of', 'after']) ? $v : false,
			],
			'parent' => [
				'filter' => FILTER_CALLBACK,
				'options' => fn($v) => isset($button_names[$v]) ? $v : false,
			],
			'type' => [
				'filter' => FILTER_CALLBACK,
				'options' => fn($v) => in_array($v, ['forum', 'external']) ? $v : false,
			],
			'link' => FILTER_UNSAFE_RAW,
			'permissions' => [
				'filter' => FILTER_CALLBACK,
				'flags' => FILTER_REQUIRE_ARRAY,
				'options' => fn($v) => isset($member_groups[$v]) ? $v : false,
			],
			'status' => [
				'filter' => FILTER_CALLBACK,
				'options' => fn($v) => in_array($v, ['active', 'inactive']) ? $v : false,
			],
			'target' => [
				'filter' => FILTER_CALLBACK,
				'options' => fn($v) => in_array($v, ['_self', '_blank']) ? $v : false,
			],
		];

		return filter_input_array(INPUT_POST, $args, false) ?: [];
	}

	private function validateInput(array $menu_entry): array
	{
		global $settings;

		$post_errors = [];
		$required_fields = [
			'name',
			'link',
			'parent',
		];

		// If your session timed out, show an error, but do allow to re-submit.
		if (checkSession('post', '', false) != '')
			$post_errors[] = 'um_menu_session_verify_fail';

		// These fields are required!
		foreach ($required_fields as $required_field)
			if (empty($menu_entry[$required_field]))
				$post_errors[$required_field] = 'um_menu_empty_' . $required_field;

		// Stop making numeric names!
		if (is_numeric($menu_entry['name']))
			$post_errors['name'] = 'um_menu_numeric';

		// Ensure icon filename is legit
		if (isset($menu_entry['icon']) && empty($this->um->sanitizeFilename($menu_entry['icon'])))
			$post_errors['icon'] = 'um_menu_filename_illegal';
		elseif (isset($menu_entry['icon']) && !file_exists($settings['default_theme_dir'] . '/images/um_icons/' . $menu_entry['icon']))
			$post_errors['icon'] = 'um_menu_filename_exists';

		// Let's make sure you're not trying to make a name that's already taken.
		if (!empty($this->um->checkButton($menu_entry['in'], $menu_entry['name'])))
			$post_errors['name'] = 'um_menu_mysql';

		return $post_errors;
	}

	public function SaveButton(): void
	{
		global $context, $txt, $settings, $modSettings;

		if (isset($_POST['submit']))
		{
			$menu_entry = array_replace(
				[
					'target' => '_self',
					'type' => 'forum',
					'position' => 'before',
					'permissions' => [],
					'status' => 'active',
					'parent' => '',
					'icon' => '',
					'image' => '',
				],
				$this->getInput()
			);
			$post_errors = $this->validateInput($menu_entry);

			// I see you made it to the final stage, my young padawan.
			if (empty($post_errors))
			{
				if (isset($_FILES['attachment']))
					unset($_FILES['attachment']);

				clearstatcache();
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
				$context['button_names'] = $this->um->getButtonNames();
				$context['post_error'] = $post_errors;
				$context['error_title'] = empty($menu_entry['in'])
					? 'um_menu_errors_create'
					: 'um_menu_errors_modify';
				$context['button_data'] = [
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'parent' => $menu_entry['parent'],
					'icon' => (!empty($menu_entry['icon']) ? $menu_entry['icon'] : ''),
					'image' => '<img id="um_icon_img" style="width: 16px;height: 16px;object-fit: contain;" alt="" src="' . $this->um->iconFilePath(!empty($menu_entry['icon']) ? $menu_entry['icon'] : '') . '">',
					'permissions' => $this->um->listGroups(
						array_filter($menu_entry['permissions'], 'strlen')
					),
					'status' => $menu_entry['status'],
					'id' => $menu_entry['in'],
				];
				$context['all_groups_checked'] = empty(array_diff_key(
					$context['button_data']['permissions'],
					array_flip(array_filter($menu_entry['permissions'], 'strlen'))
				));
				$context['template_layers'][] = 'form';
				$context['template_layers'][] = 'errors';
				$context['um_button_icons'] = $this->um->getIconPathContents();
			}
		}
		else
			fatal_lang_error('no_access', false);
	}

	public function EditButton(): void
	{
		global $settings, $modSettings, $context, $txt;

		$row = isset($_GET['in']) ? $this->um->fetchButton($_GET['in']) : [];
		if (empty($row))
			fatal_lang_error('no_access', false);

		$bytes = openssl_random_pseudo_bytes(10);
		$codeValue = strval(bin2hex($bytes));
		updateSettings([
			'um_secureCode' => $codeValue
		]);
		$modSettings['um_secureCode'] = $codeValue;
		$context['button_data'] = [
			'id' => $row['id'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => $this->um->listGroups($row['permissions']),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
			'icon' => !empty($row['icon']) ? $row['icon'] : '',
			'image' => '<img id="um_icon_img" style="width: 16px;height: 16px;object-fit: contain;" alt="" src="' . $this->um->iconFilePath(!empty($row['icon']) ? $row['icon'] : '') . '">',
			'um_secureCode' => $codeValue,

		];
		$context['all_groups_checked'] = empty(array_diff_key(
			$context['button_data']['permissions'],
			array_flip($row['permissions'])
		));
		$context['page_title'] = $txt['um_menu_edit_title'];
		$context['button_names'] = $this->um->getButtonNames();
		$context['template_layers'][] = 'form';
		$context['um_button_icons'] = $this->um->getIconPathContents();
		$context['html_headers'] .= '
		<script>
			let um_secureCode = "' . $codeValue . '";
		</script>';
	}

	public function AddButton(): void
	{
		global $settings, $modSettings, $context, $txt;

		$bytes = openssl_random_pseudo_bytes(10);
		$codeValue = strval(bin2hex($bytes));
		updateSettings([
			'um_secureCode' => $codeValue
		]);
		$modSettings['um_secureCode'] = $codeValue;

		$context['button_data'] = [
			'name' => '',
			'link' => '',
			'target' => '_self',
			'type' => 'forum',
			'position' => 'before',
			'status' => 'active',
			'permissions' => $this->um->listGroups([-3]),
			'parent' => 'home',
			'icon' => '',
			'image' => '<img id="um_icon_img" style="width: 16px;height: 16px;object-fit: contain;" alt="" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=">',
			'id' => 0,
			'um_secureCode' => $codeValue,
		];
		$context['all_groups_checked'] = true;
		$context['page_title'] = $txt['um_menu_add_title'];
		$context['button_names'] = $this->um->getButtonNames();
		$context['template_layers'][] = 'form';
		$context['um_button_icons'] = $this->um->getIconPathContents();
		$context['html_headers'] .= '
		<script>
			let um_secureCode = "' . $codeValue . '";
		</script>';
	}

	public function UmUploadIcon(): void
	{
		global $txt, $settings, $modSettings, $sourcedir;
		checkSession('post');
		list($types, $json_msg) = [['jpeg', 'jpg', 'png'], ['error' => $txt['um_menu_filename_illegal'], 'file' => '']];
		$postVar = !empty($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$checkCode = isset($_POST['um_checkcode']) ? $_POST['um_checkcode'] : '';
		$umCode = !empty($modSettings['um_secureCode']) ? $modSettings['um_secureCode'] : '';

		if (empty($checkCode) || empty($umCode) || $checkCode != $umCode)
			exit(json_encode($json_msg));

		clearstatcache();
		if (!empty($postVar) && !empty($postVar['name']))
		{
			$newname = $postVar['name'] = $this->um->sanitizeFilename(basename($postVar['name']));
			$target = $this->um->unixDirSeparator($settings['default_theme_dir'] . '/images/um_icons');
			$tmp_name = $postVar['tmp_name'];
			$ext = strtolower(pathinfo($newname, PATHINFO_EXTENSION));
			$filename = pathinfo($newname, PATHINFO_FILENAME);
			$file = $this->um->hexadecimal_filename($filename) . '.' . $ext;
			if (!in_array($ext, $types))
				$json_msg['error'] = $txt['um_menu_filename_illegal'];
			else
			{
				if (file_exists($target . '/' . $newname))
				{
					@unlink($target . '/' . $newname);
					clearstatcache();
				}
				$com = fopen($target . '/' . $newname, "ab");
				$in = fopen($tmp_name, "rb");
				if ($in)
				{
					while ($buff = fread($in, 1048576))
					{
						fwrite($com, $buff);
						sleep(1);
					}
					fclose($in);
				}
				fclose($com);
				clearstatcache();
				if (!empty($newname) && file_exists($target . '/' . $newname))
				{
					$renamed = $this->um->imageResize($target . '/' . $newname, $target . '/' . $file, $ext, 16, 16, false);
					if (!empty($renamed) && $renamed != $newname)
					{
						$newname = $renamed;
						$json_msg = ['error' => '', 'file' => $newname];
					}
					elseif (!empty($renamed))
						$json_msg = ['error' => $txt['um_menu_filename_compress'], 'file' => $newname];
					else
						$json_msg['error'] = $txt['um_menu_filename_unknown'];
				}
				else
					$json_msg['error'] = $txt['um_menu_filename_exists'];
			}
			unset($_FILES['attachment']);
		}

		exit(json_encode($json_msg));
	}
}
