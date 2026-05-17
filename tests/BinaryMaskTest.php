<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BinaryMaskTest extends TestCase
{
	/**
	 * @return array<string, array{0: array<int>}>
	 */
	public static function datasetProvider(): array
	{
		return [
			'single_zero' => [[0]],
			'single_one' => [[1]],
			'single_high' => [[100000]],
			'byte_boundary' => [[7, 8, 15, 16]],
			'dense' => [range(0, 55)],
		];
	}

	#[DataProvider('datasetProvider')]
	public function testBinaryMaskRoundtrip(array $bits): void
	{
		$shifted = 0;

		$bytes = BinaryMask::toBytes($bits, true, $shifted);
		$data = BinaryMask::toBinData($bits, true, $shifted);
		$decoded = BinaryMask::fromBinData($data, $shifted);

		$expectedData = $bytes ? pack('C*', ...$bytes) : '';

		$this->assertSame($expectedData, $data, 'Packed binary mismatch');
		$this->assertSame($bits, $decoded, 'Roundtrip mismatch');
	}

	#[DataProvider('datasetProvider')]
	public function testBinaryMaskHexLength(array $bits): void
	{
		$shifted = 0;

		$bytes = BinaryMask::toBytes($bits, true, $shifted);
		$hex = BinaryMask::toHexString($bits, true, true, $shifted);

		$this->assertSame(count($bytes) * 3, strlen($hex) + 1);
	}

	#[DataProvider('datasetProvider')]
	public function testBinaryMaskBinLength(array $bits): void
	{
		$shifted = 0;

		$bytes = BinaryMask::toBytes($bits, true, $shifted);
		$bin = BinaryMask::toBinString($bits, true, true, $shifted);

		$this->assertSame(count($bytes) * 9, strlen($bin) + 1);
	}

	#[DataProvider('datasetProvider')]
	public function testBinaryIdsRoundtrip(array $bits): void
	{
		$ids = array_map(static fn(int $bit): int => $bit + 1, $bits);

		$shifted = 0;

		$data = BinaryIds::toBinData($ids, true, $shifted);
		$decoded = BinaryIds::fromBinData($data, $shifted);

		$this->assertSame($ids, $decoded);
	}

	public function testRejectsNegativeBit(): void
	{
		$this->expectException(Exception::class);

		$shifted = 0;

		BinaryMask::toBytes([-1], true, $shifted);
	}

	public function testEmptyInput(): void
	{
		$shifted = 999;

		$this->assertSame([], BinaryMask::toBytes([], true, $shifted));
		$this->assertSame(0, $shifted);
		$this->assertSame('', BinaryMask::toBinData([]));
		$this->assertSame([], BinaryMask::fromBinData(''));
	}
}
