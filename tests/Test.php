<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

final class Test extends TestCase
{
	public function buttonProvider(): array
	{
		$btn = ['href' => 'link', 'show' => true];
		$btn1 = ['href' => 'link1', 'show' => true];

		return [
			[
				'test',
				[
					'before' => [
						'inserted_test' => $btn,
						'test' => $btn,
						'test1' => $btn1,
					],
					'after' => [
						'test' => $btn,
						'inserted_test' => $btn,
						'test1' => $btn1,
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'inserted_test' => $btn,
							],
						],
						'test1' => $btn1,
					],
				],
			],
			[
				'test1',
				[
					'before' => [
						'test' => $btn,
						'inserted_test1' => $btn,
						'test1' => $btn1,
					],
					'after' => [
						'test' => $btn,
						'test1' => $btn1,
						'inserted_test1' => $btn,
					],
					'child_of' => [
						'test' => $btn,
						'test1' => [
							'href' => 'link1',
							'show' => true,
							'sub_buttons' => [
								'inserted_test1' => $btn,
							],
						],
					],
				],
			],
			[
				'dungeon',
				[
					'before' => [
						'test' => $btn,
						'test1' => $btn1,
					],
					'after' => [
						'test' => $btn,
						'test1' => $btn1,
					],
					'child_of' => [
						'test' => $btn,
						'test1' => $btn1,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider buttonProvider
	 */
	public function testInsertButton(string $insertion_point, array $expected): void
	{
		$btn = ['href' => 'link', 'show' => true];
		$btn1 = ['href' => 'link1', 'show' => true];

		foreach (['before', 'after', 'child_of'] as $where)
		{
			$haystack = ['test' => $btn, 'test1' => $btn1];
			recursive_button(
				$btn,
				$haystack,
				$insertion_point,
				$where,
				'inserted_' . $insertion_point
			);
			$this->assertSame($expected[$where], $haystack);
		}
	}

	public function childButtonProvider(): array
	{
		$btn = ['href' => 'link', 'show' => true];
		return [
			[
				'sub',
				[
					'before' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'inserted_sub' => $btn,
								'sub' => $btn ,
								'sub1' => $btn,
							],
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn,
								'inserted_sub' => $btn,
								'sub1' => $btn,
							],
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => [
									'href' => 'link',
									'show' => true,
									'sub_buttons' => [
										'inserted_sub' => $btn,
									],
								],
								'sub1' => $btn,
							],
						],
					],
				],
			],
			[
				'sub1',
				[
					'before' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn,
								'inserted_sub1' => $btn,
								'sub1' => $btn,
							],
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn,
								'sub1' => $btn,
								'inserted_sub1' => $btn,
							],
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn,
								'sub1' => [
									'href' => 'link',
									'show' => true,
									'sub_buttons' => [
										'inserted_sub1' => $btn,
									],
								],
							],
						],
					],
				],
			],
			[
				'dungeon',
				[
					'before' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn, 'sub1' => $btn,
							],
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn, 'sub1' => $btn,
							],
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => $btn, 'sub1' => $btn,
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider childButtonProvider
	 */
	public function testInsertChildButton(string $insertion_point, array $expected): void
	{
		foreach (['before', 'after', 'child_of'] as $where)
		{
			$haystack = [
				'test' => [
					'href' => 'link',
					'show' => true,
					'sub_buttons' => [
						'sub' => [
							'href' => 'link',
							'show' => true,
						],
						'sub1' => [
							'href' => 'link',
							'show' => true,
						],
					],
				],
			];
			recursive_button(
				[
					'href' => 'link',
					'show' => true,
				],
				$haystack,
				$insertion_point,
				$where,
				'inserted_' . $insertion_point
			);
			$this->assertSame($expected[$where], $haystack);
		}
	}

	public function testHook(): void
	{
		global $modSettings;

		add_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'my_func');
		$this->assertEquals('um_load_menu,my_func', $modSettings['integrate_menu_buttons']);
		$dummy = [];
		um_load_menu($dummy);
		$this->assertEquals('my_func,um_load_menu', $modSettings['integrate_menu_buttons']);
		$dummy = [];
		um_load_menu($dummy);
		$this->assertEquals('my_func,um_load_menu', $modSettings['integrate_menu_buttons']);
	}

	public function testMenu(): void
	{
		global $modSettings;

		$modSettings['um_count'] = 2;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"before","link":"t","active":true,"groups":[-1,0,2],"parent":"register"}';
		$haystack = ['register' => 'l'];
		um_load_menu($haystack);
		$this->assertCount(2, $haystack);
		$this->assertArrayHasKey('um_button_2', $haystack);
		$this->assertCount(4, $haystack['um_button_2']);
		$this->assertArrayHasKey('title', $haystack['um_button_2']);
		$this->assertArrayHasKey('href', $haystack['um_button_2']);
		$this->assertEquals('Test', $haystack['um_button_2']['title']);
		$this->assertEquals('?t', $haystack['um_button_2']['href']);
		unset($modSettings['um_count'], $modSettings['um_button_2']);
	}

	public function testListButtons(): void
	{
		global $modSettings;

		$modSettings['um_count'] = 2;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"before","link":"t","active":true,"groups":[-1,0,2],"parent":"register"}';
		$haystack = (new UltimateMenu)->getButtonNames();
		$this->assertArrayHasKey('um_button_2', $haystack);
		$this->assertCount(2, $haystack['um_button_2']);
		$this->assertSame([0, 'Test'], $haystack['um_button_2']);
		$this->assertArrayHasKey('help', $haystack);
		$this->assertArrayHasKey('admin', $haystack);
		$this->assertArrayHasKey('profile', $haystack);
		$this->assertArrayHasKey('logout', $haystack);
		$this->assertArrayHasKey('register', $haystack);
		unset($modSettings['um_count'], $modSettings['um_button_2']);
	}

	public function testIntegration(): void
	{
		global $context, $modSettings;

		$modSettings['um_count'] = 2;
		$modSettings['um_button_2'] = '{"name":"Test","type":"forum","target":"_self","position":"before","link":"t","active":true,"groups":[0],"parent":"help"}';
		setupMenuContext();
		$this->assertArrayHasKey('um_button_2', $context['menu_buttons']);
		$this->assertArrayHasKey('title', $context['menu_buttons']['um_button_2']);
		$this->assertArrayHasKey('href', $context['menu_buttons']['um_button_2']);
		$this->assertEquals('Test', $context['menu_buttons']['um_button_2']['title']);
		$this->assertEquals('?t', $context['menu_buttons']['um_button_2']['href']);
		unset($modSettings['um_count'], $modSettings['um_button_2']);
	}

	public function testDispatch(): void
	{
		$mock = $this->getMockBuilder('ManageUltimateMenu')
			->setMethods(array('ManageMenu'))
			->disableOriginalConstructor()
			->getMock();

		$mock->expects($this->once())
			 ->method('ManageMenu');

		$mock->__construct('');
	}
}
