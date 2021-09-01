<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

final class Test extends TestCase
{
	public function buttonProvider(): array
	{
		return [
			[
				'test',
				[
					'before' => [
						'inserted_test' => [
							'href' => 'link',
							'show' => true,
						],
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'inserted_test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'inserted_test' => [
									'href' => 'link',
									'show' => true,
								],
							],
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
				],
			],
			[
				'test1',
				[
					'before' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'inserted_test1' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
						'inserted_test1' => [
							'href' => 'link',
							'show' => true,
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
							'sub_buttons' => [
								'inserted_test1' => [
									'href' => 'link',
									'show' => true,
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
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
					],
					'child_of' => [
						'test' => [
							'href' => 'link',
							'show' => true,
						],
						'test1' => [
							'href' => 'link1',
							'show' => true,
						],
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
		foreach (['before', 'after', 'child_of'] as $where)
		{
			$haystack = [
				'test' => [
					'href' => 'link',
					'show' => true,
				],
				'test1' => [
					'href' => 'link1',
					'show' => true,
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

	public function childButtonProvider(): array
	{
		return [
			[
				'sub',
				[
					'before' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'inserted_sub' => [
									'href' => 'link',
									'show' => true,
								],
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
					],
					'after' => [
						'test' => [
							'href' => 'link',
							'show' => true,
							'sub_buttons' => [
								'sub' => [
									'href' => 'link',
									'show' => true,
								],
								'inserted_sub' => [
									'href' => 'link',
									'show' => true,
								],
								'sub1' => [
									'href' => 'link',
									'show' => true,
								],
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
										'inserted_sub' => [
											'href' => 'link',
											'show' => true,
										],
									],
								],
								'sub1' => [
									'href' => 'link',
									'show' => true,
								],
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
								'sub' => [
									'href' => 'link',
									'show' => true,
								],
								'inserted_sub1' => [
									'href' => 'link',
									'show' => true,
								],
								'sub1' => [
									'href' => 'link',
									'show' => true,
								],
							],
						],
					],
					'after' => [
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
								'inserted_sub1' => [
									'href' => 'link',
									'show' => true,
								],
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
								],
								'sub1' => [
									'href' => 'link',
									'show' => true,
									'sub_buttons' => [
										'inserted_sub1' => [
											'href' => 'link',
											'show' => true,
										],
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
					],
					'after' => [
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
					],
					'child_of' => [
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
	}

	public function testListButtons(): void
	{
		$haystack = (new UltimateMenu)->flatten([
			'test' => [
				'title' => 'link',
				'sub_buttons' => ['sub' => ['title' => 'link1']],
			],
		]);
		$this->assertArrayHasKey('test', $haystack);
		$this->assertArrayHasKey('sub', $haystack);
		$this->assertCount(2, $haystack);
		$this->assertSame(['test' => [0, 'link'], 'sub' => [1, 'link1']], $haystack);
	}
}
