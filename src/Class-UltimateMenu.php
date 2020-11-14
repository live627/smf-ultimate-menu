<?php

/**
 * @package   Ultimate Menu mod
 * @version   1.1
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

class UltimateMenu
{
	/**
	 * Gets all membergroups and filters them according to the parameters.
	 *
	 * @param int[] $checked    list of all id_groups to be checked (have a mark in the checkbox).
	 *                          Default is an empty array.
	 * @param int[] $disallowed list of all id_groups that are skipped. Default is an empty array.
	 * @param bool  $inherited  whether or not to filter out the inherited groups. Default is false.
	 *
	 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
	 */
	public function listGroups(array $checked = [], array $disallowed = [], $inherited = false)
	{
		global $modSettings, $smcFunc, $sourcedir, $txt;

		loadLanguage('ManageBoards');
		$groups = array();
		if (!in_array(-1, $disallowed))
			$groups[-1] = array(
				'id' => -1,
				'name' => $txt['parent_guests_only'],
				'checked' => in_array(-1, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			);
		if (!in_array(0, $disallowed))
			$groups[0] = array(
				'id' => 0,
				'name' => $txt['parent_members_only'],
				'checked' => in_array(0, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			);
		$where = [];
		if (!$inherited)
		{
			$where[] = 'id_parent = {int:not_inherited}';
			if (empty($modSettings['permission_enable_postgroups']))
				$where[] = 'min_posts = {int:min_posts}';
		}
		$request = $smcFunc['db_query']('', '
			SELECT
				group_name, id_group, min_posts
			FROM {db_prefix}membergroups
			WHERE ' . implode('
				AND '$where),
			array(
				'not_inherited' => -2,
				'min_posts' => -1,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!in_array($row['id_group'], $disallowed))
				$groups[$row['id_group']] = array(
					'id' => $row['id_group'],
					'name' => trim($row['group_name']),
					'checked' => in_array($row['id_group'], $checked) || in_array(-3, $checked),
					'is_post_group' => $row['min_posts'] != -1,
				);
		}
		$smcFunc['db_free_result']($request);

		asort($groups);

		return $groups;
	}

	/**
	 * Loads all buttons from the db
	 *
	 * @return string[]
	 */
	public function total_getMenu()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent
			FROM {db_prefix}um_menu'
		);
		$buttons = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$buttons[] = $row;

		return $buttons;
	}

	/**
	 * Createlist callback, used to display um entries
	 *
	 * @param int    $start
	 * @param int    $items_per_page
	 * @param string $sort
	 *
	 * @return string[]
	 */
	public function list_getMenu($start, $items_per_page, $sort)
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, parent
			FROM {db_prefix}um_menu
			ORDER BY {raw:sort}
			LIMIT {int:offset}, {int:limit}',
			array(
				'sort' => $sort,
				'offset' => $start,
				'limit' => $items_per_page,
			)
		);
		$buttons = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$buttons[] = $row;

		return $buttons;
	}

	/**
	 * Createlist callback to determine the number of buttons
	 * 
	 * @return int
	 */
	public function list_getNumButtons()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}um_menu'
		);
		list ($numButtons) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $numButtons;
	}

	/**
	 * Sets the serialized array of buttons into settings
	 *
	 * Called whenever the menu structure is updated in the ACP
	 */
	public function rebuildMenu()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}um_menu'
		);
		$buttons = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$buttons['um_button_' . $row['id_button']] = json_encode($row);
		$smcFunc['db_free_result']($request);
		updateSettings(
			array(
				'um_count' => count($buttons),
			) + $buttons
		);
	}

	/**
	 * Removes menu item(s) from the um system
	 *
	 * @param int[] $ids
	 */
	public function deleteButton(array $ids)
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}um_menu
			WHERE id_button IN ({array_int:button_list})',
			array(
				'button_list' => $ids,
			)
		);
	}

	/**
	 * Changes the status of a button from active to inactive
	 *
	 * @param array $updates
	 */
	public function updateButton(array $updates)
	{
		global $smcFunc;

		foreach ($this->total_getMenu() as $item)
		{
			$status = !empty($updates['status'][$item['id_button']]) ? 'active' : 'inactive';
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
	}

	/**
	 * Checks if there is an existing um id with the same name before saving
	 *
	 * @param int    $id
	 * @param string $name
	 *
	 * @return int
	 */
	public function checkButton($id, $name)
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT id_button
			FROM {db_prefix}um_menu
			WHERE name = {string:name}
				AND id_button != {int:id}',
			array(
				'name' => $name,
				'id' => $id ?: 0,
			)
		);
		$check = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $check;
	}

	/**
	 * Saves a new or updates an existing button
	 */
	public function saveButton(array $menu_entry)
	{
		global $smcFunc;

		if (!empty($menu_entry['id']))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}um_menu
				SET
					name = {string:name},
					type = {string:type},
					target = {string:target},
					position = {string:position},
					link = {string:link},
					status = {string:status},
					permissions = {string:permissions},
					parent = {string:parent}
				WHERE id_button = {int:id}',
				array(
					'id' => $menu_entry['id'],
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'status' => $menu_entry['status'],
					'permissions' => implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					'parent' => $menu_entry['parent'],
				)
			);
		}
		else
		{
			$smcFunc['db_insert'](
				'insert',
				'{db_prefix}um_menu',
				array(
					'name' => 'string',
					'type' => 'string',
					'target' => 'string',
					'position' => 'string',
					'link' => 'string',
					'status' => 'string',
					'permissions' => 'string',
					'parent' => 'string',
				),
				array(
					$menu_entry['name'],
					$menu_entry['type'],
					$menu_entry['target'],
					$menu_entry['position'],
					$menu_entry['link'],
					$menu_entry['status'],
					implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					$menu_entry['parent'],
				),
				array('id_button')
			);
		}
	}

	/**
	 * Fetch a specific button
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function fetchButton($id)
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent
			FROM {db_prefix}um_menu
			WHERE id_button = {int:button}',
			array(
				'button' => $id,
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		return array(
			'id' => $row['id_button'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => explode(',', $row['permissions']),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
		);
	}

	/**
	 * Removes all buttons
	 */
	public function deleteallButtons()
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			TRUNCATE {db_prefix}um_menu'
		);
	}

	/**
	 * Fetches the names of all SMF menu buttons.
	 *
	 * @return array
	 */
	public function getButtonNames()
	{
		global $context;

		$button_names = [];
		foreach ($context['menu_buttons'] as $button_index => $button_data)
		{
			$button_names[$button_index] = $button_data['title'];

			if (!empty($button_data['sub_buttons']))
			{
				foreach ($button_data['sub_buttons'] as $child_button => $child_button_data)
					$button_names[$child_button] = $child_button_data['title'];

				if (!empty($child_button_data['sub_buttons']))
					foreach ($child_button_data['sub_buttons'] as $grand_child_button => $grand_child_button_data)
						$button_names[$grand_child_button] = $grand_child_button_data['title'];
			}
		}

		return $button_names;
	}
}