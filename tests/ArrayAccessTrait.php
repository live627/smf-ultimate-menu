<?php

use PHPUnit\Framework\TestCase;
use UltimateMenu\ArrayAccessTrait;

class ArrayAccessTraitTest extends TestCase
{
	use ArrayAccessTrait;

	private object $testObject;

	protected function setUp(): void
	{
		$this->testObject = $this->getObjectForTrait(ArrayAccessTrait::class);
	}

	public function testOffsetExists()
	{
		$this->testObject->key = 'value';
		$this->assertTrue($this->testObject->offsetExists('key'));
		$this->assertFalse($this->testObject->offsetExists('nonexistent'));
	}

	public function testOffsetGet()
	{
		$this->testObject->key = 'value';
		$this->assertEquals('value', $this->testObject->offsetGet('key'));
		$this->assertNull($this->testObject->offsetGet('nonexistent'));
	}

	public function testOffsetSet()
	{
		$this->testObject->offsetSet('key', 'value');
		$this->assertEquals('value', $this->testObject->key);
	}

	public function testOffsetUnset()
	{
		$this->testObject->key = 'value';
		$this->testObject->offsetUnset('key');
		$this->assertFalse(isset($this->testObject->key));
	}
}
