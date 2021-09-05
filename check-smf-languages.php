<?php

/**
 * @package    smf-coding-standard
 * @author     Simple Machines https://www.simplemachines.org
 * @copyright  2021 Simple Machines and individual contributors
 * @license    https://www.simplemachines.org/about/smf/license.php BSD
 */

$start_time = microtime(true);
$ignoreFiles = [
	'\./[A-Za-z0-9/]+/index\.php',
];
$errors = ['e' => [], 'f' => []];
$idx = 0;
$files = readFilesystem('../smf2.1/Themes/default/languages/');
$maxIdx = count($files) - 1;
foreach ($files as $currentFile => $fileInfo)
{
	$tokens = array_values(
		array_filter(
			token_get_all(file_get_contents($currentFile)),
			fn($token) => !is_array(
	$token
) || $token[0] != T_COMMENT && $token[0] != T_DOC_COMMENT && $token[0] != T_WHITESPACE
	)
);
$i = 0; $n = count($tokens) - 1;
while ($i++ < $n)
{
	if ($tokens[$i][0] == T_GLOBAL)
	{
		$i++;
		while ($tokens[$i][0] == T_VARIABLE && $tokens[$i + 1] == ',')
			$i += 2;
		if ($tokens[$i][0] == T_VARIABLE && $tokens[$i + 1] == ';')
			$i++;
	}
	// Copyright.
	if (isValidToken($tokens[$i], T_VARIABLE, '/\$forum_copyright/') && $tokens[$i + 2][0] == T_CONSTANT_ENCAPSED_STRING)
		$i += 2;
	// The familiar syntax: $txt['bar']!
	elseif (isValidToken($tokens[$i], T_VARIABLE, '/\$(?:help|tz|editor)?txt/')
		&& $tokens[$i + 2][0] == T_CONSTANT_ENCAPSED_STRING && $tokens[$i + 3] == ']')
	{
		// Characters in keys should be alphanumeric.
		if (preg_match('/[^A-Za-z0-9_\/]/', trim($tokens[$i + 2][1], '\'')))
			$errors['f'][$currentFile][] = $tokens[$i + 2] + [3 => 'key'];

		// Allow   (new in SMF 2.1).
		while (($tokens[$i + 5][0] == T_LNUMBER || $tokens[$i + 5][0] == T_CONSTANT_ENCAPSED_STRING)
			&& $tokens[$i + 4] == '[' && $tokens[$i + 6] == ']' && ($tokens[$i + 7] == '[' || $tokens[$i + 7] == '='))
		{
			if (preg_match('/[^A-Za-z0-9_]/', trim($tokens[$i + 5][1], '\'')))
				$errors['f'][$currentFile][] = $tokens[$i + 5] + [3 => 'key'];
			$i += 3;
		}

		// Characters in the hexadecimal ranges 00–08, 0B–0C, 0E–1F, and 7F–9F cannot be used in an HTML document.
		if (isValidToken($tokens[$i + 5], T_CONSTANT_ENCAPSED_STRING, '/ [\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\x9F]/'))
			$errors['f'][$currentFile][] = $tokens[$i + 2] + [3 => 'chars'];

		if ($tokens[$i + 5] == '[' || ($tokens[$i + 5][0] == T_ARRAY && $tokens[$i + 6] == '('))
		{
			$errors['f'][$currentFile][] = $tokens[$i + 2] + [3 => 'array'];
			$j = $i + 6;
			if ($tokens[$j - 1][0] == T_ARRAY && $tokens[$j] == '(')
				$j++;
			while (
				(($tokens[$j][0] == T_LNUMBER || isValidToken($tokens[$j], T_CONSTANT_ENCAPSED_STRING, '/[a-z_]/'))
					&& $tokens[$j + 1][0] == T_DOUBLE_ARROW && $tokens[$j + 2][0] == T_CONSTANT_ENCAPSED_STRING
				) || ($tokens[$j][0] == T_CONSTANT_ENCAPSED_STRING))
			{
				$j++;
				if ($tokens[$j][0] == T_DOUBLE_ARROW)
					$j += 2;
				if ($tokens[$j] == ',')
					$j++;
			}
			if (($tokens[$j] == ')' || $tokens[$j] == ']') && $tokens[$j + 1] == ';')
				$i = $j + 1;
		}
		elseif ($tokens[$i + 6] == ';')
			$i += 6;
	}
	elseif ($tokens[$i][0] == T_CLOSE_TAG)
		$i++;
	// Unwanted or misplaced tokens.
	elseif (is_array($tokens[$i]))
		$errors['e'][$currentFile][] = $tokens[$i];
}
	outputProgress(
		isset($errors['e'][$currentFile]) ? 'E' : (isset($errors['f'][$currentFile]) ? 'F' : '.'),
		$idx++,
		$maxIdx
	);
}
$end_time = microtime(true);

echo $end_time - $start_time;

outputResults($errors);

function isValidToken($token, int $type, string $str = ''): bool
{
	return is_array($token) && $token[0] == $type && ($str == '' || preg_match($str, $token[1]));
}

function readFilesystem(string $dirname): array
{
	return iterator_to_array(
		new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($dirname, FilesystemIterator::UNIX_PATHS),
				function ($fileInfo, $currentFile)
				{
					global $ignoreFiles;

					foreach ($ignoreFiles as $if)
						if (preg_match('~' . $if . '~i', strtr($currentFile, ['./smf2.1' => ''])))
							return false;

					return $fileInfo->getExtension() == 'php';
				}
			)
		)
	);
}

function outputProgress(string $progress, int $i, int $maxIdx): void
{
	static $column = 0;

	switch ($progress)
	{
		case 'F':
			echo "\033[1;37;41m$progress\033[0m";
			break;
		case 'E':
			echo "\033[1;31m$progress\033[0m";
			break;
		case '.':
			echo "\033[32m$progress\033[0m";
			break;
	}
	$width = strlen((string) $maxIdx);
	$maxColumn = 80 - strlen('  /  (XXX%)') - (2 * $width);
	if (++$column == $maxColumn || $i == $maxIdx)
	{
		if ($i == $maxIdx)
			echo str_repeat(' ', $maxColumn - $column);

		echo sprintf(
			' %' . $width . 'd / %' . $width . 'd (%3s%%)',
			$i,
			$maxIdx,
			floor(($i / $maxIdx) * 100)
		);

		if ($column == $maxColumn)
		{
			$column = 0;
			echo "\n";
		}
	}
}

function outputResults(array $errors)
{
	$t = [
		'array' => 'Array syntax is deprecated',
		'key' => 'Key must be alphanumeric',
	];
	foreach (['e', 'f'] as $type)
		if (!empty($errors[$type]))
		{
			fwrite(STDERR, "\n\n");
			foreach ($errors[$type] as $file => $tokens)
			{
				fwrite(STDERR, "\n\e[4;33m$file\e[24;39m)\n");
				foreach ($tokens as $token)
					fprintf(
						STDERR,
						"\e[31m✖\e[39m %s (\e[90m%s\e[0m) on line \e[1m%d\e[0m\n",
						isset($token[3]) ? $t[$token[3]] : "Unexpected \e[1;35m" . token_name($token[0]) . "\e[21;39m",
						$token[1],
						$token[2]
					);
			}
		}
	if (!empty($errors['e']) && !empty($errors['f']))
		exit(1);
}
