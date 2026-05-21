<?php

use UltimateMenu\ActionInterface;
use UltimateMenu\ActionTrait;

class ActionFixture implements ActionInterface, Stringable
{
	use ActionTrait;

	public string $var = '';

	public function execute(): void
	{
		$this->var = 'Action Executed';
	}

	public function __toString(): string
	{
		return $this->var;
	}
}
