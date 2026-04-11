<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.3
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2026, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */
class UltimateMenu
{
	/**
	 * Gets all membergroups and filters them according to the parameters.
	 *
	 * @param int[] $checked    list of all id_groups to be checked (have a mark in the checkbox).
	 *                          Default is an empty array.
	 * @param bool  $inherited  whether or not to filter out the inherited groups. Default is false.
	 *
	 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
	 */
	public function listGroups(array $checked = [], $inherited = false)
	{
		global $modSettings, $smcFunc, $sourcedir, $txt;

		loadLanguage('ManageBoards');
		$groups = [
			-1 => [
				'name' => $txt['parent_guests_only'],
				'checked' => in_array(-1, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			],
			0 => [
				'name' => $txt['parent_members_only'],
				'checked' => in_array(0, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
			],
		];
		$where = ['id_group NOT IN (1, 3)'];

		if (!$inherited) {
			$where[] = 'id_parent = {int:not_inherited}';

			if (empty($modSettings['permission_enable_postgroups'])) {
				$where[] = 'min_posts = {int:min_posts}';
			}
		}
		$request = $smcFunc['db_query']('', '
			SELECT
				id_group, group_name, min_posts
			FROM {db_prefix}membergroups
			WHERE ' . implode("\n\t\t\t\tAND ", $where),
			[
				'not_inherited' => -2,
				'min_posts' => -1,
			]
		);

		while ([$id, $name, $min_posts] = $smcFunc['db_fetch_row']($request)) {
			$groups[$id] = [
				'name' => trim($name),
				'checked' => in_array($id, $checked) || in_array(-3, $checked),
				'is_post_group' => $min_posts != -1,
			];
		}
		$smcFunc['db_free_result']($request);

		return $groups;
	}

	/**
	 * Loads all buttons from the db
	 *
	 * @return string[]
	 */
	public function total_getMenu()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent, icon
			FROM {db_prefix}um_menu'
		);
		$buttons = [];

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$buttons[] = $row;
		}

		return $buttons;
	}

	/**
	 * Createlist callback, used to display um entries
	 *
	 * @param int    $start
	 * @param int    $items_per_page
	 * @param string $sort
	 *
	 * @return string[]
	 */
	public function list_getMenu($start, $items_per_page, $sort)
	{
		global $smcFunc;

		$buttons = [];
		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, parent, icon
			FROM {db_prefix}um_menu
			ORDER BY {raw:sort}
			LIMIT {int:offset}, {int:limit}',
			[
				'sort' => $sort,
				'offset' => $start,
				'limit' => $items_per_page,
			]
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$buttons[] = $row;
		}

		return $buttons;
	}

	/**
	 * Createlist callback to determine the number of buttons
	 *
	 * @return int
	 */
	public function list_getNumButtons()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}um_menu'
		);
		[$numButtons] = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $numButtons;
	}

	/**
	 * File list callback to determine the number of jpg/png files
	 *
	 * @return int
	 */
	public function list_getNumIcons(): int
	{
		global $settings;

		$images = glob($settings['default_theme_dir'] . "/images/um_icons/*.{jpg,jpeg,png}", GLOB_BRACE);
		return count($images);
	}

	/**
	 * Sets the serialized array of buttons into settings
	 *
	 * Called whenever the menu structure is updated in the ACP
	 */
	public function rebuildMenu(): void
	{
		global $smcFunc, $settings;

		$buttons = [];
		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent, icon
			FROM {db_prefix}um_menu'
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$buttons['um_button_' . $row['id_button']] = json_encode([
				'name' => $row['name'],
				'target' => $row['target'],
				'type' => $row['type'],
				'position' => $row['position'],
				'groups' => array_map('intval', explode(',', $row['permissions'])),
				'link' => $row['link'],
				'active' => $row['status'] == 'active',
				'parent' => $row['parent'],
				'icon' => !empty($row['icon']) && file_exists($settings['default_theme_dir'] . '/images/um_icons/' . $row['icon']) ? $row['icon'] : '',
			]);
		}
		$smcFunc['db_free_result']($request);

		$request = $smcFunc['db_query']('', '
			SELECT MAX(id_button)
			FROM {db_prefix}um_menu'
		);
		[$max] = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}settings
			WHERE variable LIKE {string:settings_search}
				AND variable NOT IN ({array_string:settings})',
			[
				'settings_search' => 'um_button%',
				'settings' => array_keys($buttons),
			]
		);
		updateSettings(['um_count' => $max] + $buttons);
	}

	/**
	 * Removes menu item(s) from the um system
	 *
	 * @param int[] $ids
	 */
	public function deleteButton(array $ids): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}um_menu
			WHERE id_button IN ({array_int:button_list})',
			[
				'button_list' => $ids,
			]
		);
	}

	/**
	 * Changes the status of a button from active to inactive
	 *
	 */
	public function updateButton(array $updates): void
	{
		global $smcFunc;

		foreach ($this->total_getMenu() as $item) {
			$status = !empty($updates['status'][$item['id_button']]) ? 'active' : 'inactive';

			if ($status != $item['status']) {
				$smcFunc['db_query'](
					'',
					'
					UPDATE {db_prefix}um_menu
					SET status = {string:status}
					WHERE id_button = {int:item}',
					[
						'status' => $status,
						'item' => $item['id_button'],
					]
				);
			}
		}
	}

	/**
	 * Checks if there is an existing um id with the same name before saving
	 *
	 * @param int    $id
	 * @param string $name
	 *
	 * @return int
	 */
	public function checkButton($id, $name): int
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT id_button
			FROM {db_prefix}um_menu
			WHERE name = {string:name}
				AND id_button != {int:id}',
			[
				'name' => $name,
				'id' => $id ?: 0,
			]
		);
		$check = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $check;
	}

	/**
	 * Saves a new or updates an existing button
	 */
	public function saveButton(array $menu_entry): void
	{
		global $smcFunc;

		if (!empty($menu_entry['in'])) {
			$smcFunc['db_query'](
				'',
				'
				UPDATE {db_prefix}um_menu
				SET
					name = {string:name},
					type = {string:type},
					target = {string:target},
					position = {string:position},
					link = {string:link},
					status = {string:status},
					permissions = {string:permissions},
					parent = {string:parent},
					icon = {string:icon}
				WHERE id_button = {int:id}',
				[
					'id' => $menu_entry['in'],
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'status' => $menu_entry['status'],
					'permissions' => implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					'parent' => $menu_entry['parent'],
					'icon' => $menu_entry['icon'],
				]
			);
		}
		else {
			$smcFunc['db_insert'](
				'insert',
				'{db_prefix}um_menu',
				[
					'name' => 'string',
					'type' => 'string',
					'target' => 'string',
					'position' => 'string',
					'link' => 'string',
					'status' => 'string',
					'permissions' => 'string',
					'parent' => 'string',
					'icon' => 'string',
				],
				[
					$menu_entry['name'],
					$menu_entry['type'],
					$menu_entry['target'],
					$menu_entry['position'],
					$menu_entry['link'],
					$menu_entry['status'],
					implode(',', array_filter($menu_entry['permissions'], 'strlen')),
					$menu_entry['parent'],
					$menu_entry['icon'],
				],
				['id_button']
			);
		}
	}

	/**
	 * Fetch a specific button
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function fetchButton($id): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT
				id_button, name, target, type, position, link, status, permissions, parent, icon
			FROM {db_prefix}um_menu
			WHERE id_button = {int:button}',
			[
				'button' => $id,
			]
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		return [
			'id' => $row['id_button'],
			'name' => $row['name'],
			'target' => $row['target'],
			'type' => $row['type'],
			'position' => $row['position'],
			'permissions' => explode(',', $row['permissions']),
			'link' => $row['link'],
			'status' => $row['status'],
			'parent' => $row['parent'],
			'icon' => $row['icon'],
		];
	}

	/**
	 * Removes all buttons
	 */
	public function deleteallButtons(): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			TRUNCATE {db_prefix}um_menu'
		);
	}

	/**
	 * Fetches the names of all SMF menu buttons.
	 *
	 * @return array
	 */
	public function getButtonNames(): array
	{
		global $context;

		// Start an instant replay.
		add_integration_function('integrate_menu_buttons', 'um_replay_menu', false);

		// It's expected to be present.
		$context['user']['unread_messages'] = 0;

		// Load SMF's default menu context.
		setupMenuContext();

		// We are in the endgame now.
		remove_integration_function('integrate_menu_buttons', 'um_replay_menu');

		return $this->flatten($context['replayed_menu_buttons']);
	}

	/**
	 * Returns list of icons formatted for the admin section $listOptions
	 *
	 * @return array
	 */
	public function listIconPathContents(): array
	{
		global $txt;

		$filesList = [];
		$start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
		$files = $this->getIconPathContents();
		$buttons = $this->total_getMenu();
		foreach ($files as $index => $file) {
			$assignedIndex = array_search($file, array_column($buttons, 'icon'));
			$filesList[] = [
				'id_file' => $index,
				'name' => $file,
				'assigned' => is_bool($assignedIndex) ? $txt['um_menu_icon_unassigned'] : $buttons[$assignedIndex]['name'],
			];
		}

		$list = isset($_REQUEST['desc']) ? array_reverse($filesList) : $filesList;
		return array_slice($list, $start, 20);
	}

	/**
	 * Deletes opted icon files
	 */
	public function deleteIcons($task = 'selected', $files = []): void
	{
		global $settings;

		clearstatcache();
		$icons = glob($settings['default_theme_dir'] . "/images/um_icons/*.{jpg,jpeg,png}", GLOB_BRACE);
		$buttons = $this->total_getMenu();

		switch ($task) {
			case 'all':
				foreach ($icons as $icon) {
					unlink($icon);
				}
				break;
			case 'unassigned':
				foreach ($icons as $icon) {
					$assignedIndex = array_search(basename($icon), array_column($buttons, 'icon'));
					if (is_bool($assignedIndex)) {
						unlink($icon);
					}
				}
				break;
			default:
				foreach ($files as $file) {
					if (in_array($settings['default_theme_dir'] . '/images/um_icons/' . $file, $icons)) {
						unlink($settings['default_theme_dir'] . '/images/um_icons/' . $file);
					}
				}
		}

		clearstatcache();
	}

	/**
	 * Lists all jpg or png files contained in the um_icons path
	 *
	 * @return array
	 */
	public function getIconPathContents(): array
	{
		global $settings;

		list($images, $pathName) = [[''], $settings['default_theme_dir'] . '/images/um_icons'];
		clearstatcache();

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(realpath($pathName)),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $file) {
			if (!$file->isDir() && in_array($file->getExtension(), ['jpg', 'jpeg', 'png'])) {
				$images[] = basename($file->getRealPath());
			}
		}

		$pathContents = $this->icon_files_sort($images);
		return !empty($pathContents) && is_array($pathContents) ? array_filter($pathContents) : [];
	}

	/**
	 * Returns a Unix formatted path
	 *
	 * @return string
	 */
	public function unixDirSeparator($path) : string
	{
		return preg_replace('#/+#u', '/', str_replace('\\', '/', $path));
	}

	/**
	 * Resizes jpg or png images
	 *
	 * @return string
	 */
	public function imageResize($src, $dst, $ext, $width = 16, $height = 16, $crop = false): string
	{
		global $settings, $boarddir, $boardurl;

		ini_set("gd.jpeg_ignore_warning", 1);
		$src = $this->unixDirSeparator($settings['default_theme_dir']) . '/images/um_icons/' . basename($src);
		list($error, $imagick, $baseFile, $imgTypes) = [false, false, basename($src), ['jpg', 'png']];

		if ($ext == 'jpeg') {
			$renamed = rtrim($src, '.jpeg') . '.jpg';
			if (file_exists($renamed)) {
				unlink($renamed);
				clearstatcache();
			}
			rename($src, $renamed);
			clearstatcache();
			if (file_exists($renamed)) {
				list($src, $ext) = [$renamed, 'jpg'];
			}
		}

		if (empty($src) || !file_exists($src)) {
			return $baseFile;
		}

		if (!list($w, $h) = getimagesize($src)) {
			return $baseFile;
		}

		if (!in_array($ext, $imgTypes)) {
			return $baseFile;
		}

		$imgInfo = getimagesize($src);
		$imgMime = $imgInfo['mime'];
		foreach ($imgTypes as $imgType) {
			if (stripos($imgMime, $imgType) !== false) {
				$ext = $imgType;
			}
		}
		$ext = $ext == 'jpeg' ? 'jpg' : $ext;

		if (!in_array($ext, $imgTypes)) {
			return $baseFile;
		}

		clearstatcache();
		if (extension_loaded('imagick')) {
			try {
				$icon = new \Imagick($src);
			} catch (ImagickException $e) {
				$error = true;
			}
			if (empty($error)) {
				$w = $icon->getImageWidth();
				$h = $icon->getImageHeight();
				if ($w < $width && $h < $height) {
					return $baseFile;
				}

				if ($w > $h) {
					$resize_width = $w * $height / $h;
					$resize_height = $height;
				} else {
					$resize_width = $width;
					$resize_height = $h * $width / $w;
				}

				$icon->setCompressionQuality(100);
				if (!$crop) {
					$icon->resizeImage($width, $height, Imagick::FILTER_CATROM, 0);
				} else {
					$icon->resizeImage($resize_width, $resize_height, Imagick::FILTER_LANCZOS, 0.9);
					$icon->cropImage($width, $height, ($resize_width - $width) / 2, ($resize_height - $height) / 2);
				}
				clearstatcache();
				$icon->getImageBlob();
				$icon->writeImage($dst);
				$icon->destroy();
				$imagick = true;

				clearstatcache();
				if (!file_exists($dst)) {
					$imagick = false;
				} else {
					unlink($src);
					$baseFile = basename($dst);
				}
			}
		}

		if (empty($imagick)) {
			switch ($ext) {
				case 'png':
					if (!$img = imagecreatefrompng($src)) {
						$img = imagecreatefromstring(file_get_contents($src));
					}
					break;
				default:
					if (!$img = imagecreatefromjpeg($src)) {
						$img = imagecreatefromstring(file_get_contents($src));
					}
			}

			if (empty($img)) {
				return $baseFile;
			}

			imageinterlace($img, true);
			if ($crop) {
				if ($w < $width || $h < $height) {
					return $baseFile;
				}

				$ratio = max($width / $w, $height / $h);
				$h = $height / $ratio;
				$x = ($w - $width / $ratio) / 2;
				$w = $width / $ratio;
			}
			else {
				if ($w < $width && $h < $height) {
					return $baseFile;
				}

				$ratio = min($width / $w, $height / $h);
				$width = intval($w * $ratio);
				$height = intval($h * $ratio);
				$x = 0;
			}

			$new = imagecreatetruecolor($width, $height);

			imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
			imagealphablending($new, false);
			imagesavealpha($new, true);
			imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

			switch ($ext) {
				case 'png':
					imagepng($new, $dst, 0);
					break;
				default:
					imagejpeg($new, $dst, 0);
			}
			clearstatcache();
			if (file_exists($dst)) {
				$baseFile = basename($dst);
				if (file_exists($src)) {
					unlink($src);
					clearstatcache();
				}
			} else {
				$baseFile = basename($src);
			}
		}

		return $baseFile;
	}

	/**
	 * Returns a random hexadecimal representation of a filename with an incrementing um prefix
	 *
	 * @return string
	 */
	public function hexadecimal_filename($filename): string
	{
		global $modSettings;

		$letters = range('a', 'z');
		$random_key = array_rand($letters);
		$bytes = random_bytes(5);
		$codeValue = mb_strtolower(strval(bin2hex($bytes)), 'UTF-8') . $letters[$random_key];
		return 'um--' . $this->um_file_increment() . '_' . $codeValue;
	}

	/**
	 * Returns a strict ASCII compatible filename
	 *
	 * @return string
	 */
	public function sanitizeFilename($filename = ''): string
	{
		if (extension_loaded('intl')) {
			$transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
			$filename = $transliterator->transliterate($filename);
		} else {
			$filename = str_replace('?', '_', iconv("UTF-8", "ASCII//TRANSLIT", $filename));
		}

		$filename = preg_replace('/--+/u', '--', preg_replace('/[^a-zA-Z0-9\-\._]/u', '-', basename($filename)));
		return trim(trim(mb_strtolower($filename, 'UTF-8'), '.-'));
	}

	/**
	 * Returns an existing jpg/png image file path else a 1x1 pixel transparent GIF
	 *
	 * @return string
	 */
	public function iconFilePath($filename): string
	{
		global $settings;

		$filename = !empty($filename) ? $this->sanitizeFilename(basename($filename)) : '';
		return !empty($filename)
			&& in_array(pathinfo($filename, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])
			&& file_exists($settings['default_theme_dir'] . '/images/um_icons/' . $filename)
			? $settings['default_theme_url'] . '/images/um_icons/' . $filename
			: 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
	}

	/**
	 * Sorts files alphabetically
	 *
	 * @return array
	 */
	public function icon_files_sort($array): array
	{
		usort($array, function($a, $b) {
			return $a <=> $b;
		});

		return $array;
	}

	/**
	 * Returns the next Ultimate Menu icon increment
	 *
	 * @return int
	 */
	private function um_file_increment($number = 1, $numbers = []): int
	{
		global $settings;

		$files = $this->getIconPathContents();
		usort($files, function($a, $b) {
			list($numberA, $numberB, $matches) = [0, 0, []];
			if (preg_match('/^um--(\\d+)/u', $a, $matches)) {
				$numberA = (float) $matches[1];
			}

			if (preg_match('/^um--(\\d+)/u', $b, $matches)) {
				$numberB = (float) $matches[1];
			}

			return $numberA <=> $numberB;
		});

		$numbers = array_unique(array_filter(array_map(fn($value): int => (int) preg_replace('/\D/u', '', strstr((string) $value, '_', true)), $files)));
		sort($numbers);
		foreach ($numbers as $value) {
			if (!in_array($number, $numbers)) {
				break;
			}

			$number++;
		}

		return $number;
	}

	/**
	 * Flattens an array to a single subset of values
	 *
	 * @return array
	 */
	private function flatten(array $array, int $i = 0): array
	{
		$result = [];
		foreach ($array as $key => $value) {
			$result[$key] = [$i, $value['title']];
			if (!empty($value['sub_buttons'])) {
				$result += $this->flatten($value['sub_buttons'], $i + 1);
			}
		}
		return $result;
	}
}
