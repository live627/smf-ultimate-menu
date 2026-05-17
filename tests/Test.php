<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class Test extends TestCase
{
	public static function buttonProvider(): \Generator
	{
		yield ['um_button_2', 'before', 'test', 'test1'];
		yield ['test', 'before', 'test1', 'test1'];
		yield ['test', 'after', 'test', 'test1'];
		yield ['test', 'after', 'test1', 'um_button_2'];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('buttonProvider')]
	public function testInsertButton(string $first_key, string $position, string $parent, string $last_key): void
	{
		global $modSettings;

		$modSettings['um_count'] = 2;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"' . $position .  '","link":"t","active":true,"groups":[-1,0,2],"parent":"' . $parent . '","icon":"um--4_b9c4f9a81de.png","sprite":"1"}';
		$haystack = [
			'test' => [
				'title' => 'Test',
				'href' => 'link',
				'show' => true,
			],
			'test1' => [
				'title' => 'Test1',
				'href' => 'link1',
				'show' => true,
			],
		];

		um_load_menu($haystack);

		$this->assertCount(3, $haystack);
		$this->assertArrayHasKey('um_button_2', $haystack);
		$this->assertEquals($first_key, array_key_first($haystack));
		$this->assertEquals($last_key, array_key_last($haystack));
		$this->assertCount(5, $haystack['um_button_2']);
		$this->assertArrayHasKey('title', $haystack['um_button_2']);
		$this->assertArrayHasKey('href', $haystack['um_button_2']);
		$this->assertArrayHasKey('icon', $haystack['um_button_2']);
		$this->assertEquals('Test', $haystack['um_button_2']['title']);
		$this->assertEquals(dirname(__DIR__) . '?t', $haystack['um_button_2']['href']);
		$this->assertSame('_self', $haystack['um_button_2']['target']);
		$this->assertNull($haystack['um_button_2']['icon']);
		$this->assertTrue($haystack['um_button_2']['show']);

		unset($modSettings['um_count'], $modSettings['um_button_2']);
	}

	public function testListButtons(): void
	{
		global $modSettings;

		$modSettings['um_count'] = 2;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"child_of","link":"t","active":true,"groups":[-1,0,2],"parent":"signup","icon":"um--4_b9c4f9a81de.png","sprite":"1"}';
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
		$haystack = (new UltimateMenu)->getButtonNames();
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');

		$this->assertArrayHasKey('um_button_2', $haystack);
		$this->assertSame([1, 'Test'], $haystack['um_button_2']);
		$this->assertArrayHasKey('admin', $haystack);
		$this->assertArrayHasKey('logout', $haystack);
		$this->assertArrayHasKey('signup', $haystack);

		unset($modSettings['um_count'], $modSettings['um_button_2']);
	}

	public function testIntegration(): void
	{
		global $context, $modSettings;

		$modSettings['um_count'] = 4;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"before","link":"t","active":true,"groups":[0],"parent":"home","icon":"um--4_b9c4f9a81de.png","sprite":"1"}';
		$modSettings['um_button_3'] = '{"name":"Test","type":"forum","target":"_self","position":"child_of","link":"t","active":true,"groups":[0],"parent":"um_button_2","icon":"um--4_b9c4f9a81de.png","sprite":"1"}';
		$modSettings['um_button_4'] = '{"name":"Test","type":"forum","target":"_self","position":"after","link":"t","active":true,"groups":[0],"parent":"signup","icon":"um--4_b9c4f9a81de.png","sprite":"1"}';

		add_integration_function('integrate_menu_buttons', 'um_load_menu');
		setupMenuContext();
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');

		$this->assertArrayHasKey('um_button_2', $context['menu_buttons']);
		$this->assertArrayHasKey('sub_buttons', $context['menu_buttons']['um_button_2']);
		$this->assertCount(1, $context['menu_buttons']['um_button_2']['sub_buttons']);
		$this->assertArrayHasKey('um_button_3', $context['menu_buttons']['um_button_2']['sub_buttons']);
		$this->assertEquals('um_button_2', array_key_first($context['menu_buttons']));
		$this->assertEquals('um_button_4', array_key_last($context['menu_buttons']));

		unset($modSettings['um_count'], $modSettings['um_button_2'], $modSettings['um_button_3'], $modSettings['um_button_4'], $context['menu_buttons']);
	}

	public function testDispatch(): void
	{
		$mock = $this->getMockBuilder('ManageUltimateMenu')
			->onlyMethods(['ManageMenu'])
			->disableOriginalConstructor()
			->getMock();

		// Asset that this function is csalled.
		$mock->expects($this->once())
			 ->method('ManageMenu');

		$mock->__construct('');
	}

	public function testSanitizedFilename(): void
	{
		$test = new UltimateMenu();
		$result = $test->sanitizeFilename('test¢¥filename.jpg');
		$this->assertEquals('test--filename.jpg', $result);
	}

	public function testIconFileSorting(): void
	{
		$test = new UltimateMenu();
		$result = $test->icon_files_sort(['um--3_7cd80jyt0eq.png', 'um--35_4cd80isg0eq.png', 'um--1_4cd80hnm0eq.png', 'um--17_4cd80egf7eq.png']);
		$this->assertEquals(['um--1_4cd80hnm0eq.png', 'um--3_7cd80jyt0eq.png', 'um--17_4cd80egf7eq.png', 'um--35_4cd80isg0eq.png'], $result);
	}
}
