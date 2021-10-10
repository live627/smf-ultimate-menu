<?php

/**
 * @package   Ultimate Menu mod
 * @version   "1.1.3"
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function um_load_menu(&$menu_buttons)
{
	global $smcFunc, $user_info, $scripturl, $modSettings;

	// Make damn sure we ALWAYS load last. Priority: 100!
	if (substr($modSettings['integrate_menu_buttons'], -12) !== 'um_load_menu')
	{
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
	}

	$num_buttons = isset($modSettings['um_count'])
		? $modSettings['um_count']
		: 0;

	for ($i = 1; $i <= $num_buttons; $i++)
	{
		$key = 'um_button_' . $i;

		if (!isset($modSettings[$key]))
			continue;
		$row = json_decode($modSettings[$key], true);
		$temp_menu = [
			'title' => $row['name'],
			'href' => ($row['type'] == 'forum' ? $scripturl . '?' : '') . $row['link'],
			'target' => $row['target'],
			'show' => (allowedTo('admin_forum') || array_intersect($user_info['groups'], explode(',', $row['permissions'])) != []) && $row['status'] == 'active',
		];

		recursive_button($temp_menu, $menu_buttons, $row['parent'], $row['position'], $key);
	}
}

function um_replay_menu(&$menu_buttons)
{
	global $context;

	$context['replayed_menu_buttons'] = $menu_buttons;
}

function recursive_button(array $needle, array &$haystack, $insertion_point, $where, $key)
{
	foreach ($haystack as $area => &$info)
	{
		if ($area == $insertion_point)
		{
			if ($where == 'before' || $where == 'after')
			{
				insert_button([$key => $needle], $haystack, $insertion_point, $where);
				break;
			}

			if ($where == 'child_of')
			{
				$info['sub_buttons'][$key] = $needle;
				break;
			}
		}
		elseif (!empty($info['sub_buttons']))
			recursive_button($needle, $info['sub_buttons'], $insertion_point, $where, $key);
	}
}

function insert_button(array $needle, array &$haystack, $insertion_point, $where = 'after')
{
	$offset = 0;

	foreach ($haystack as $area => $dummy)
		if (++$offset && $area == $insertion_point)
			break;

	if ($where == 'before')
		$offset--;

	$haystack = array_slice($haystack, 0, $offset, true) + $needle + array_slice($haystack, $offset, null, true);
}

function um_admin_areas(&$admin_areas)
{
	global $txt;

	loadLanguage('ManageUltimateMenu');
	$admin_areas['config']['areas']['umen'] = [
		'label' => $txt['um_admin_menu'],
		'file' => 'ManageUltimateMenu.php',
		'function' => function (): void
		{
			new ManageUltimateMenu;
		},
		'icon' => 'umen.png',
		'subsections' => [
			'manmenu' => [$txt['um_admin_manage_menu'], ''],
			'addbutton' => [$txt['um_admin_add_button'], ''],
		],
	];
}