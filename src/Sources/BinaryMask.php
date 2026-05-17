<?php

declare(strict_types=1);

class BinaryMask
{
	protected static int $indexOffset = 0;

	protected static array $binTable = [];

	protected static array $hexTable = [];

	protected static function initTables(): void
	{
		if (self::$binTable) {
			return;
		}

		for ($i = 0; $i < 256; $i++) {
			self::$binTable[$i] = sprintf('%08b', $i);
			self::$hexTable[$i] = sprintf('%02x', $i);
		}
	}

	/**
	 * Convert bit indexes into byte array.
	 *
	 * @param array<int> $bits
	 * @param bool $shift
	 * @param int $shifted
	 * @return array<int>
	 */
	public static function toBytes(
		array $bits,
		bool $shift = false,
		int &$shifted = 0,
	): array {
		if (!$bits) {
			$shifted = 0;

			return [];
		}

		$indexOffset = static::$indexOffset;
		$minBit = min($bits);
		$minBit -= $indexOffset;

		if ($minBit < 0) {
			throw new Exception('Each element in the $bits array should be >=0');
		}

		$maxBit = max($bits);
		$maxBit -= $indexOffset;

		$shifted = $shift ? ($minBit >> 3) : 0;
		$shiftedBits = $shifted << 3;
		$sizeInBytes = (($maxBit + 8) >> 3) - $shifted;

		if ($sizeInBytes <= 0) {
			return [];
		}

		$res = array_fill(0, $sizeInBytes, 0);
		$reverseIndexBase = $sizeInBytes - 1;
		$adjust = $indexOffset + $shiftedBits;

		foreach ($bits as $bit) {
			$bit -= $adjust;

			$res[$reverseIndexBase - ($bit >> 3)] |= 1 << ($bit & 7);
		}

		return $res;
	}

	/**
	 * Convert bit indexes into packed binary string.
	 *
	 * @param array<int> $bits
	 */
	public static function toBinData(
		array $bits,
		bool $shift = false,
		int &$shifted = 0,
	): string {
		$bytes = static::toBytes($bits, $shift, $shifted);

		if (!$bytes) {
			return '';
		}

		return pack('C*', ...$bytes);
	}

	/**
	 * Convert bit indexes into binary string.
	 *
	 * @param array<int> $bits
	 */
	public static function toBinString(
		array $bits,
		bool $split = false,
		bool $shift = false,
		int &$shifted = 0,
	): string {
		self::initTables();

		$bytes = static::toBytes($bits, $shift, $shifted);
		$count = count($bytes);

		if ($count === 0) {
			return '';
		}

		$pos = 0;
		$binTable = self::$binTable;

		foreach ($bytes as $byte) {
			$bytes[$pos++] = $binTable[$byte];
		}

		return implode($split ? ' ' : '', $bytes);
	}

	/**
	 * Convert bit indexes into hexadecimal string.
	 *
	 * @param array<int> $bits
	 */
	public static function toHexString(
		array $bits,
		bool $split = false,
		bool $shift = false,
		int &$shifted = 0,
	): string {
		self::initTables();

		$bytes = static::toBytes($bits, $shift, $shifted);
		$count = count($bytes);

		if ($count === 0) {
			return '';
		}

		$pos = 0;
		$hexTable = self::$hexTable;

		foreach ($bytes as $byte) {
			$bytes[$pos++] = $hexTable[$byte];
		}

		return implode($split ? ' ' : '', $bytes);
	}

	/**
	 * Decode packed binary string into bit indexes.
	 *
	 * Optimized version.
	 *
	 * @return array<int>
	 */
	public static function fromBinData(
		string $data,
		int $shifted = 0,
	): array {
		if ($data === '') {
			return [];
		}

		static $decodeTable = null;

		if ($decodeTable === null) {
			$decodeTable = [];

			for ($byte = 0; $byte < 256; $byte++) {
				$bits = [];

				for ($bit = 0; $bit < 8; $bit++) {
					if ($byte & (1 << $bit)) {
						$bits[] = $bit;
					}
				}

				$decodeTable[$byte] = $bits;
			}
		}

		$res = [];

		$lastByte = strlen($data) - 1;
		$base = ($shifted << 3) + static::$indexOffset;

		for ($i = $lastByte; $i >= 0; $i--) {
			$byte = ord($data[$i]);

			if ($byte === 0) {
				continue;
			}

			$currentBase = (($lastByte - $i) << 3) + $base;

			foreach ($decodeTable[$byte] as $bit) {
				$res[] = $currentBase + $bit;
			}
		}

		return $res;
	}

	/**
	 * Generate SQL condition for testing a bit.
	 */
	public static function getSqlCondition(
		string $column,
		int $bit,
		?string $shiftColumn = null,
	): string {
		$bit -= static::$indexOffset;

		if ($shiftColumn === null) {
			return "ASCII(SUBSTR({$column}, -CEIL(({$bit}) / 8), 1)) & (1 << (({$bit}) % 8))";
		}

		return "ASCII(SUBSTR({$column}, -(CEIL(({$bit}) / 8) - {$shiftColumn}), 1)) & (1 << (({$bit}) % 8))";
	}
}

class BinaryIds extends BinaryMask
{
	protected static int $indexOffset = 1;
}
