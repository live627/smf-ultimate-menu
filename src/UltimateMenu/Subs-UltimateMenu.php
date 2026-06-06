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

	if (!empty($modSettings['um_settings'])) {
		$umSettings = json_decode($modSettings['um_settings'], true);
		$umSettings['um_icon_dimension'] = (int) $umSettings['um_icon_dimension'] ?? 32;
	} else {
		$umSettings = [
			'um_fingerprint' => mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8'),
			'um_icon_dimension' => 32,
			'um_secureCode' =>  strval(bin2hex(random_bytes(10))),
		];
	}
}

/**
 * Add the menu button
 * Called by:
 *        integrate_autoload
 */
function um_autoload(&$class_map)
{
	$class_map['UltimateMenu\\'] = 'UltimateMenu/';
}

function um_linking(): void
{
	global $modSettings, $context, $umSettings;

	loadCSSFile('ultimate-menu-buttons' . (!empty($modSettings['minimize_files']) ? '.min' : '') . '.css', ['default_theme' => true, 'minimize' => false, 'order_pos' => 901], 'um_buttons');
	loadCSSFile('ultimate-menu-icons.css', ['default_theme' => true, 'minimize' => false, 'order_pos' => 900], 'um_icons');
}

function um_cache_busting($force = false): string
{
	global $umSettings;

	return !empty($umSettings['um_fingerprint']) && !um_admin_queryString(['action' => 'admin', 'area' => 'umen']) && empty($force) ? $umSettings['um_fingerprint'] : mb_strtolower(strval(bin2hex(random_bytes(5))), 'UTF-8');
}

function um_admin_queryString(array $parameters = []): bool
{
	foreach ($parameters as $request => $value) {
		if (!isset($_GET[$request]) || !str_starts_with($_GET[$request], $value)) {
			return false;
		}
	}

	return true;
}

function um_admin_areas(&$admin_areas): void
{
	global $context, $txt;

	loadLanguage('ManageUltimateMenu');
	$admin_areas['config']['areas']['umen'] = [
		'label' => $txt['um_admin_menu_um'],
		'file' => 'UltimateMenu/ManageUltimateMenu.php',
		'function' => function (): void {
			loadCSSFile('ultimate-menu.css', ['default_theme' => true, 'minimize' => false, 'order_pos' => 902], 'um_admin');
			loadJavaScriptFile('ultimate-menu.js', ['default_theme' => true, 'minimize' => false, 'defer' => true], 'um_admin');

			loadTemplate('ManageUltimateMenu');

			UltimateMenu\ManageUltimateMenu::call();
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
