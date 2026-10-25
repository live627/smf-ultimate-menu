<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.5
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

/**
 * Add the menu button
 * Called by:
 *        integrate_autoload
 */
function um_autoload(&$class_map)
{
	$class_map['UltimateMenu\\'] = 'UltimateMenu/';
}
