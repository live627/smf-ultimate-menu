<?php

/**
 * @package   Ultimate Menu mod
 * @version   2.0.5
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace UltimateMenu;

/**
 * Provides a uniform interface for accessing and modifying the properties
 * of an object as if it were an array.
 *
 * Exposing an object as an array is necessary when interacting with SMF, such
 * as when using createList() to operate on an array of {@link EntityInterface} objects.
 */
trait ArrayAccessTrait
{
	/**
	 * Check whether the given offset exists.
	 *
	 * @param mixed $offset The offset to check.
	 *
	 * @return bool True if the offset exists, false otherwise.
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->$offset);
	}

	/**
	 * Retrieve the value of the given offset.
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed|null The value of the offset, or null if it does not exist.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->$offset ?? null;
	}

	/**
	 * Set the value of the given offset.
	 *
	 * @param mixed $offset The offset to set.
	 * @param mixed $value  The value to assign to the offset.
	 */
	public function offsetSet($offset, $value): void
	{
		$this->$offset = $value;
	}

	/**
	 * Unset the given offset.
	 *
	 * @param mixed $offset The offset to unset.
	 *
	 * @param  mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		unset($this->$offset);
	}
}
