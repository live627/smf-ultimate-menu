<?php

use PHPUnit\Framework\TestCase;
use UltimateMenu\ActionInterface;

class ActionTraitTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		require_once __DIR__ . '/ActionFixture.php';
	}

	public function testLoadCreatesSingleInstance()
	{
		$instance1 = ActionFixture::load();
		$instance2 = ActionFixture::load();

		$this->assertInstanceOf(ActionInterface::class, $instance1);
		$this->assertInstanceOf(ActionFixture::class, $instance1);
		$this->assertInstanceOf(ActionInterface::class, $instance2);
		$this->assertInstanceOf(ActionFixture::class, $instance2);
		$this->assertSame($instance1, $instance2);
	}

	public function testCallInvokesExecute()
	{
		ActionFixture::call();
		$this->assertEquals('Action Executed', ActionFixture::load());
	}
}
