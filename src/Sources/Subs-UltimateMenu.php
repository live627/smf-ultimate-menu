<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.5
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function um_get_settings(): void
{
	global $modSettings, $umSettings;

	if (substr($modSettings['integrate_menu_buttons'], -12) !== 'um_load_menu') {
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
	}

	if (!empty($modSettings['um_settings'])) {
		$umSettings = json_decode($modSettings['um_settings'], true);
		$umSettings['um_icon_dimension'] = (int) $umSettings['um_icon_dimension'];
	} else {
		$umSettings = [
			'um_fingerprint' => mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8'),
			'um_icon_dimension' => 32,
			'um_secureCode' =>  strval(bin2hex(random_bytes(10))),
		];
	}
}

function um_linking(): void
{
	global $modSettings, $context, $umSettings;

	loadCSSFile('ultimate-menu-buttons' . (!empty($modSettings['minimize_files']) ? '.min' : '') . '.css?v=' . um_cache_busting(false), ['default_theme' => true, 'minimize' => false, 'order_pos' => 901], 'um_buttons');
	loadCSSFile('ultimate-menu-icons.css?v=' . um_cache_busting(false), ['default_theme' => true, 'minimize' => false, 'order_pos' => 900], 'um_icons');

	if (um_admin_queryString(['action' => 'admin', 'area' => 'umen'])) {
		loadCSSFile('ultimate-menu.css?v=' . um_cache_busting(false), ['default_theme' => true, 'minimize' => false, 'order_pos' => 902], 'um_admin');
		loadJavaScriptFile('ultimate-menu.js?v=' . um_cache_busting(false), ['default_theme' => true, 'minimize' => false, 'defer' => true], 'um_admin');
	}
}


function um_load_menu(array &$menu_buttons): void
{
	global $context, $modSettings, $user_info, $scripturl;

	if (!isset($modSettings['um_keys'])) {
		$context['um_all_buttons'] = $menu_buttons;
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
		emit_node(
			$key,
			$nodes,
			$children,
			$before,
			$after,
			$menu_buttons,
		);
	}

	$context['um_all_buttons'] = $menu_buttons;
}

function emit_node(string $key, array $nodes, array $children, array $before, array $after, array &$result): void
{
	if (isset($before[$key])) {
		foreach ($before[$key] as $before_key) {
			emit_node(
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
			emit_node(
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
			emit_node($after[$key][$i], $nodes, $children, $before, $after, $result);
		}
	}
}

function um_cache_busting($force = false): string
{
	global $umSettings;

	return !empty($umSettings['um_fingerprint']) && !um_admin_queryString(['action' => 'admin', 'area' => 'umen']) && empty($force) ? $umSettings['um_fingerprint'] : mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8');
}

function um_admin_queryString($parameters = []): bool
{
	global $smcFunc;

	$count = 0;

	return array_walk_recursive($parameters, function($value, $request) use (&$count, $smcFunc) {
		$count = isset($_GET[$request]) && stripos($smcFunc['htmlspecialchars']($_GET[$request]), $value) !== false ? $count + 1 : $count;
	}, $count) == (count($parameters) ?: -1);
}

function um_admin_areas(&$admin_areas): void
{
	global $context, $txt;

	loadLanguage('ManageUltimateMenu');
	$admin_areas['config']['areas']['umen'] = [
		'label' => $txt['um_admin_menu_um'],
		'file' => 'ManageUltimateMenu.php',
		'function' => function(): void {
			global $sourcedir;

			loadTemplate('ManageUltimateMenu');

			require_once $sourcedir . '/Class-UltimateMenu.php';
			(new ManageUltimateMenu($_GET['sa'] ?? ''));
		},
		'icon' => 'umen.png',
		'subsections' => [
			'manmenu' => [$txt['um_admin_manage_menu'], ''],
			'fileslist' => [$txt['um_admin_manage_icons'], ''],
			'addbutton' => [$txt['um_admin_add_button'], ''],
		],
	];

	// Additional messages for "Remove all data associated with this modification."
	if (um_admin_queryString(['action' => 'admin', 'area' => 'packages', 'sa' => 'uninstall', 'package' => 'ultimate-menu'])) {
		$context['html_headers'] .= '
		<script>
			$(document).ready(function(){
				' . implode('', array_map(fn($val) => '$("<li>", {text: \'' . preg_replace("/(?<!\\\\)'/", "\\'", $val) . '\'}).appendTo($("#db_changes_div > ul"));', explode('|', $txt['um_menu_icons_uninstall']))) . '
			});
		</script>';
	}
}
