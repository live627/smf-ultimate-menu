<?php

declare(strict_types = 1);

require_once './src/ManageUltimateMenu.php';
require_once './src/Subs-UltimateMenu.php';
require_once './src/Class-UltimateMenu.php';
require_once './src/ManageUltimateMenu.english.php';
require_once './vendor/autoload.php';

// What are you doing here, SMF?
define('SMF', 1);

global $context;
$user_info = ['is_admin' => true, 'is_guest' => false, 'language' => '', 'groups' => [0], 'permissions' => []];
$context = [
	'user' => ['can_mod' => true, 'is_guest' => false, 'id' => 1],
	'right_to_left' => false,
	'session_var' => 'var',
	'session_id' => 'id',
	'current_action' => '',
	'forum_name' => '',
	'admin_menu_name' => '',
];
$modSettings = ['lastActive' => 0, 'settings_updated' => 0, 'postmod_active' => false];

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

require_once './vendor/simplemachines/smf2.1/Sources/Load.php';
require_once './vendor/simplemachines/smf2.1/Sources/Security.php';
require_once './vendor/simplemachines/smf2.1/Sources/Subs.php';
require_once './vendor/simplemachines/smf2.1/Themes/default/languages/index.english.php';
