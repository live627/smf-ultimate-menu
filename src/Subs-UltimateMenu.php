<?php

/**
 * @package Ultimate Menu mod
 * @version   1.0.5
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function um_load_menu(&$menu_buttons)
{
	global $smcFunc, $user_info, $scripturl, $context, $modSettings;

	// Make damn sure we ALWAYS losd last. Priority: 100!
	$hooks = explode(',', $modSettings['integrate_menu_buttons']);
	$hook = end($hooks);
	if (strpos($hook, 'um_load_menu') === false)
	{
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
	}

	$db_buttons = @unserialize($modSettings['um_menu']);

	if (empty($db_buttons))
		return $menu_buttons;

	foreach ($db_buttons as $key => $row)
	{
		$temp_menu = array(
			'title' => $row['name'],
			'href' => ($row['type'] == 'forum' ? $scripturl . '?' : '') . $row['link'],
			'target' => $row['target'],
			'show' => (allowedTo('admin_forum') || count(array_intersect($user_info['groups'], explode(',', $row['permissions']))) >= 1) && $row['status'] == 'active',
		);

		foreach ($menu_buttons as $area => &$info)
		{
			if ($area == $row['parent'])
			{
				if ($row['position'] == 'before' || $row['position'] == 'after')
				{
					if (array_key_exists($row['parent'], $menu_buttons))
					{
						insert_button(array($row['slug'] => $temp_menu), $menu_buttons, $row['parent'], $row['position']);
						break;
					}
				}

				if ($row['position'] == 'child_of')
				{
					$info['sub_buttons'][$row['slug']] = $temp_menu;
					break;
				}
			}

			if (isset($info['sub_buttons'][$row['parent']]))
			{
				if ($row['position'] == 'before' || $row['position'] == 'after')
				{
					insert_button(array($row['slug'] => $temp_menu), $info['sub_buttons'], $row['parent'], $row['position']);
					break;
				}
				if ($row['position'] == 'child_of')
				{
					$info['sub_buttons'][$row['parent']]['sub_buttons'][$row['slug']] = $temp_menu;
					break;
				}
			}
		}
	}
}

/**
 * Gets all membergroups and filters them according to the parameters.
 *
 * @param string $checked comma-seperated list of all id_groups to be checked (have a mark in the checkbox). Default is an empty array.
 * @param string $disallowed comma-seperated list of all id_groups that are skipped. Default is an empty array.
 * @param bool $inherited whether or not to filter out the inherited groups. Default is false.
 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
 * @since 1.0
 */
function list_groups($checked, $disallowed = '', $inherited = false, $permission = null, $board_id = null)
{
	global $context, $modSettings, $smcFunc, $sourcedir, $txt;

	// We'll need this for loading up the names of each group.
	if (!loadLanguage('ManageBoards'))
		loadLanguage('ManageBoards');

	$checked = explode(',', $checked);
	$disallowed = explode(',', $disallowed);

	// Are we also looking up permissions?
	if ($permission !== null)
	{
		require_once($sourcedir . '/Subs-Members.php');
		$member_groups = groupsAllowedTo($permission, $board_id);
		$disallowed = array_diff(array_keys(list_groups(-3)), $member_groups['allowed']);
	}

	$groups = array();

	if (!in_array(-1, $disallowed))
		// Guests
		$groups[-1] = array(
			'id' => -1,
			'name' => $txt['parent_guests_only'],
			'checked' => in_array(-1, $checked) || in_array(-3, $checked),
			'is_post_group' => false,
		);

	if (!in_array(0, $disallowed))
		// Regular Members
		$groups[0] = array(
			'id' => 0,
			'name' => $txt['parent_members_only'],
			'checked' => in_array(0, $checked) || in_array(-3, $checked),
			'is_post_group' => false,
		);

	// Load membergroups.
	$request = $smcFunc['db_query']('', '
		SELECT group_name, id_group, min_posts
		FROM {db_prefix}membergroups
		WHERE id_group > {int:is_zero}' . (!$inherited ? '
			AND id_parent = {int:not_inherited}' : '') . (!$inherited && empty($modSettings['permission_enable_postgroups']) ? '
			AND min_posts = {int:min_posts}' : ''),
		array(
			'is_zero' => 0,
			'not_inherited' => -2,
			'min_posts' => -1,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		if (!in_array($row['id_group'], $disallowed))
			$groups[(int) $row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => trim($row['group_name']),
				'checked' => in_array($row['id_group'], $checked) || in_array(-3, $checked),
				'is_post_group' => $row['min_posts'] != -1,
			);
	$smcFunc['db_free_result']($request);

	asort($groups);

	return $groups;
}

function insert_button($needle, &$haystack, $insertion_point, $where = 'after')
{
	if (array_key_exists($insertion_point, $haystack))
	{
		$offset = 0;

		foreach ($haystack as $area => $dummy)
			if (++$offset && $area == $insertion_point)
				break;

		if ($where == 'before')
			$offset--;

		$haystack = array_slice($haystack, 0, $offset, true) + $needle + array_slice($haystack, $offset, null, true);
	}
	else
		foreach ($haystack as $stack)
			if (array_key_exists($insertion_point, $haystack[$stack]))
			{
				$offset = 0;

				foreach ($haystack[$stack] as $area => $dummy)
					if (++$offset && $area == $insertion_point)
						break;

				if ($where == 'before')
					$offset--;

				$haystack[$stack] = array_slice($haystack[$stack], 0, $offset, true) + $needle + array_slice($haystack[$stack], $offset, null, true);
				break;
			}
}

function um_admin_areas(&$admin_areas)
{
	global $txt;

	loadLanguage('ManageUltimateMenu');
	$admin_areas['config']['areas']['umen'] = array(
		'label' => $txt['um_admin_menu'],
		'file' => 'ManageUltimateMenu.php',
		'function' => 'Menu',
		'icon' => 'umen.png',
		'subsections' => array(
			'manmenu' => array($txt['um_admin_manage_menu'], ''),
			'addbutton' => array($txt['um_admin_add_button'], ''),
		),
	);
}

?>
