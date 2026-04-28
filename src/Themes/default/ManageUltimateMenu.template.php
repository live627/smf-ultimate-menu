<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.4
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function template_form_above(): void
{
	global $context, $scripturl;

	echo '
		<form action="', $scripturl, '?action=admin;area=umen;sa=savebutton" enctype="multipart/form-data" method="post" accept-charset="', $context['character_set'], '" name="postmodify" id="postmodify">
			<div class="cat_bar">
				<h3 class="catbg">
					', $context['page_title'], '
				</h3>
			</div>
			<div class="roundframe noup">';
}

function template_errors_above(): void
{
	global $context, $txt;

	if (!empty($context['post_error'])) {
		echo '
					<div class="errorbox" id="errors">
						<strong>', $txt[$context['error_title']], '</strong>
						<ul>';

		foreach ($context['post_error'] as $error) {
			echo '
							<li>', $txt[$error], '</li>';
		}

		echo '
						</ul>
					</div>';
	}
}

function template_errors_below(): void
{
}

function template_main(): void
{
	global $context, $txt, $scripturl, $settings;

	$sel = fn(bool $x, string $str): string => $x ? ' ' . $str : '';

	echo '
					<dl class="settings um_dl_padding">
						<dt>
							<span class="um_strong">', $txt['um_menu_button_name'], ':</span>
						</dt>
						<dd>
							<input type="text" name="name" value="', $context['button_data']['name'], '" style="width: 100%;">
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_position'], ':</span>
						</dt>
						<dd>
							<select name="position" size="10" style="width: 22%;">';

	foreach (['after', 'child_of', 'before'] as $v) {
		printf(
			'
								<option value="%s"%s>%s...</option>',
			$v,
			$sel($context['button_data']['position'] == $v, 'selected'),
			$txt['um_menu_' . $v]
		);
	}

	echo '
							</select>
							<select name="parent" size="10" style="width: 75%;">';

	foreach ($context['button_names'] as $idx => $title) {
		printf(
			'
								<option value="%s"%s>%s</option>',
			$idx,
			$sel($context['button_data']['parent'] == $idx, 'selected'),
			str_repeat('&emsp;', $title[0] * 2) . $title[1]
		);
	}

	echo '
							</select>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_type'], ':</span>
						</dt>
						<dd>
							<span class="um_grid_line">
								<input id="um_type_forum" type="radio" name="type" value="forum"', $sel($context['button_data']['type'] == 'forum', 'checked'), '>
								<label for="um_type_forum">', $txt['um_menu_forum'], '</label>
							</span>
							<span class="um_grid_line">
								<input id="um_type_external" type="radio" name="type" value="external"', $sel($context['button_data']['type'] == 'external', 'checked'), '>
								<label for="um_type_external">', $txt['um_menu_external'], '</label>
							</span>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_link_type'], ':</span>
						</dt>
						<dd>
							<span class="um_grid_line">
								<input id="um_target_self" type="radio" name="target" value="_self"', $sel($context['button_data']['target'] == '_self', 'checked'), '>
								<label for="um_target_self">', $txt['um_menu_same_window'], '</label>
							</span>
							<span class="um_grid_line">
								<input type="radio" name="target" value="_blank"', $sel($context['button_data']['target'] == '_blank', 'checked'), '>
								<label for="um_target_blank">', $txt['um_menu_new_tab'], '</label>
							</span>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_link'], ':</span>
						</dt>
						<dd>
							<input type="text" name="link" value="', $context['button_data']['link'], '" style="width: 100%;">
							<span class="smalltext">', $txt['um_menu_button_link_desc'], '</span>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_perms'], ':</span>
						</dt>
						<dd>
							<fieldset id="group_perms">
								<legend>', $txt['avatar_select_permission'], '</legend>';

	foreach ($context['button_data']['permissions'] as $id => $permission) {
		echo '
								<label class="um_grid_line">
									<input type="checkbox" name="permissions[]" value="', $id, '"', $sel($permission['checked'], 'checked'), '>
									<span' . ($permission['is_post_group'] ? ' title="' . $txt['mboards_groups_post_group'] . '"' : '') . '>', $permission['name'], '</span>
								</label>';
	}

	echo '
								<label class="um_grid_line">
									<input type="checkbox"', $sel($context['all_groups_checked'], 'checked'), '>
									<span class="um_italic">', $txt['check_all'], '</span>
								</label>
							</fieldset>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_status'], ':</span>
						</dt>
						<dd>
							<span class="um_grid_line">
								<input id="um_status_active" type="radio" name="status" value="active"', $sel($context['button_data']['status'] == 'active', 'checked'), '>
								<label for="um_status_active">', $txt['um_menu_button_active'], '</label>
							</span>
							<span class="um_grid_line">
								<input id="um_status_inactive" type="radio" name="status" value="inactive"', $sel($context['button_data']['status'] == 'inactive', 'checked'), '>
								<label for="um_status_inactive">', $txt['um_menu_button_inactive'], '</label>
							</span>
						</dd>
						<dt>
							<span class="um_strong">', (!empty($context['um_sprite_detected']) ? $txt['um_menu_button_sprite_detected'] : $txt['um_menu_button_sprite_undetected']), ':</span>
						</dt>
						<dd>
							<span class="um_sprite_info">', $txt['um_menu_button_sprite_info'], '</span>
							<span class="um_grid_line">
								<input id="um_sprite" type="radio" name="sprite" value="1"', (!empty($context['button_data']['sprite']) ? ' checked' : ''), (empty($context['um_sprite_detected']) ? ' disabled' : ''), '>
								<label for="um_sprite_active">', $txt['um_menu_sprite_active'], '</label>
								<span class="um_icon_pseudo um_button_' . $context['button_data']['id'] . '" style="visibility: ' . (!empty($context['button_data']['sprite']) && !empty($context['um_sprite_detected']) ? 'visible' : 'hidden') . ';"></span>
							</span>
							<span class="um_grid_line">
								<input id="um_sprite_inactive" type="radio" name="sprite" value="0"', (empty($context['button_data']['sprite']) || empty($context['um_sprite_detected']) ? ' checked' : ''), '>
								<label for="um_sprite_inactive">', $txt['um_menu_sprite_inactive'], '</label>
								<span class="um_icon_container" style="background-image: url(\'' . $context['button_data']['image'] . '\');visibility: ' . (empty($context['button_data']['sprite']) ? 'visible' : 'hidden') . ';"></span>
							</span>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_upload'], ':</span>
						</dt>
						<dd class="um_dd_file">
							<span class="um_file_button">
								<input id="um_file" type="file" name="attachment" accept="image/png, image/jpeg, .png, .jpg, .jpeg" value="', $context['button_data']['icon'], '" style="width: 100%;">
							</span>
							<span class="um_file_loading">
								<span id="um_loader"></span>
							</span>
						</dd>
						<dt>
							<span class="um_strong">', $txt['um_menu_button_icon'], ':</span>
						</dt>
						<dd class="um_dd_files_list windowbg2">
							<span id="um_icon_list">
								<select id="um_icon_select" name="icon">
									<optgroup label="' . $txt['um_admin_menu_um_opt_file'] . '">';
	foreach ($context['um_button_icons'] as $filename) {
		echo '
									<option value="', (!empty($filename) ? $filename : '______'), '"', ($context['button_data']['icon'] == $filename ? ' selected="selected"' : ''), '>
										', (!empty($filename) && $filename != '______' ? $filename : $txt['um_menu_icons_none']), '
									</option>';
	}

	echo '
								</select>
								<span style="display: none;" id="advum_icons">
									<span class="ultimateMenu_drop">
										<span class="ultimateMenuDrop">
											<span style="display: none;" class="um_hideSelect" id="um_hideSelect">
												' . $txt['um_admin_menu_um_opt_file'] . '
											</span>
										</span>
									</span>
								</span>
							</span>
						</dd>
					</dl>';
}

function template_form_below(): void
{
	global $settings, $context, $scripturl, $txt;

	echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<input name="in" value="', $context['button_data']['id'], '" type="hidden">
					<div class="righttext padding">
						<input name="submit" value="', $txt['admin_manage_um_submit'], '" class="button" type="submit">
					</div>
				</div>
			</form>';
}
