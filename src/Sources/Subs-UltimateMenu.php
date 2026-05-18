<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.5
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function um_load_menu(&$menu_buttons): void
{
	global $context, $modSettings, $smcFunc, $user_info, $scripturl, $settings;

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

	$context['um_all_buttons'] = $menu_buttons;
}

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
			'um_secureCode' =>  strval(bin2hex(random_bytes(10)))
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
	if (um_admin_queryString(['action' => 'admin', 'area' => 'packages', 'sa' => 'uninstall', 'package' => 'ultimate-menu'])) {
		$context['html_headers'] .= '
		<script>
			$(document).ready(function(){
				' . implode('', array_map(fn($val) => '$("<li>", {text: \'' . preg_replace("/(?<!\\\\)'/", "\\'", $val) . '\'}).appendTo($("#db_changes_div > ul"));', explode('|', $txt['um_menu_icons_uninstall']))) . '
			});
		</script>';
	}
}
