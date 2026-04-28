<?php

declare(strict_types=1);

global $scripturl, $settings, $sourcedir, $boarddir, $context, $txt, $umButtonKeyCount;

$sourcePath = is_dir('./src/Sources') ? './src/Sources' : './src';
$langPath = is_dir('./src/languages') ? './src/languages' : './src';

// detect old/new directory scenario to determine $temp_menu[] key count
$umButtonKeyCount = is_dir('./src/Sources') ? 5 : 4;

require_once $sourcePath . '/ManageUltimateMenu.php';
require_once $sourcePath . '/Subs-UltimateMenu.php';
require_once $sourcePath . '/Class-UltimateMenu.php';
require_once $langPath . '/ManageUltimateMenu.english.php';
require_once './vendor/autoload.php';

// What are you doing here, SMF?
define('SMF', 1);

$user_info = [
	'is_admin' => true,
	'is_guest' => false,
	'language' => '',
	'groups' => [0],
	'permissions' => []
];

$context = [
	'user' => ['can_mod' => true, 'is_guest' => false, 'id' => 1],
	'right_to_left' => false,
	'session_var' => 'var',
	'session_id' => 'id',
	'current_action' => '',
	'forum_name' => '',
	'admin_menu_name' => '',
	'html_headers' => ''
];

$modSettings = [
	'lastActive' => 0,
	'settings_updated' => 0,
	'postmod_active' => false
];

$settings = [
	'theme_dir' => './src/Themes/default',
	'default_theme_dir' => './src/Themes/default',
	'theme_url' => dirname(__DIR__) . '/Themes/default',
	'default_theme_url' => dirname(__DIR__) . '/Themes/default',
	'images_url' => dirname(__DIR__) . '/Themes/default/images',
];

$scripturl = dirname(__DIR__);
$sourcedir = './vendor/simplemachines/smf/Sources';
$boarddir = './vendor/simplemachines/smf';
$txt['assert_count'] = 'Test array does not contain %d elements';

$smcFunc['db_query'] = function($name, $query, $args)
{
	global $current_item, $modSettings;

	$current_item = 0;

	if (isset($args['variable']) && $args['variable'] == 'integrate_menu_buttons') {
		return [[$modSettings[$args['variable']] ?? null]];
	}

	return [['']];
};
$smcFunc['db_fetch_assoc'] = function($request)
{
	global $current_item;

	return $request[$current_item++] ?? null;
};
$smcFunc['db_fetch_row'] = function($request)
{
	global $current_item;

	return $request[$current_item++] ?? null;
};
$smcFunc['db_free_result'] = function(): void
{
};
$smcFunc['db_insert'] = function(): void
{
};
$smcFunc['htmltrim'] = fn(string $string): string => trim($string);
$smcFunc['htmlspecialchars'] = fn(string $string): string => htmlspecialchars($string, ENT_QUOTES);

require_once './vendor/simplemachines/smf/Sources/Load.php';
require_once './vendor/simplemachines/smf/Sources/Security.php';
require_once './vendor/simplemachines/smf/Sources/Subs.php';
require_once './vendor/simplemachines/smf/Sources/Errors.php';
require_once './vendor/simplemachines/smf/Themes/default/languages/index.english.php';
