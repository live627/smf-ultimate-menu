<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.0
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
	 * @param bool  $inherited  whether or not to filter out the inherited groups. Default is false.
	 *
	 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
	 */
	public function listGroups(array $checked = [], $inherited = false)
	{
		global $modSettings, $smcFunc, $sourcedir, $txt;

		loadLanguage('ManageBoards');
		$groups = [
			-1 => [
				'name' => $txt['parent_guests_only'],
				'checked' => in_array(-1, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			],
			0 => [
				'name' => $txt['parent_members_only'],
				'checked' => in_array(0, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			],
		];
		$where = ['id_group NOT IN (1, 3)'];

		if (!$inherited)
		{
			$where[] = 'id_parent = {int:not_inherited}';

			if (empty($modSettings['permission_enable_postgroups']))
				$where[] = 'min_posts = {int:min_posts}';
		}
		$request = $smcFunc['db_query']('', '
			SELECT
				id_group, group_name, min_posts
			FROM {db_prefix}membergroups
			WHERE ' . implode("\n\t\t\t\tAND ", $where),
			[
				'not_inherited' => -2,
				'min_posts' => -1,
			]
		);

		while ([$id, $name, $min_posts] = $smcFunc['db_fetch_row']($request))
			$groups[$id] = [
				'name' => trim($name),
				'checked' => in_array($id, $checked) || in_array(-3, $checked),
				'is_post_group' => $min_posts != -1,
			];
		$smcFunc['db_free_result']($request);

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
		$buttons = [];

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

		$buttons = [];
		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, parent
			FROM {db_prefix}um_menu
			ORDER BY {raw:sort}
			LIMIT {int:offset}, {int:limit}',
			[
				'sort' => $sort,
				'offset' => $start,
				'limit' => $items_per_page,
			]
		);

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
		[$numButtons] = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $numButtons;
	}

	/**
	 * Sets the serialized array of buttons into settings
	 *
	 * Called whenever the menu structure is updated in the ACP
	 */
	public function rebuildMenu(): void
	{
		global $smcFunc;

		$buttons = [];
		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent
			FROM {db_prefix}um_menu'
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$buttons['um_button_' . $row['id_button']] = json_encode([
				'name' => $row['name'],
				'target' => $row['target'],
				'type' => $row['type'],
				'position' => $row['position'],
				'groups' => array_map('intval', explode(',', $row['permissions'])),
				'link' => $row['link'],
				'active' => $row['status'] == 'active',
				'parent' => $row['parent'],
			]);
		$smcFunc['db_free_result']($request);

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

	/**
	 * Removes menu item(s) from the um system
	 *
	 * @param int[] $ids
	 */
	public function deleteButton(array $ids): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}um_menu
			WHERE id_button IN ({array_int:button_list})',
			[
				'button_list' => $ids,
			]
		);
	}

	/**
	 * Changes the status of a button from active to inactive
	 *
	 */
	public function updateButton(array $updates): void
	{
		global $smcFunc;

		foreach ($this->total_getMenu() as $item)
		{
			$status = !empty($updates['status'][$item['id_button']]) ? 'active' : 'inactive';

			if ($status != $item['status'])
				$smcFunc['db_query'](
					'',
					'
					UPDATE {db_prefix}um_menu
					SET status = {string:status}
					WHERE id_button = {int:item}',
					[
						'status' => $status,
						'item' => $item['id_button'],
					]
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
	public function checkButton($id, $name): int
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT id_button
			FROM {db_prefix}um_menu
			WHERE name = {string:name}
				AND id_button != {int:id}',
			[
				'name' => $name,
				'id' => $id ?: 0,
			]
		);
		$check = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $check;
	}

	/**
	 * Saves a new or updates an existing button
	 */
	public function saveButton(array $menu_entry): void
	{
		global $smcFunc;

		if (!empty($menu_entry['in']))
		{
			$smcFunc['db_query'](
				'',
				'
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
				[
					'id' => $menu_entry['in'],
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'status' => $menu_entry['status'],
					'permissions' => implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					'parent' => $menu_entry['parent'],
				]
			);
		}
		else
		{
			$smcFunc['db_insert'](
				'insert',
				'{db_prefix}um_menu',
				[
					'name' => 'string',
					'type' => 'string',
					'target' => 'string',
					'position' => 'string',
					'link' => 'string',
					'status' => 'string',
					'permissions' => 'string',
					'parent' => 'string',
				],
				[
					$menu_entry['name'],
					$menu_entry['type'],
					$menu_entry['target'],
					$menu_entry['position'],
					$menu_entry['link'],
					$menu_entry['status'],
					implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					$menu_entry['parent'],
				],
				['id_button']
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
	public function fetchButton($id): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent
			FROM {db_prefix}um_menu
			WHERE id_button = {int:button}',
			[
				'button' => $id,
			]
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		return [
			'id' => $row['id_button'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => explode(',', $row['permissions']),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
		];
	}

	/**
	 * Removes all buttons
	 */
	public function deleteallButtons(): void
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
	public function getButtonNames(): array
	{
		global $context;

		// Start an instant replay.
		add_integration_function('integrate_menu_buttons', 'um_replay_menu', false);

		// It's expected to be present.
		$context['user']['unread_messages'] = 0;

		// Load SMF's default menu context.
		setupMenuContext();

		// We are in the endgame now.
		remove_integration_function('integrate_menu_buttons', 'um_replay_menu');

		return $this->flatten($context['replayed_menu_buttons']);
	}

	private function flatten(array $array, int $i = 0): array
	{
		$result = array();
		foreach ($array as $key => $value)
		{
			$result[$key] = [$i, $value['title']];
			if (!empty($value['sub_buttons']))
				$result += $this->flatten($value['sub_buttons'], $i + 1);
		}
		return $result;
	}
}