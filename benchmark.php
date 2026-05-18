<?php

declare(strict_types=1);

/**
 * Optimized Ultimate Menu benchmark + correctness validation.
 *
 * Goals:
 *  - Preserve original behavior
 *  - Eliminate recursive scans
 *  - Reduce array copying
 *  - Remove repeated permission checks
 *  - Compare original vs optimized output
 *  - Benchmark both implementations
 */

ini_set('memory_limit', '2048M');

const ITERATIONS = 1000;

/*
|--------------------------------------------------------------------------
| Mock Environment
|--------------------------------------------------------------------------
*/

$scripturl = 'https://example.com/index.php';

$user_info = [
	'groups' => [1, 2, 3],
];

$modSettings = [];
$context = [];
$smcFunc = [];

function allowedTo(string $permission): bool
{
	return false;
}

/*
|--------------------------------------------------------------------------
| ORIGINAL IMPLEMENTATION
|--------------------------------------------------------------------------
*/

function um_load_menu_original(array &$menu_buttons): void
{
	global $context, $modSettings, $smcFunc, $user_info, $scripturl;

	for ($i = 1; $i <= ($modSettings['um_count'] ?? 0); $i++) {
		$key = 'um_button_' . $i;

		if (!isset($modSettings[$key])) {
			continue;
		}

		$row = json_decode($modSettings[$key], true);

		$temp_menu = [
			'title' => $row['name'],
			'href' => ($row['type'] == 'forum' ? $scripturl . '?' : '') . $row['link'],
			'target' => $row['target'],
			'show' => (
				allowedTo('admin_forum')
				|| array_intersect($user_info['groups'], $row['groups']) != []
			) && $row['active'],
		];

		recursive_button_original(
			$temp_menu,
			$menu_buttons,
			$row['parent'],
			$row['position'],
			$key,
		);
	}
}

function recursive_button_original(
	array $needle,
	array &$haystack,
	$insertion_point,
	$where,
	$key,
): void {
	foreach ($haystack as $area => &$info) {
		if ($area == $insertion_point) {
			switch ($where) {
				case 'before':
				case 'after':
					insert_button_original(
						[$key => $needle],
						$haystack,
						$insertion_point,
						$where,
					);
					break 2;

				case 'child_of':
					$info['sub_buttons'][$key] = $needle;
					break 2;
			}
		} elseif (!empty($info['sub_buttons'])) {
			recursive_button_original(
				$needle,
				$info['sub_buttons'],
				$insertion_point,
				$where,
				$key,
			);
		}
	}
}

function insert_button_original(
	array $needle,
	array &$haystack,
	$insertion_point,
	$where = 'after',
): void {
	$offset = 0;

	foreach ($haystack as $area => $dummy) {
		if (++$offset && $area == $insertion_point) {
			break;
		}
	}

	if ($where == 'before') {
		$offset--;
	}

	$haystack =
		array_slice($haystack, 0, $offset, true)
		+ $needle
		+ array_slice($haystack, $offset, null, true);
}

/*
|--------------------------------------------------------------------------
| OPTIMIZED IMPLEMENTATION
|--------------------------------------------------------------------------
*/

function um_load_menu_optimized(array &$menu_buttons): void
{
	global $modSettings, $user_info, $scripturl;

	if (!isset($modSettings['um_keys'])) {
		return;
	}

	$is_admin = allowedTo('admin_forum');
	$forum_prefix = $scripturl . '?';
	$group_map = array_flip($user_info['groups']);
	$um_keys = explode(',', $modSettings['um_keys']);

	// Build flat indexes
	$nodes = $menu_buttons;
	$root_order = array_keys($menu_buttons);

	// Build lists of deferred operations
	$before = [];
	$after = [];
	$children = [];

	foreach ($um_keys as $key) {
		if (!isset($modSettings[$key])) {
			continue;
		}

		$row = json_decode($modSettings[$key], true);

		$show = $is_admin;

		if (!$show) {
			foreach ($row['groups'] as $group) {
				if (isset($group_map[$group])) {
					$show = true;
					break;
				}
			}
		}

		if (empty($row['active'])) {
			continue;
		}

		$nodes[$key] = [
			'title' => $row['name'],
			'href' => ($row['type'] === 'forum' ? $forum_prefix : '') . $row['link'],
			'target' => $row['target'],
			'show' => $show,
		];

		switch ($row['position']) {
			case 'before':
				$before[$row['parent']][] = $key;
				break;

			case 'after':
				$after[$row['parent']][] = $key;
				break;

			case 'child_of':
				$children[$row['parent']][] = $key;
				break;
		}
	}

	$menu_buttons = [];

	foreach ($root_order as $key) {
		emit_node(
			$key,
			$nodes,
			$children,
			$before,
			$after,
			$menu_buttons,
		);
	}
}

function emit_node(
	string $key,
	array $nodes,
	array $children,
	array $before,
	array $after,
	array &$result,
): void {
	if (isset($before[$key])) {
		foreach ($before[$key] as $before_key) {
			emit_node(
				$before_key,
				$nodes,
				$children,
				$before,
				$after,
				$result,
			);
		}
	}

	$item = $nodes[$key];

	if (isset($children[$key])) {
		$child_result = [];

		foreach ($children[$key] as $child_key) {
			emit_node(
				$child_key,
				$nodes,
				$children,
				$before,
				$after,
				$child_result,
			);
		}

		$item['sub_buttons'] = $child_result;
	}

	$result[$key] = $item;

	if (isset($after[$key])) {
		for ($i = count($after[$key]) - 1; $i >= 0; $i--) {
			emit_node($after[$key][$i], $nodes, $children, $before, $after, $result);
		}
	}
}

/*
|--------------------------------------------------------------------------
| DATA GENERATION
|--------------------------------------------------------------------------
*/

function generate_base_menu(int $count): array
{
	$menu = [];

	for ($i = 1; $i <= $count; $i++) {
		$menu['button_' . $i] = [
			'title' => 'Button ' . $i,
			'href' => '#',
			'sub_buttons' => [],
		];
	}

	return $menu;
}

function generate_mod_settings(int $count, bool $deep = false): array
{
	$settings = [
		'um_count' => $count,
	];
	$menu = [];

	$before = [];
	$after = [];
	$children = [];

	for ($i = 1; $i <= $count; $i++) {
		$key = 'um_button_' . $i;
		$menu[] = $key;

		if ($deep) {
			$parent = $i === 1
				? 'button_1'
				: 'um_button_' . ($i - 1);

			$position = 'child_of';
		} else {
			$parent = 'button_' . (($i % 10) + 1);

			$positions = ['before', 'after', 'child_of'];

			$position = $positions[$i % 3];
		}

		$settings[$key] = json_encode([
			'name' => 'Ultimate Menu ' . $i,
			'type' => 'forum',
			'link' => 'action=test' . $i,
			'target' => '',
			'groups' => [1, 2],
			'active' => true,
			'parent' => $parent,
			'position' => $position,
		]);

		switch ($position) {
			case 'before':
				$before[$parent][] = $key;
				break;

			case 'after':
				$after[$parent][] = $key;
				break;

			case 'child_of':
				$children[$parent][] = $key;
				break;
		}
	}

	$settings['um_cache'] = json_encode([
		'keys' => $menu,
		'before' => $before,
		'after' => $after,
		'children' => $children,
	]);

	return $settings + ['um_keys' => implode(',', $menu)];
}

/*
|--------------------------------------------------------------------------
| CORRECTNESS TESTS
|--------------------------------------------------------------------------
*/

function assert_equal(mixed $left, mixed $right): bool
{
	return $left === $right;
}

function run_correctness_tests(array $tests): void
{
	global $modSettings;

	echo PHP_EOL;
	echo str_repeat('=', 100) . PHP_EOL;
	echo 'CORRECTNESS TESTS' . PHP_EOL;
	echo str_repeat('=', 100) . PHP_EOL;

	printf(
		"%-12s %-10s %-10s %-12s %-10s\n",
		'Scenario',
		'Buttons',
		'Menu',
		'Deep',
		'Result',
	);

	echo str_repeat('-', 100) . PHP_EOL;

	foreach ($tests as [$label, $buttonCount, $menuSize, $deep]) {
		$modSettings = generate_mod_settings($buttonCount, $deep);

		$menu_original = generate_base_menu($menuSize);
		$menu_optimized = generate_base_menu($menuSize);

		um_load_menu_original($menu_original);
		um_load_menu_optimized($menu_optimized);

		$pass = assert_equal(
			serialize($menu_original),
			serialize($menu_optimized),
		);

		printf(
			"%-12s %-10d %-10d %-12s %-10s\n",
			$label,
			$buttonCount,
			$menuSize,
			$deep ? 'Yes' : 'No',
			$pass ? 'PASS' : 'FAIL',
		);

		if (!$pass) {
			echo PHP_EOL;
			echo 'Mismatch detected in: ' . $label . PHP_EOL;

			$diff = ArrayDiff::diff($menu_original, $menu_optimized);
			ArrayDiff::render($diff);

			exit(1);
		}
	}

	echo str_repeat('-', 100) . PHP_EOL;
	echo 'All correctness tests passed.' . PHP_EOL . PHP_EOL;
}

/**
 * Visual recursive array diffing utility.
 *
 * Highlights:
 * - Added keys
 * - Removed keys
 * - Changed values
 * - Type changes
 * - Nested arrays
 *
 * Designed for debugging large SMF menu structures.
 */
final class ArrayDiff
{
	public const SAME = 'same';

	public const ADDED = 'added';

	public const REMOVED = 'removed';

	public const CHANGED = 'changed';

	/**
	 * Generate recursive diff tree.
	 */
	public static function diff(array $left, array $right): array
	{
		$result = [];

		$keys = array_unique(array_merge(
			array_keys($left),
			array_keys($right),
		));

		foreach ($keys as $key) {
			$left_exists = array_key_exists($key, $left);
			$right_exists = array_key_exists($key, $right);

			if (!$left_exists) {
				$result[$key] = [
					'type' => self::ADDED,
					'left' => null,
					'right' => $right[$key],
				];

				continue;
			}

			if (!$right_exists) {
				$result[$key] = [
					'type' => self::REMOVED,
					'left' => $left[$key],
					'right' => null,
				];

				continue;
			}

			$left_value = $left[$key];
			$right_value = $right[$key];

			/*
			|--------------------------------------------------------------------------
			| Recursive arrays
			|--------------------------------------------------------------------------
			*/

			if (is_array($left_value) && is_array($right_value)) {
				$children = self::diff($left_value, $right_value);

				$type = self::SAME;

				foreach ($children as $child) {
					if ($child['type'] !== self::SAME) {
						$type = self::CHANGED;
						break;
					}
				}

				$result[$key] = [
					'type' => $type,
					'children' => $children,
				];

				continue;
			}

			/*
			|--------------------------------------------------------------------------
			| Scalar compare
			|--------------------------------------------------------------------------
			*/

			if ($left_value !== $right_value) {
				$result[$key] = [
					'type' => self::CHANGED,
					'left' => $left_value,
					'right' => $right_value,
				];

				continue;
			}

			$result[$key] = [
				'type' => self::SAME,
				'value' => $left_value,
			];
		}

		return $result;
	}

	/**
	 * Render diff tree visually.
	 */
	public static function render(array $diff, int $depth = 0): void
	{
		$indent = str_repeat('    ', $depth);

		foreach ($diff as $key => $node) {
			switch ($node['type']) {
				case self::ADDED:
					echo $indent . "+ {$key}\n";
					echo $indent . '  RIGHT: ';
					var_export($node['right']);
					echo "\n";
					break;

				case self::REMOVED:
					echo $indent . "- {$key}\n";
					echo $indent . '  LEFT: ';
					var_export($node['left']);
					echo "\n";
					break;

				case self::CHANGED:
					echo $indent . "~ {$key}\n";

					if (isset($node['children'])) {
						self::render($node['children'], $depth + 1);
					} else {
						echo $indent . '  LEFT : ';
						var_export($node['left']);
						echo "\n";

						echo $indent . '  RIGHT: ';
						var_export($node['right']);
						echo "\n";
					}

					break;

				case self::SAME:
					echo $indent . "= {$key}\n";
					break;
			}
		}
	}
}


class TablePrinter
{
	protected array $headers = [];

	protected array $rows = [];

	protected array $widths = [];

	public function __construct(array $headers)
	{
		$this->headers = $headers;

		foreach ($headers as $header) {
			$this->widths[] = strlen((string) $header);
		}
	}

	public function addRow(array $row): void
	{
		$this->rows[] = $row;

		foreach ($row as $index => $value) {
			$length = strlen((string) $value);

			if ($length > $this->widths[$index]) {
				$this->widths[$index] = $length;
			}
		}
	}

	public function printHeader(): void
	{
		$this->printDivider();
		$this->printRow($this->headers);
		$this->printDivider();
	}

	public function printRows(): void
	{
		foreach ($this->rows as $row) {
			$this->printRow($row);
		}

		$this->printDivider();
	}

	public function printRow(array $row): void
	{
		$output = '';

		foreach ($row as $index => $value) {
			$output .= str_pad(
				(string) $value,
				$this->widths[$index] + 2,
			);
		}

		echo rtrim($output) . PHP_EOL;
	}

	protected function printDivider(): void
	{
		$total = array_sum($this->widths)
			+ (count($this->widths) * 2);

		echo str_repeat('-', $total) . PHP_EOL;
	}
}

/*
|--------------------------------------------------------------------------
| BENCHMARKS
|--------------------------------------------------------------------------
*/

function formatBytes(int $bytes): string
{
	$units = ['B', 'KB', 'MB', 'GB'];
	$i = 0;

	while ($bytes >= 1024 && $i < count($units) - 1) {
		$bytes /= 1024;
		$i++;
	}

	return sprintf('%.2f %s', $bytes, $units[$i]);
}

function benchmark(
	callable $fn,
	int $menuSize,
	int $buttonCount,
	bool $deep = false,
): array {
	global $modSettings;

	$modSettings = generate_mod_settings($buttonCount, $deep);

	$times = [];
	$memory = [];

	gc_collect_cycles();

	for ($i = 0; $i < ITERATIONS; $i++) {
		$menu_buttons = generate_base_menu($menuSize);

		memory_reset_peak_usage();

		$memStart = memory_get_usage();

		$start = hrtime(true);

		$fn($menu_buttons);

		$times[] = hrtime(true) - $start;

		$memory[] = (
			memory_get_peak_usage() - $memStart
		);
	}

	sort($times);
	sort($memory);

	$mid = intdiv(ITERATIONS, 2);
	$medianTime = (
		ITERATIONS % 2
			? $times[$mid]
			: ($times[$mid - 1] + $times[$mid]) / 2
	);
	$medianMem = (
		ITERATIONS % 2
			? $memory[$mid]
			: ($memory[$mid - 1] + $memory[$mid]) / 2
	);
	$totalNs = array_sum($times);
	$totalMem = array_sum($memory);

	return [
		'total_ms' => $totalNs / 1_000_000,
		'avg_ms' => (
			($totalNs / 1_000_000)
			/ ITERATIONS
		),
		'median_ms' => (
			$medianTime / 1_000_000
		),
		'min_ms' => min($times) / 1_000_000,
		'max_ms' => max($times) / 1_000_000,
		'avg_mem' => (
			$totalMem / ITERATIONS
		),
		'median_mem' => $medianMem,
		'peak_mem' => max($memory),
		'total_mem' => $totalMem,
	];
}

function run_benchmarks(array $tests): void
{
	echo PHP_EOL;
	echo 'BENCHMARKS' . PHP_EOL;
	echo PHP_EOL;

	$table = new TablePrinter([
		'Scenario',
		'Menu',
		'Buttons',
		'Orig Mem',
		'Opt Mem',
		'Orig Avg(ms)',
		'Opt Avg(ms)',
		'Improvement',
		'Faster',
	]);

	foreach ($tests as [$label, $menuSize, $buttonCount, $deep]) {
		$original = benchmark(
			'um_load_menu_original',
			$menuSize,
			$buttonCount,
			$deep,
		);

		$optimized = benchmark(
			'um_load_menu_optimized',
			$menuSize,
			$buttonCount,
			$deep,
		);

		$improvement = (
			$original['avg_ms'] / $optimized['avg_ms']
		);

		$fasterPercent = (
			1 - ($optimized['avg_ms'] / $original['avg_ms'])
		) * 100;

		$table->addRow([
			$label,
			$menuSize,
			$buttonCount,
			formatBytes($original['median_mem']),
			formatBytes($optimized['median_mem']),
			number_format($original['avg_ms'], 6),
			number_format($optimized['avg_ms'], 6),
			number_format($improvement, 2) . 'x',
			number_format($fasterPercent, 2) . '%',
		]);
	}

	$table->printHeader();
	$table->printRows();

	echo PHP_EOL;
}

$tests = [
	['Small', 10, 25, false],
	['Medium', 20, 50, false],
	['Large', 40, 100, false],
	['Huge', 80, 200, false],
	['Small Deep', 10, 25, true],
	['Medium Deep', 20, 50, true],
	['Large Deep', 40, 100, true],
	['Huge Deep', 80, 200, true],
];

run_correctness_tests($tests);
run_benchmarks($tests);
