<?php

declare(strict_types=1);

use UltimateMenu\ArrayAccessTrait;

final class ArrayAccessTraitObject implements ArrayAccess
{
	use ArrayAccessTrait;

	public string $key;
}
