<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.1
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

function template_form_above(): void
{
	global $context, $scripturl;

	echo '
		<form action="', $scripturl, '?action=admin;area=umen;sa=savebutton" method="post" accept-charset="', $context['character_set'], '" name="postmodify" id="postmodify">
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

	if (!empty($context['post_error']))
	{
		echo '
					<div class="errorbox" id="errors">
						<strong>', $txt[$context['error_title']], '</strong>
						<ul>';

		foreach ($context['post_error'] as $error)
			echo '
							<li>', $txt[$error], '</li>';

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
	global $context, $txt, $scripturl;

	$sel = fn(bool $x, string $str): string => $x ? ' ' . $str : '';

	echo '
					<dl class="settings">
						<dt>
							<strong>', $txt['um_menu_button_name'], ':</strong>
						</dt>
						<dd>
							<input type="text" name="name" value="', $context['button_data']['name'], '" style="width: 100%;" />
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_position'], ':</strong>
						</dt>
						<dd>
							<select name="position" size="10" style="width: 22%;">';

	foreach (['after', 'child_of', 'before'] as $v)
		printf(
			'
								<option value="%s"%s>%s...</option>',
			$v,
			$sel($context['button_data']['position'] == $v, 'selected'),
			$txt['um_menu_' . $v]
		);

	echo '
							</select>
							<select name="parent" size="10" style="width: 75%;">';

	foreach ($context['button_names'] as $idx => $title)
		printf(
			'
								<option value="%s"%s>%s</option>',
			$idx,
			$sel($context['button_data']['parent'] == $idx, 'selected'),
			str_repeat('&emsp;', $title[0] * 2) . $title[1]
		);

	echo '
							</select>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_type'], ':</strong>
						</dt>
						<dd>
							<input type="radio" name="type" value="forum"', $sel($context['button_data']['type'] == 'forum', 'checked'), '/>', $txt['um_menu_forum'], '<br />
							<input type="radio" name="type" value="external"', $sel($context['button_data']['type'] == 'external', 'checked'), '/>', $txt['um_menu_external'], '
						</dd>
						<dt>
							<strong>', $txt['um_menu_link_type'], ':</strong>
						</dt>
						<dd>
							<input type="radio" name="target" value="_self"', $sel($context['button_data']['target'] == '_self', 'checked'), '/>', $txt['um_menu_same_window'], '<br />
							<input type="radio" name="target" value="_blank"', $sel($context['button_data']['target'] == '_blank', 'checked'), '/>', $txt['um_menu_new_tab'], '
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_link'], ':</strong><br />
						</dt>
						<dd>
							<input type="text" name="link" value="', $context['button_data']['link'], '" style="width: 100%;" />
							<span class="smalltext">', $txt['um_menu_button_link_desc'], '</span>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_perms'], ':</strong>
						</dt>
						<dd>
							<fieldset id="group_perms">
								<legend>', $txt['avatar_select_permission'], '</legend>';

	foreach ($context['button_data']['permissions'] as $id => $permission)
	{
		echo '
								<label>
									<input type="checkbox" name="permissions[]" value="', $id, '"', $sel($permission['checked'], 'checked'), ' />
									<span';

		if ($permission['is_post_group'])
			echo ' title="' . $txt['mboards_groups_post_group'] . '"';

		echo '>', $permission['name'], '</span>
								</label>
								<br>';
	}

	echo '
								<label>
									<input type="checkbox"', $sel($context['all_groups_checked'], 'checked'), ' />
									<em>', $txt['check_all'], '</em>
								</label>
							</fieldset>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_status'], ':</strong>
						</dt>
						<dd>
							<input type="radio" name="status" value="active"', $sel($context['button_data']['status'] == 'active', 'checked'), ' />', $txt['um_menu_button_active'], ' <br />
							<input type="radio" name="status" value="inactive"', $sel($context['button_data']['status'] == 'inactive', 'checked'), ' />', $txt['um_menu_button_inactive'], '
						</dd>
					</dl>';
}

function template_form_below(): void
{
	global $context, $txt;

	echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input name="in" value="', $context['button_data']['id'], '" type="hidden" />
					<div class="righttext padding">
						<input name="submit" value="', $txt['admin_manage_menu_submit'], '" class="button" type="submit" />
					</div>
				</div>
			</form>
			<script>
				var
					el = document.createElement("a"),
					div = document.getElementById("group_perms"),
					l = div.firstElementChild,
					a = document.createElement("a");
				el.textContent = l.textContent;
				el.className = "toggle_down";
				el.href = "#";
				el.style.display = "";
				el.addEventListener("click", function(event)
				{
					div.classList.remove("hidden");
					this.style.display = "none";
					event.stopPropagation();
					event.preventDefault();
				});
				div.classList.add("hidden");
				div.parentNode.appendChild(el);
				a.className = "toggle_up";
				a.textContent = l.textContent;
				a.href = "#";
				a.style.display = "";
				a.addEventListener("click", function(event)
				{
					div.classList.add("hidden");
					el.style.display = "";
					event.stopPropagation();
					event.preventDefault();
				});
				l.textContent = "";
				l.appendChild(a);
				div.lastElementChild.firstElementChild.addEventListener("click", function()
				{
					invertAll(this, this.form, "permissions[]");
				});
			</script>';
}
