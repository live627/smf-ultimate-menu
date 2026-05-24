<?php

use PHPUnit\Framework\TestCase;
use UltimateMenu\DatabaseHelper;

class DatabaseHelperTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		TestObj::$fake_queries = true;
	}

	public static function tearDownAfterClass(): void
	{
		TestObj::$fake_queries = false;
	}

	public function testFetchBy(): void
	{
		DatabaseHelper::fetchBy(['id', 'name'], 'users', ['age' => 21], [], ['age > {int:age}'], ['name'], [], 10);

		$this->assertStringContainsString('SELECT id, name FROM users WHERE (age > {int:age}) ORDER BY name LIMIT 10', TestObj::$last_query);
		$this->assertArrayHasKey('age', TestObj::$last_params);
	}

	public function testInsert(): void
	{
		DatabaseHelper::insert('users', ['name' => ['string', 'John'], 'age' => ['int', 30]]);

		$this->assertEquals(['insert', 'users', ['name' => 'string', 'age' => 'int'], [['John', 30]], []], TestObj::$last_insert);
	}

	public function testUpdate(): void
	{
		DatabaseHelper::update('users', ['name' => ['string', 'John Doe']], 'id', 5);

		$this->assertStringContainsString('UPDATE users SET name = {string:name} WHERE {identifier:col} = {int:id}', TestObj::$last_query);
		$this->assertArrayHasKey('name', TestObj::$last_params);
	}

	public function testDelete(): void
	{
		DatabaseHelper::delete('users', 'id', 5);

		$this->assertStringContainsString('DELETE FROM users WHERE {identifier:col} = {int:id}', TestObj::$last_query);
		$this->assertArrayHasKey('id', TestObj::$last_params);
	}
}
