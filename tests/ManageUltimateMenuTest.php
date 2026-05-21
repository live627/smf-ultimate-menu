<?php

use PHPUnit\Framework\TestCase;
use UltimateMenu\ActionInterface;

class ManageUltimateMenuTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		require_once __DIR__ . '/ManageUltimateMenuFixture.php';
	}

	public function testDispatch(): void
	{
		$obj = ManageUltimateMenuFixture::load();

		$this->assertInstanceOf(ActionInterface::class, $obj);

		$obj->execute();

		$this->assertEquals('Action Executed ManageMenu', (string) $obj);
	}
}
