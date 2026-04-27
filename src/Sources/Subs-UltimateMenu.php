<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.3
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function um_load_menu(&$menu_buttons): void
{
	global $smcFunc, $user_info, $scripturl, $modSettings, $settings, $context;

	// Make damn sure we ALWAYS load last. Priority: 100!
	if (substr($modSettings['integrate_menu_buttons'], -12) !== 'um_load_menu') {
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
		return;
	}

	$context['html_headers'] .= '
		<link rel="stylesheet" href="' . $settings['default_theme_url'] . '/css/ultimate-menu-buttons' . (!empty($modSettings['minimize_files']) ? '.min' : '') . '.css?v=' . um_cache_busting(false) . '">';

	for ($i = 1; $i <= ($modSettings['um_count'] ?? 0); $i++) {
		$key = 'um_button_' . $i;
		if (!isset($modSettings[$key])) {
			continue;
		}
		$row = json_decode($modSettings[$key], true);
		$temp_menu = [
			'title' => $row['name'],
			'href' => ($row['type'] == 'forum' ? $scripturl . '?' : '') . $row['link'],
			'target' => $row['target'],
			'icon' => !empty($row['icon']) && empty($row['sprite']) ? 'um_icons/' . $row['icon'] : (!empty($row['sprite']) ? null : 'um_icons/blank.png'),
			'show' => (allowedTo('admin_forum') || array_intersect($user_info['groups'], $row['groups']) != []) && $row['active'],
		];

		recursive_button($temp_menu, $menu_buttons, $row['parent'], $row['position'], $key);
	}
}

function um_replay_menu(&$menu_buttons)
{
	global $context;

	$context['replayed_menu_buttons'] = $menu_buttons;
}

function recursive_button(array $needle, array &$haystack, $insertion_point, $where, $key): void
{
	foreach ($haystack as $area => &$info) {
		if ($area == $insertion_point) {
			switch ($where) {
				case 'before':
				case 'after':
					insert_button([$key => $needle], $haystack, $insertion_point, $where);
					break 2;

				case 'child_of':
					$info['sub_buttons'][$key] = $needle;
					break 2;
			}
		} elseif (!empty($info['sub_buttons'])) {
			recursive_button($needle, $info['sub_buttons'], $insertion_point, $where, $key);
		}
	}
}

function insert_button(array $needle, array &$haystack, $insertion_point, $where = 'after'): void
{
	$offset = 0;

	foreach ($haystack as $area => $dummy) {
		if (++$offset && $area == $insertion_point) {
			break;
		}
	}

	if ($where == 'before') {
		$offset--;
	}

	$haystack = array_slice($haystack, 0, $offset, true) + $needle + array_slice($haystack, $offset, null, true);
}

function um_cache_busting($force = false): string
{
	global $modSettings;

	list($count, $requestPairs) = [0, ['action' => 'admin', 'area' => 'umen']];
	array_walk_recursive($requestPairs, function($value, $request) use (&$count) {
		$count = isset($_REQUEST[$request]) && stripos($_REQUEST[$request], $value) !== false ? $count + 1 : $count;
	}, $count);

	return !empty($modSettings['um_fingerprint']) && $count < 2 && empty($force) ? $modSettings['um_fingerprint'] : mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8');
}

function um_admin_areas(&$admin_areas): void
{
	global $context, $txt;

	loadLanguage('ManageUltimateMenu');
	$admin_areas['config']['areas']['umen'] = [
		'label' => $txt['um_admin_menu_um'],
		'file' => 'ManageUltimateMenu.php',
		'function' => function(): void
		{
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
	list($count, $requestPairs) = [0, ['action' => 'admin', 'area' => 'packages', 'sa' => 'uninstall', 'package' => 'ultimate-menu']];
	array_walk_recursive($requestPairs, function($value, $request) use (&$count) {
		$count = isset($_REQUEST[$request]) && stripos($_REQUEST[$request], $value) !== false ? $count + 1 : $count;
	}, $count);

	if ($count > 3) {
		$context['html_headers'] .= '
		<script>
			$(document).ready(function(){
				$("#db_changes_div > ul").append(\'<li>' . $txt['um_menu_icons_uninstall'] . '</li>\');
			});
		</script>';
	}
}
