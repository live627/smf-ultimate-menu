<?php

declare(strict_types = 1);

// What are you doing here, SMF?
define('SMF', 1);

global $context, $modSettings, $smcFunc, $settings, $txt, $user_info;

// Set up necessary global variables
$context = [
	'user' => ['can_mod' => true, 'is_guest' => false, 'id' => 1],
	'right_to_left' => false,
	'session_var' => 'var',
	'session_id' => 'id',
	'current_action' => '',
	'forum_name' => '',
	'html_headers' => '',
	'admin_menu_name' => 'Admin Menu',
];
$settings = ['default_theme_url' => '/theme/url'];
$txt = [
	'admin_menu_title' => 'Admin Menu Title',
	'admin_menu' => 'Admin Menu',
	'admin_menu_description' => 'Admin Menu Description',
	'admin_manage_menu_description' => 'Manage Menu Description',
	'admin_menu_add_page_description' => 'Add Page Description',
	'parent_guests_only' => 'Guests',
	'parent_members_only' => 'Members',
	'login' => '',
];
$user_info = ['is_admin' => true, 'is_guest' => false, 'language' => '', 'id' => 1, 'name' => 'Test User', 'groups' => [0], 'permissions' => []];
$modSettings = ['lastActive' => 0, 'settings_updated' => 0, 'postmod_active' => false];

global $scripturl; 
$scripturl = dirname(__DIR__);

$smcFunc['db_query'] = function ($name, $query, $args)
{
	global $current_item, $modSettings;

	$current_item = 0;

	if (isset($args['variable']) && $args['variable'] == 'integrate_menu_buttons')
		return [[$modSettings[$args['variable']] ?? null]];

	return [['']];
};
$smcFunc['db_fetch_assoc'] = function ($request)
{
	global $current_item;

	return $request[$current_item++] ?? null;
};
$smcFunc['db_fetch_row'] = function ($request)
{
	global $current_item;

	return $request[$current_item++] ?? null;
};
$smcFunc['db_free_result'] = function (): void
{
};
$smcFunc['db_insert'] = function (): void
{
};
$smcFunc['htmltrim'] = fn(string $string): string => trim($string);
$smcFunc['htmlspecialchars'] = fn(string $string): string => htmlspecialchars($string, ENT_QUOTES);

require_once './src/ManageUltimateMenu.php';
require_once './src/Subs-UltimateMenu.php';
require_once './src/Class-UltimateMenu.php';
require_once './src/ManageUltimateMenu.english.php';
require_once './vendor/autoload.php';

require_once './vendor/simplemachines/smf/Sources/Load.php';
require_once './vendor/simplemachines/smf/Sources/Security.php';
require_once './vendor/simplemachines/smf/Sources/Subs.php';
require_once './vendor/simplemachines/smf/Themes/default/languages/index.english.php';
