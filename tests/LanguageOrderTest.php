<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LanguageOrderTest extends TestCase
{
	private const LANGUAGES_PATH = __DIR__ . '/../src/languages';
	private const REFERENCE_FILE = 'ManageUltimateMenu.english.php';

	/**
	 * Get all language file paths except the reference
	 */
	private function getLanguageFiles(): array
	{
		$files = glob(self::LANGUAGES_PATH . '/*.php');

		if ($files === false) {
			return [];
		}

		$files = array_values($files);
		// Filter out the reference file
		$files = array_filter($files, fn($file) => !str_ends_with($file, '.english.php'));

		return array_values($files);
	}

	/**
	 * Get the reference language file path
	 */
	private function getReferenceFile(): string
	{
		return self::LANGUAGES_PATH . '/' . self::REFERENCE_FILE;
	}

	/**
	 * Extract txt entries with line numbers from a language file
	 */
	private function extractTxtEntriesWithLines(string $file_path): array
	{
		$content = file_get_contents($file_path);

		if ($content === false) {
			return [];
		}

		$entries = [];
		$lines = explode("\n", $content);
		$pattern = '/\$txt\[\'([^\']+)\'\]/';

		foreach ($lines as $line_num => $line) {
			if (preg_match($pattern, $line, $matches)) {
				$entries[] = [
					'key' => $matches[1],
					'line' => $line_num + 1,
				];
			}
		}

		return $entries;
	}

	/**
	 * Test that all language files declare txt entries in the same order as English
	 */
	public function testLanguageFilesDeclareTextEntriesInSameOrderAsEnglish(): void
	{
		$reference_file = $this->getReferenceFile();
		$this->assertFileExists($reference_file, 'Reference file not found: ' . self::REFERENCE_FILE);

		$reference_entries = $this->extractTxtEntriesWithLines($reference_file);
		$this->assertGreaterThan(0, count($reference_entries), 'No txt entries found in reference file');

		$language_files = $this->getLanguageFiles();
		$this->assertGreaterThan(0, count($language_files), 'No language files to compare');

		$reference_file = $this->getReferenceFile();
		$reference_entries = $this->extractTxtEntriesWithLines($reference_file);
		$reference_keys = array_column($reference_entries, 'key');

		$language_files = $this->getLanguageFiles();

		foreach ($language_files as $language_file) {
			$file_name = basename($language_file);
			$current_entries = $this->extractTxtEntriesWithLines($language_file);
			$current_keys = array_column($current_entries, 'key');

			$differences = array_values(array_filter(
				$this->diffAlignedArrays($reference_keys, $current_keys),
				static fn(array $result): bool => $result['left'] !== $result['right'],
			));

			if ($differences !== []) {
				$messages = [];

				foreach ($differences as $position => $result) {
					$ref_entry = isset($result['l']) ? $reference_entries[$result['l']] : null;
					$cur_entry = isset($result['r']) ? $current_entries[$result['r']] : null;

					$expected_key = $ref_entry['key'] ?? 'MISSING';
					$expected_line = $ref_entry['line'] ?? '?';

					$actual_key = $cur_entry['key'] ?? 'MISSING';
					$actual_line = $cur_entry['line'] ?? '?';

					$messages[] = sprintf(
						"[%d] Expected '%s' (line %s), got '%s' (line %s)",
						$position,
						$expected_key,
						$expected_line,
						$actual_key,
						$actual_line,
					);
				}

				$this->fail(
					sprintf(
						"%s\nKey order mismatches:\n%s",
						$file_name,
						implode("\n", $messages),
					),
				);
			}
		}
	}

	private function diffAlignedArrays(array $left, array $right): array
	{
		$result = [];

		$leftValues = array_values($left);
		$rightValues = array_values($right);

		$l = 0;
		$r = 0;

		while ($l < count($leftValues) || $r < count($rightValues)) {
			$leftValue = $leftValues[$l] ?? null;
			$rightValue = $rightValues[$r] ?? null;

			if ($leftValue === $rightValue) {
				$result[] = [
					'left' => $leftValue,
					'right' => $rightValue,
					'l' => $l,
					'r' => $r,
					'match' => true,
				];

				$l++;
				$r++;
				continue;
			}

			if ($leftValue !== null && !in_array($leftValue, array_slice($rightValues, $r), true)) {
				$result[] = [
					'left' => $leftValue,
					'right' => null,
					'l' => $l,
					'r' => $r,
					'match' => false,
				];

				$l++;
				continue;
			}

			if ($rightValue !== null) {
				$result[] = [
					'left' => null,
					'right' => $rightValue,
					'l' => $l,
					'r' => $r,
					'match' => false,
				];

				$r++;
			}
		}

		return $result;
	}

	/**
	 * Test that all language files contain the same txt keys as English
	 */
	public function testLanguageFilesContainSameTxtKeysAsEnglish(): void
	{
		$reference_file = $this->getReferenceFile();
		$reference_entries = $this->extractTxtEntriesWithLines($reference_file);
		$reference_keys = array_column($reference_entries, 'key');

		$language_files = $this->getLanguageFiles();

		foreach ($language_files as $language_file) {
			$file_name = basename($language_file);
			$current_entries = $this->extractTxtEntriesWithLines($language_file);
			$current_keys = array_column($current_entries, 'key');

			// Check for missing keys
			$missing_keys = array_diff($reference_keys, $current_keys);

			if (!empty($missing_keys)) {
				$missing_list = implode(', ', $missing_keys);
				$this->fail("{$file_name}: Missing keys: {$missing_list}");
			}

			// Check for extra keys
			$extra_keys = array_diff($current_keys, $reference_keys);

			foreach ($extra_keys as $i => $key) {
				$lines = [];

				foreach ($current_entries as $entry) {
					if ($entry['key'] === $extra_keys[$i]) {
						$lines[] = $entry['line'];
					}
				}

				$extra_keys[$i] = '  L' . implode(', L', $lines) . ': ' . $current_entries[$i]['key'];
			}

			if (!empty($extra_keys)) {
				$extra_list = implode("\n", $extra_keys);
				$this->fail("{$file_name}: Extra keys\n{$extra_list}");
			}
		}
	}

	/**
	 * Test that no duplicate txt keys exist within a single language file
	 */
	public function testNoDuplicateTxtKeysInLanguageFiles(): void
	{
		$reference_file = $this->getReferenceFile();
		$this->testFileDuplicates($reference_file);

		$language_files = $this->getLanguageFiles();

		foreach ($language_files as $language_file) {
			$this->testFileDuplicates($language_file);
		}
	}

	/**
	 * Helper to test a single file for duplicates
	 */
	private function testFileDuplicates(string $file_path): void
	{
		$file_name = basename($file_path);
		$entries = $this->extractTxtEntriesWithLines($file_path);
		$keys = array_column($entries, 'key');
		$unique_keys = array_unique($keys);
		$diff_keys = array_diff_assoc($keys, $unique_keys);

		foreach ($diff_keys as $i => $key) {
			$lines = [];

			foreach ($entries as $entry) {
				if ($entry['key'] === $diff_keys[$i]) {
					$lines[] = $entry['line'];
				}
			}

			$diff_keys[$i] = '  L' . implode(', L', $lines) . ': ' . $entries[$i]['key'];
		}

		$this->assertSameSize(
			$keys,
			$unique_keys,
			"{$file_name}: Contains duplicate txt keys\n" . implode("\n", $diff_keys),
		);
	}
}
