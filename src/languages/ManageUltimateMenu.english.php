<?php

declare(strict_types=1);

/**
 * @package Ultimate Menu mod
 * @version   2.0.3
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

$txt['um_admin_menu_um'] = 'Ultimate Menu';
$txt['um_admin_add_button'] = 'Add Button';
$txt['um_admin_manage_menu'] = 'Manage Menu';
$txt['um_admin_menu_um_opt_file'] = 'Choose Icon';
$txt['um_admin_manage_icons'] = 'Manage Files';

$txt['admin_menu_um'] = 'Ultimate Menu';
$txt['admin_menu_um_title'] = 'Menu Settings';
$txt['admin_menu_um_desc'] = 'This page allows you to add and edit custom menu buttons.';

$txt['admin_manage_um_submit'] = 'Submit';
$txt['admin_manage_um_desc'] = 'Manage your Ultimate Menu buttons and icons';
$txt['admin_menu_um_add_button_desc'] = 'Add new buttons to Ultimate Menu';
$txt['admin_manage_um_icons_desc'] = 'Manage your uploaded Ultimate Menu icons';

$txt['um_menu_external_link'] = 'External Link';
$txt['um_menu_forum_link'] = 'Forum Link';
$txt['um_menu_active'] = 'Active';
$txt['um_menu_inactive'] = 'Inactive';
$txt['um_menu_no_buttons'] = 'There are no Buttons yet...';
$txt['um_menu_button_id'] = 'Button ID';
$txt['um_menu_button_name'] = 'Button Name';
$txt['um_menu_button_upload'] = 'Image Upload';
$txt['um_menu_button_type'] = 'Button Type';
$txt['um_menu_button_position'] = 'Button Position';
$txt['um_menu_button_link'] = 'Button Link';
$txt['um_menu_actions'] = 'Actions';
$txt['um_menu_modify'] = 'Modify';
$txt['um_menu_before'] = 'Before';
$txt['um_menu_child_of'] = 'Child of';
$txt['um_menu_after'] = 'After';
$txt['um_menu_remove_selected'] = 'Remove Selected Buttons';
$txt['um_menu_remove_all'] = 'Remove All Buttons';
$txt['um_menu_remove_confirm'] = 'Are you sure you want to remove the selected buttons?';
$txt['um_menu_remove_all_confirm'] = 'Are you sure you want to remove all of the buttons?';
$txt['um_menu_add_title'] = 'Add Button';
$txt['um_menu_edit_title'] = 'Edit Button';
$txt['um_menu_button_name'] = 'Button Name';
$txt['um_menu_button_type'] = 'Button Type';
$txt['um_menu_external'] = 'External Link';
$txt['um_menu_forum'] = 'Forum Link';
$txt['um_menu_button_link_desc'] = 'For forum link you can just put the stuff after "index.php?" in your forum\'s link.';
$txt['um_menu_button_link'] = 'Button Link';
$txt['um_menu_button_perms'] = 'Allowed Groups';
$txt['um_menu_button_guest'] = 'Guests';
$txt['um_menu_button_position'] = 'Button Position';
$txt['um_menu_button_status'] = 'Button Status';
$txt['um_menu_button_active'] = 'Active';
$txt['um_menu_button_inactive'] = 'Not Active';
$txt['um_menu_link_type'] = 'Link Type';
$txt['um_menu_same_window'] = 'Same Window';
$txt['um_menu_new_tab'] = 'New Tab';

$txt['um_menu_button_icon'] = 'Button Icon';
$txt['um_menu_no_icons'] = 'There are no Ultimate Menu icons';
$txt['um_menu_icon_name'] = 'File Name';
$txt['um_menu_icon_assigned'] = 'Assigned Button';
$txt['um_menu_delete_selected'] = 'Delete Selected Icons';
$txt['um_menu_delete_unassigned'] = 'Delete Unassigned Icons';
$txt['um_menu_delete_all'] = 'Delete All Icons';
$txt['um_menu_standardize_all'] = 'Standardize Files';
$txt['um_menu_standardize_all_confirm'] = 'Are you sure that you want to standardize all file names and image dimensions?';
$txt['um_menu_delete_selected_confirm'] = 'Are you sure you want to delete the selected icon files?';
$txt['um_menu_delete_unassigned_confirm'] = 'Are you sure you want to delete all unassigned icon files?';
$txt['um_menu_delete_all_confirm'] = 'Are you sure you want to delete all of the icon files?';
$txt['um_menu_icons_uninstall'] = 'Remove all Ultimate Menu icons from the "um_icons" path';
$txt['um_menu_icons_none'] = 'None';
$txt['um_menu_icon_unassigned'] = '&#11160; Unassigned';
$txt['um_menu_icon_assigned_button'] = '&#8658; %s';
$txt['um_menu_icon_unstandardized'] = '&#11079; Non-standardized';

$txt['um_menu_button_sprite_generate'] = 'Generate';
$txt['um_menu_button_sprite_generate_confirm'] = 'This will generate a fresh sprite containing all current Ultimate Menu button icons.\n\nIf the checkbox is selected, it will auto adjust each button that has an icon set to use the sprite.\n\nAre you sure you want to do this?';
$txt['um_menu_button_sprite_info'] = 'Sprites reduce HTTP requests resulting in quicker page loads.';
$txt['um_menu_button_sprite_detected'] = 'Sprite Available';
$txt['um_menu_button_sprite_undetected'] = 'Sprite Unavailable';
$txt['um_menu_sprite_active'] = 'Sprite';
$txt['um_menu_sprite_inactive'] = 'Icon';
$txt['um_menu_button_sprite_generated'] = 'Sprite was successfully generated!';

// Submission errors
$txt['um_menu_session_verify_fail'] = 'Session verification failed. Please then try again.';
$txt['um_menu_not_found'] = 'The button you tried to edit does not exist!';
$txt['um_menu_errors_create'] = 'The following error or errors occurred while adding your  Button:';
$txt['um_menu_errors_modify'] = 'The following error or errors occurred while editing your  Button:';
$txt['um_menu_numeric_desc'] = 'The button name you chose is all numeric. You must use a name that contains at least one non-numeric character.<br>1e5 is considered numeric (scientific notation) 1.5 is considered numeric (decimal number)';
$txt['um_menu_empty_name'] = 'The name was left empty.';
$txt['um_menu_empty_link'] = 'The link was left empty.';
$txt['um_menu_empty_parent'] = 'The parent was left empty.';
$txt['um_menu_depend_desc'] = 'You must move or remove all child and/or grandchild buttons of this button before modifying it.';
$txt['um_menu_before_child_title'] = 'Make this a child button';
$txt['um_menu_before_child_desc'] = 'Use Child Of to make buttons the child of another!';
$txt['um_menu_mysql'] = 'The button name you chose is already in use!';
$txt['um_menu_filename_illegal'] = 'The filename entered contains illegal characters.';
$txt['um_menu_filename_exists'] = 'The filename entered does not exist within the Ultimate Menu icons path.';
$txt['um_menu_filename_compress'] = 'The file can be used but cannot be compressed.';
$txt['um_menu_filename_unknown'] = 'An unknown error occurred with the image file.';

$txt['um_menu_sprite_create'] = 'Could not create empty sprite image.';
$txt['um_menu_sprite_sourcepath'] = 'Source path does not exist: %s';
$txt['um_menu_sprite_savepath'] = 'Save path does not exist: %s';
$txt['um_menu_sprite_csspath'] = 'CSS path does not exist: %s';
$txt['um_menu_button_sprite_error'] = 'An error occurred attempting to generate the sprite.\nPlease check file permissions and attempt this again.';
$txt['um_menu_button_sprite_drivel'] = 'The intended message is non sequitur!';
