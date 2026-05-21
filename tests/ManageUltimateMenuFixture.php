<?php

use UltimateMenu\ManageUltimateMenu;

class ManageUltimateMenuFixture extends ManageUltimateMenu
{
	public string $var = '';

	public function ManageMenu(): void
	{
		$this->var = 'Action Executed ' . __FUNCTION__;
	}

	public function __toString(): string
	{
		return $this->var;
	}
}
