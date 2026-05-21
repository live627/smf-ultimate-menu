<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.5
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace UltimateMenu;

class Menu
{
	public static array $all_buttons = [];

	/**
	 * Main method for injecting dynamic buttons into the menu.
	 *
	 * Called by:
	 *        integrate_menu_buttons
	 *
	 * @param array $menu_buttons Reference to the existing menu button structure.
	 */
	public static function main(array &$menu_buttons): void
	{
		global $modSettings, $user_info, $scripturl;

		if (!isset($modSettings['um_keys'])) {
			return;
		}

		$is_admin = allowedTo('admin_forum');
		$forum_prefix = $scripturl . '?';
		$group_map = array_flip($user_info['groups']);
		$um_keys = explode(',', $modSettings['um_keys']);

		// Build flat indexes
		$nodes = $menu_buttons;
		$root_order = array_keys($menu_buttons);

		// Build lists of deferred operations
		$before = [];
		$after = [];
		$children = [];

		foreach ($um_keys as $key) {
			if (!isset($modSettings[$key])) {
				continue;
			}

			$row = json_decode($modSettings[$key], true);

			$show = $is_admin;

			if (!$show) {
				foreach ($row['groups'] as $group) {
					if (isset($group_map[$group])) {
						$show = true;
						break;
					}
				}
			}

			$show = $show && !empty($row['active']);

			$nodes[$key] = [
				'title' => $row['name'],
				'href' => ($row['type'] === 'forum' ? $forum_prefix : '') . $row['link'],
				'target' => $row['target'],
				'icon' => !empty($row['icon']) && empty($row['sprite']) ? 'um_icons/' . $row['icon'] : (!empty($row['sprite']) ? null : 'um_icons/blank.png'),
				'show' => $show,
			];

			switch ($row['position']) {
				case 'before':
					$before[$row['parent']][] = $key;
					break;

				case 'after':
					$after[$row['parent']][] = $key;
					break;

				case 'child_of':
					$children[$row['parent']][] = $key;
					break;
			}
		}

		$menu_buttons = [];

		foreach ($root_order as $key) {
			self::emitNode(
				$key,
				$nodes,
				$children,
				$before,
				$after,
				$menu_buttons,
			);
		}

		self::$all_buttons = $menu_buttons;
	}

	private static function emitNode(
		string $key,
		array $nodes,
		array $children,
		array $before,
		array $after,
		array &$result,
	): void {
		if (isset($before[$key])) {
			foreach ($before[$key] as $before_key) {
				self::emitNode(
					$before_key,
					$nodes,
					$children,
					$before,
					$after,
					$result,
				);
			}
		}

		$item = $nodes[$key];

		if (isset($children[$key])) {
			$child_result = [];

			foreach ($children[$key] as $child_key) {
				self::emitNode(
					$child_key,
					$nodes,
					$children,
					$before,
					$after,
					$child_result,
				);
			}

			$item['sub_buttons'] = $child_result;
		}

		$result[$key] = $item;

		if (isset($after[$key])) {
			for ($i = count($after[$key]) - 1; $i >= 0; $i--) {
				self::emitNode($after[$key][$i], $nodes, $children, $before, $after, $result);
			}
		}
	}
}
