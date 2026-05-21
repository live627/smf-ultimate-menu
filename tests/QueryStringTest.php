<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QueryStringTest extends TestCase
{
	protected function setUp(): void
	{
		$_GET = [];
	}

	protected function tearDown(): void
	{
		$_GET = [];
	}

	#[DataProvider('providerUmAdminQueryString')]
	public function testUmAdminQueryString(
		array $parameters,
		array $query,
		bool $expected,
	): void {
		$_GET = $query;

		$this->assertSame(
			$expected,
			um_admin_queryString($parameters),
		);
	}

	public static function providerUmAdminQueryString(): array
	{
		return [
			'empty parameters' => [
				[],
				[],
				true,
			],

			'exact match' => [
				['action' => 'admin'],
				['action' => 'admin'],
				true,
			],

			'starts with match' => [
				['action' => 'admin'],
				['action' => 'admin/settings'],
				true,
			],

			'missing parameter' => [
				['action' => 'admin'],
				[],
				false,
			],

			'non matching prefix' => [
				['action' => 'admin'],
				['action' => 'profile/admin'],
				false,
			],

			'case sensitive mismatch' => [
				['action' => 'Admin'],
				['action' => 'admin/settings'],
				false,
			],

			'multiple matching parameters' => [
				[
					'action' => 'admin',
					'area' => 'settings',
				],
				[
					'action' => 'admin/index',
					'area' => 'settings/general',
				],
				true,
			],

			'one matching and one failing parameter' => [
				[
					'action' => 'admin',
					'area' => 'members',
				],
				[
					'action' => 'admin/index',
					'area' => 'settings/general',
				],
				false,
			],
		];
	}
}
