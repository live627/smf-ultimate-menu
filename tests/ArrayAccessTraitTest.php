<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ArrayAccessTraitTest extends TestCase
{
	private ArrayAccessTraitObject $testObject;

	public static function setUpBeforeClass(): void
	{
		require_once __DIR__ . '/ArrayAccessTraitObject.php';
	}

	protected function setUp(): void
	{
		$this->testObject = new ArrayAccessTraitObject();
	}

	public function testOffsetExists(): void
	{
		$this->testObject['key'] = 'value';

		$this->assertTrue($this->testObject->offsetExists('key'));
		$this->assertFalse($this->testObject->offsetExists('nonexistent'));
	}

	public function testOffsetGet(): void
	{
		$this->testObject['key'] = 'value';

		$this->assertSame('value', $this->testObject->offsetGet('key'));
		$this->assertNull($this->testObject->offsetGet('nonexistent'));
	}

	public function testOffsetSet(): void
	{
		$this->testObject->offsetSet('key', 'value');

		$this->assertSame('value', $this->testObject['key']);
	}

	public function testOffsetUnset(): void
	{
		$this->testObject['key'] = 'value';

		$this->testObject->offsetUnset('key');

		$this->assertFalse(isset($this->testObject['key']));
	}
}
