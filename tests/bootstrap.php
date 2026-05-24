 <?php

// What are you doing here, SMF?
define('SMF', 1);

global $context, $modSettings, $smcFunc, $settings, $txt, $user_info, $scripturl, $sourcedir, $boarddir;

$sourcePath = is_dir('./src/Sources') ? './src/Sources' : './src';
$langPath = is_dir('./src/languages') ? './src/languages' : './src';

// Set up necessary global variables
$context = [
	'user' => ['can_mod' => true, 'is_guest' => false, 'id' => 1],
	'right_to_left' => false,
	'session_var' => 'var',
	'session_id' => 'id',
	'current_action' => '',
	'forum_name' => '',
	'html_headers' => '',
	'admin_menu_name' => 'Admin Menu',
];
$settings = [
	'theme_dir' => './src/Themes/default',
	'default_theme_dir' => './src/Themes/default',
	'theme_url' => dirname(__DIR__) . '/Themes/default',
	'default_theme_url' => dirname(__DIR__) . '/Themes/default',
	'images_url' => dirname(__DIR__) . '/Themes/default/images',
];

$scripturl = dirname(__DIR__);
$sourcedir = './vendor/simplemachines/smf/Sources';
$boarddir = './vendor/simplemachines/smf';

$txt = [
	'admin_menu_title' => 'Admin Menu Title',
	'admin_menu' => 'Admin Menu',
	'admin_menu_description' => 'Admin Menu Description',
	'admin_manage_menu_description' => 'Manage Menu Description',
	'admin_menu_add_page_description' => 'Add Page Description',
	'parent_guests_only' => 'Guests',
	'parent_members_only' => 'Members',
	'login' => '',
	'logout' => '',
	'signup' => '',
];
$user_info = ['is_admin' => true, 'is_guest' => false, 'language' => '', 'id' => 1, 'name' => 'Test User', 'groups' => [0], 'permissions' => []];
$modSettings = ['lastActive' => 0, 'settings_updated' => 0, 'postmod_active' => false];

class TestObj
{
	public static $last_query;
	public static $last_params;
	public static array $last_insert;
	public static bool $fake_queries = false;
	public static PDO $pdo;
}

TestObj::$pdo = new PDO('sqlite::memory:');
TestObj::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
TestObj::$pdo->exec('CREATE TABLE settings (
	variable TEXT PRIMARY KEY,
	value TEXT NOT NULL DEFAULT \'\'
);

CREATE TABLE membergroups (
	id_group INTEGER PRIMARY KEY AUTOINCREMENT,
	group_name TEXT NOT NULL DEFAULT \'\',
	description TEXT NOT NULL,
	online_color TEXT NOT NULL DEFAULT \'\',
	min_posts INTEGER NOT NULL DEFAULT -1,
	max_messages INTEGER NOT NULL DEFAULT 0,
	icons TEXT NOT NULL DEFAULT \'\',
	group_type INTEGER NOT NULL DEFAULT 0,
	hidden INTEGER NOT NULL DEFAULT 0,
	id_parent INTEGER NOT NULL DEFAULT -2,
	tfa_required INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_min_posts ON membergroups (min_posts);

INSERT INTO membergroups 
	(id_group, group_name, description, online_color, min_posts, icons, group_type) 
VALUES 
	(1, \'Administrator\', \'\', \'#FF0000\', -1, \'5#iconadmin.png\', 1),
	(2, \'Global Moderator\', \'\', \'#0000FF\', -1, \'5#icongmod.png\', 0),
	(3, \'Moderator\', \'\', \'\', -1, \'5#iconmod.png\', 0),
	(4, \'Newbie\', \'\', \'\', 0, \'1#icon.png\', 0),
	(5, \'Jr. Member\', \'\', \'\', 50, \'2#icon.png\', 0),
	(6, \'Full Member\', \'\', \'\', 100, \'3#icon.png\', 0),
	(7, \'Sr. Member\', \'\', \'\', 250, \'4#icon.png\', 0),
	(8, \'Hero Member\', \'\', \'\', 500, \'5#icon.png\', 0);');

$smcFunc['db_query'] = function (string $identifier, string $query, array $params = []) {
	global $modSettings, $smcFunc;

	TestObj::$last_query = preg_replace('/\s+/', ' ', $query);
	TestObj::$last_params = $params;

	if (!TestObj::$fake_queries) {
		return TestObj::$pdo->query(
			preg_replace(
				['/TRUNCATE/', '/NOW\(\)/'],
				['DELETE FROM', 'DATE(\'now\')'],
				$smcFunc['db_quote']($query, $params),
			),
		) ?: null;
	}


};

$smcFunc['db_fetch_assoc'] = fn(?PDOStatement $stmt) => $stmt?->fetch(PDO::FETCH_ASSOC) ?: null;

$smcFunc['db_fetch_row'] = fn(?PDOStatement $stmt) => $stmt?->fetch(PDO::FETCH_NUM) ?: null;

$smcFunc['db_free_result'] = fn(?PDOStatement $stmt) => $stmt?->closeCursor();

$smcFunc['db_quote'] = function (string $db_string, array $db_values, ?object $connection = null): string {
	// Only bother if there's something to replace.
	if (str_contains($db_string, '{')) {
		$conn = $connection ?? TestObj::$pdo;

		$replacement__callback = function (array $matches) use ($db_values, $conn): string {
			if ($matches[1] === 'db_prefix') {
				return '';
			}

			if ($matches[1] === 'empty') {
				return '\'\'';
			}

			if (!isset($matches[2])) {
				throw new \InvalidArgumentException('Invalid value inserted or no type specified.');
			}

			if ($matches[1] === 'literal') {
				return $conn->quote($matches[2]);
			}

			if (!isset($db_values[$matches[2]])) {
				throw new \InvalidArgumentException('The database value you\'re trying to insert does not exist: ' . htmlspecialchars($matches[2]));
			}

			$replacement = $db_values[$matches[2]];

			switch ($matches[1]) {
				case 'int':
					if (!is_numeric($replacement) || (string) $replacement !== (string) (int) $replacement) {
						throw new \InvalidArgumentException('Wrong value type sent to the database. Integer expected. (' . $matches[2] . ')');
					}

					return (string) (int) $replacement;

				case 'string':
				case 'text':
					return $conn->quote((string) $replacement);

				case 'array_int':
					if (is_array($replacement)) {
						if (empty($replacement)) {
							throw new \InvalidArgumentException('Database error, given array of integer values is empty. (' . $matches[2] . ')');
						}

						foreach ($replacement as $key => $value) {
							if (!is_numeric($value) || (string) $value !== (string) (int) $value) {
								throw new \InvalidArgumentException('Wrong value type sent to the database. Array of integers expected. (' . $matches[2] . ')');
							}
							$replacement[$key] = (int) $value;
						}

						return implode(', ', $replacement);
					}

					throw new \InvalidArgumentException('Wrong value type sent to the database. Array of integers expected. (' . $matches[2] . ')');

				case 'array_string':
					if (is_array($replacement)) {
						if (empty($replacement)) {
							throw new \InvalidArgumentException('Database error, given array of string values is empty. (' . $matches[2] . ')');
						}

						foreach ($replacement as $key => $value) {
							$replacement[$key] = $conn->quote((string) $value);
						}

						return implode(', ', $replacement);
					}

					throw new \InvalidArgumentException('Wrong value type sent to the database. Array of strings expected. (' . $matches[2] . ')');

				case 'date':
					if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $replacement)) {
						return $conn->quote($replacement);
					}

					throw new \InvalidArgumentException('Wrong value type sent to the database. Date expected. (' . $matches[2] . ')');

				case 'time':
					if (preg_match('~^\d{2}:\d{2}:\d{2}$~', $replacement)) {
						return $conn->quote($replacement);
					}

					throw new \InvalidArgumentException('Wrong value type sent to the database. Time expected. (' . $matches[2] . ')');

				case 'datetime':
					if (preg_match('~^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$~', $replacement)) {
						return $conn->quote($replacement);
					}

					throw new \InvalidArgumentException('Wrong value type sent to the database. Datetime expected. (' . $matches[2] . ')');

				case 'float':
					if (!is_numeric($replacement)) {
						throw new \InvalidArgumentException('Wrong value type sent to the database. Floating point number expected. (' . $matches[2] . ')');
					}

					return (string) (float) $replacement;

				case 'identifier':
					return '`' . str_replace('`', '``', $replacement) . '`';

				case 'raw':
					return (string) $replacement;

				default:
					throw new \InvalidArgumentException('Undefined type used in the database query. (' . $matches[1] . ':' . $matches[2] . ')');
			}
		};

		// Do the quoting and escaping
		$db_string = preg_replace_callback('~{([a-z_]+)(?::([a-zA-Z0-9_-]+))?}~', $replacement__callback, $db_string);

		unset($db_values, $conn);
	}

	return $db_string;
};

$smcFunc['db_insert'] = function ($method, $table, $columns, $data, $keys) use ($smcFunc) {
	// Create the mold for a single row insert.
	$insertData = '(';

	foreach ($columns as $columnName => $type) {
		// Are we restricting the length?
		if (strpos($type, 'string-') !== false) {
			$insertData .= sprintf('SUBSTRING({string:%1$s}, 1, ' . substr($type, 7) . '), ', $columnName);
		} else {
		$insertData .= sprintf('{%1$s:%2$s}, ', $type, $columnName);
		}
	}
	$insertData = substr($insertData, 0, -2) . ')';

	// Create an array consisting of only the columns.
	$indexed_columns = array_keys($columns);

	// Inserting data as a single row can be done as a single array.
	if (!is_array($data[array_rand($data)])) {
		$data = [$data];
	}

	// Here's where the variables are injected to the query.
	$insertRows = [];

	foreach ($data as $dataRow) {
		$insertRows[] = $smcFunc['db_quote']($insertData, array_combine($indexed_columns, $dataRow));
	}

	// Determine the method of insertion.
	$queryTitle = match ($method) {
		'replace' => 'REPLACE',
		'ignore' => 'INSERT IGNORE',
		default => 'INSERT',
	};

	// Do the insert.
	$smcFunc['db_query'](
		'',
		'
		' . $queryTitle . ' INTO ' . $table . '(`' . implode('`, `', $indexed_columns) . '`)
		VALUES
			' . implode(',
			', $insertRows),
		[
			'security_override' => true,
		],
	);

	TestObj::$last_insert = [$method, $table, $columns, $data, $keys];
};

$smcFunc['htmltrim'] = fn(string $string): string => trim($string);

$smcFunc['htmlspecialchars'] = fn(string $string): string => htmlspecialchars($string, ENT_QUOTES);

require_once $langPath . '/ManageUltimateMenu.english.php';

require_once './vendor/autoload.php';

require_once './vendor/simplemachines/smf/Sources/Load.php';

require_once './vendor/simplemachines/smf/Sources/Security.php';

require_once './vendor/simplemachines/smf/Sources/Subs.php';

require_once './vendor/simplemachines/smf/Themes/default/languages/index.english.php';
