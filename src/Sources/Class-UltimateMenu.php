<?php

declare(strict_types=1);

/**
 * @package   Ultimate Menu mod
 * @version   2.0.4
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
				id_button, name, target, type, position, link, status, permissions, parent, icon, sprite
			FROM {db_prefix}um_menu
			ORDER BY id_button ASC'
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
				id_button, name, target, type, position, link, status, parent, icon, sprite
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
				id_button, name, target, type, position, link, status, permissions, parent, icon, sprite
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
				'sprite' => !empty($row['sprite']),
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
			$smcFunc['db_query']('', '
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
					icon = {string:icon},
					sprite = {int:sprite}
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
					'sprite' => (int) $menu_entry['sprite'] ?: 0,
				]
			);
		} else {
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
					'sprite' => 'int',
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
					(int) $menu_entry['sprite'],
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
				id_button, name, target, type, position, link, status, permissions, parent, icon, sprite
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
			'sprite' => (int) $row['sprite'],
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

		// Shuffle-me-not
		$context['um_replaying_menu'] = true;

		// Load SMF's default menu context.
		setupMenuContext();

		// We are in the endgame now.
		remove_integration_function('integrate_menu_buttons', 'um_replay_menu', false);

		unset($context['um_replaying_menu']);

		return $this->um_flatten($context['replayed_menu_buttons']);
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

		foreach ($icons as $icon) {
			if (basename($icon) == 'blank.png') {
				continue;
			}

			switch ($task) {
				case 'all':
					unlink($icon);
					break;
				case 'unassigned':
					$assignedIndex = array_search(basename($icon), array_column($buttons, 'icon'));
					if (is_bool($assignedIndex)) {
						unlink($icon);
					}
					break;
				default:
					foreach ($files as $file) {
						if (in_array($settings['default_theme_dir'] . '/images/um_icons/' . $file, $icons)) {
							unlink($settings['default_theme_dir'] . '/images/um_icons/' . $file);
						}
					}	
			}
		}

		clearstatcache();
	}

	/**
	 * Lists all or standardized jpg and png files contained in the um_icons path
	 *
	 * @return array
	 */
	public function getIconPathContents($standardized = false): array
	{
		global $settings;

		list($images, $pathName) = [[''], $settings['default_theme_dir'] . '/images/um_icons'];
		clearstatcache();

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(realpath($pathName)),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		$files->setMaxDepth(0);

		foreach ($files as $file) {
			if (!$file->isDir() && in_array($file->getExtension(), ['jpg', 'jpeg', 'png']) && $file->getBasename('.' . $file->getExtension()) !== 'blank') {
				if (!$standardized || preg_match('/^um--(\\d+)/u', basename($file->getRealPath()), $matches)) {
					$images[] = basename($file->getRealPath());
				}
			}
		}

		$pathContents = $this->icon_files_sort($images);
		return !empty($pathContents) && is_array($pathContents) ? array_filter($pathContents) : [];
	}

	/**
	 * Returns the list of icons formatted for the admin section $listOptions
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
				'assigned' => preg_match('/^um--(\d+)_/', $file, $matches) && is_bool($assignedIndex)
								? $txt['um_menu_icon_unassigned']
								: (is_bool($assignedIndex)
								? $txt['um_menu_icon_unstandardized']
								: sprintf($txt['um_menu_icon_assigned_button'], $buttons[$assignedIndex]['name'])),
				'standardized' => boolval(preg_match('/^um--(\\d+)/u', $file, $matches)),
			];
		}

		$list = isset($_REQUEST['desc']) ? array_reverse($filesList) : $filesList;
		return array_slice($list, $start, 20);
	}

	/**
	 * Renames unassigned icons and/or resizes them to the Ultimate Menu standard format
	 *
	 * @return array
	 */
	public function standardizeIconPathContents(): void
	{
		global $settings;

		list($filesList, $files, $buttons, $umIconsPath) = [[], $this->getIconPathContents(), $this->total_getMenu(), $this->unixDirSeparator($settings['default_theme_dir'] . '/images/um_icons')];
		foreach ($files as $file) {
			list($fileType, $pathInfo, $assigned) = [exif_imagetype($umIconsPath . '/' . $file), pathinfo($file), array_search(basename($file), array_column($buttons, 'icon'))];
			$ext = mb_strtolower($pathInfo['extension'], 'UTF-8');
			if (in_array($fileType, [IMAGETYPE_JPEG, IMAGETYPE_PNG]) && in_array($ext, ['jpg', 'jpeg', 'png'])) {
				if (($ext == 'png' && $fileType != IMAGETYPE_PNG) || $ext == 'jpeg') {
					rename($umIconsPath . '/' . $file, $umIconsPath . '/' . $pathInfo['filename'] . '.jpg');
					list($file, $ext) = [$pathInfo['filename'] . '.jpg', 'jpg'];
					clearstatcache();
				}

				if (!preg_match('/^um--(\d+)_/', $file, $matches) && is_bool($assigned)) {
					$newFilename = $this->hexadecimal_string(true) . '.' . $ext;
					$this->imageResize($umIconsPath . '/' . $file, $umIconsPath . '/' . $newFilename, $ext);
				} elseif ($size = getimagesize($umIconsPath . '/' . $file)) {
					if ($size[0] > 16 || $size[1] > 16) {
						$tempFile = 'temp_' . $this->hexadecimal_string(false) . ($fileType == IMAGETYPE_JPEG ? '.jpg' : '.png');
						rename($umIconsPath . '/' . $file, $umIconsPath . '/' . $tempFile);
						clearstatcache();
						$this->imageResize($umIconsPath . '/' . $tempFile, $umIconsPath . '/' . $file, $ext);
					}
				}
			}
		}
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
					$icon->resizeImage($width, $height, Imagick::FILTER_CATROM, 1);
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

		if (empty($imagick) && extension_loaded('gd')) {
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
			} else {
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
					imagejpeg($new, $dst, 99);
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
	 * Returns a random hexadecimal string with an optional incrementing um prefix
	 *
	 * @return string
	 */
	public function hexadecimal_string($prefix = false): string
	{
		global $modSettings;

		$letters = range('a', 'z');
		$random_key = array_rand($letters);
		$bytes = random_bytes(5);
		$codeValue = mb_strtolower(strval(bin2hex($bytes)), 'UTF-8') . $letters[$random_key];
		return !empty($prefix) ? 'um--' . $this->um_file_increment() . '_' . $codeValue : $codeValue;
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
	public function iconFilePath($filename = ''): string
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
	 * Sorts files by numerical prefix
	 *
	 * @return array
	 */
	public function icon_files_sort($array): array
	{
		usort($array, function($a, $b) {
			preg_match('/^um--(\d+)_/', $a, $matchesA);
			preg_match('/^um--(\d+)_/', $b, $matchesB);
			$numA = isset($matchesA[0]) ? (int) preg_replace("/^\D+/", "", $matchesA[0]) : 0;
			$numB = isset($matchesB[0]) ? (int) preg_replace("/^\D+/", "", $matchesB[0]) : 0;
			return $numA <=> $numB;
		});

		return $array;
	}

	/**
	 * Returns boolean dependent on detected button sprite CSS
	 *
	 * @return boolean
	 */
	public function um_detect_sprite_css($button = ''): bool
	{
		global $settings, $modSettings;

		$minified = !empty($modSettings['minimize_files']) ? '.min' : '';
		if (file_exists($settings['default_theme_dir'] . '/images/um_icons/um_sprite/ultimate-menu-buttons.png') && file_exists($settings['default_theme_dir'] . '/css/ultimate-menu-buttons' . $minified . '.css')) {
			$um_buttons_css = preg_replace('/\s+(?!\.\,)/', '', str_replace('/* Ultimate-menu CSS */', '', file_get_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons' . $minified . '.css')));
			return empty($button) && !empty($um_buttons_css) ? true : (!empty($um_buttons_css) ? strpos($um_buttons_css, '.main_icons.' . $button . '::before,.um_icon_pseudo.' . $button . '{') !== false : false);
		}

		return false;
	}

	/**
	 * Generates sprite and CSS files for all buttons
	 *
	 * @return boolean
	 */
	public function um_generate_sprite($changeAll = 0): bool
	{
		global $sourcedir, $settings, $smcFunc;

		list($allButtons, $buttons) = [$this->total_getMenu(), []];
		array_walk($allButtons, function($row) use (&$buttons, $settings) {
			$buttons['um_button_' . $row['id_button']] = (!empty($row['icon']) && file_exists($settings['default_theme_dir'] . '/images/um_icons/' . $row['icon']) ? $row['icon'] : '');
		});

		clearstatcache();
		foreach (['/images/um_icons/um_sprite/ultimate-menu-buttons.png', '/css/ultimate-menu-buttons.css', '/css/ultimate-menu-buttons.min.css'] as $file) {
			if (file_exists($settings['default_theme_dir'] . $file)) {
				unlink($settings['default_theme_dir'] . $file);
				clearstatcache(true, $settings['default_theme_dir'] . $file);
			}
		}

		$coordinates = $this->um_sprite_generation($buttons);
		$this->um_css_generation($coordinates);


		clearstatcache();
		$success = $this->um_detect_sprite_css();

		// only adjust buttons to use the sprite if the file was created
		if ($success && !empty($changeAll)) {
			array_walk($buttons, function($row, $key) use ($smcFunc) {
				$number = sscanf($key, "um_button_%d", $id);
				if (!empty($row) && !empty($number)) {
					$smcFunc['db_query']('', '
						UPDATE {db_prefix}um_menu
						SET	sprite = {int:sprite}
						WHERE id_button = {int:id}',
						[
							'id' => $id,
							'sprite' => 1,
						]
					);
				}
			});

			$this->um_cache_fingerprint('new');
		}

		return $success;
	}

	/**
	 * Checks if sprite related files should be created
	 *
	 * @return boolean
	 */
	public function um_sprite_pending($buttonIconCount = 0, $buttonCssCount = 0):bool
	{
		global $settings;

		$css_files = [$settings['default_theme_dir'] . '/css/ultimate-menu-buttons.css', $settings['default_theme_dir'] . '/css/ultimate-menu-buttons.min.css'];
		if (file_exists($css_files[0]) && file_exists($css_files[1])) {
			list($allButtons, $sprite_css, $sprite_min_css) = [$this->total_getMenu(), $this->um_minify_css(file_get_contents($css_files[0])), file_get_contents($css_files[1])];
			array_walk($allButtons, function($row) use (&$buttonIconCount, &$buttonCssCount, $settings, $sprite_css, $sprite_min_css) {
				if (!empty($row['icon']) && file_exists($settings['default_theme_dir'] . '/images/um_icons/' . $row['icon'])) {
					$buttonIconCount++;
					$find = ".main_icons.um_button_" . (int) $row['id_button'] . "::before, .um_icon_pseudo.um_button_" . (int) $row['id_button'];
					if (strpos($sprite_css, $find) !== false && strpos($sprite_min_css, $find) !== false) {
						$buttonCssCount++;
					}
				}
			});
		}

		if ($buttonIconCount != $buttonCssCount) {
			$this->um_cache_fingerprint('temp');
			return true;
		}

		return false;
	}

	/**
	 * Returns existing or new Ultimate Menu cache buster fingerprint
	 *
	 * @return string
	 */
	public function um_cache_fingerprint($mode = 'get'): string
	{
		global $modSettings;

		switch ($mode) {
			case 'new':
				$vcode = um_cache_busting(true);
				updateSettings(
					['um_fingerprint' => $vcode]
				);
				break;
			case 'temp':
				$vcode = um_cache_busting(true);
				break;
			default:
				$vcode = um_cache_busting(false);
		}

		$modSettings['um_fingerprint'] = $vcode;
		return $vcode;
	}

	/**
	 * Creates Ultimate Menu admin message and/or console.log verbose
	 */
	public function um_alert_verbose($msg = '', $alert = false): void
	{
		global $context;		

		$context['html_headers'] .= '
		<script>
			$(function() {
				console.log("' . addcslashes($msg, '"') . '");
			});
		</script>';

		// All admin messages are one liners with some escaped character conversion
		$context['um_admin_message'] = !empty($alert) ? stripcslashes(str_replace(["\\t", "\\n", "\\r", "\\s", "\\'"], ["&emsp;", "&emsp;", "&nbsp;", "&nbsp;", "&apos;"], addcslashes($msg, '\\'))) : '';
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
			if (preg_match('/^um--(\\d+)_/u', $a, $matches)) {
				$numberA = (float) $matches[1];
			}

			if (preg_match('/^um--(\\d+)_/u', $b, $matches)) {
				$numberB = (float) $matches[1];
			}

			return $numberA <=> $numberB;
		});

		$numbers = array_unique(array_filter(array_map(fn($value): int => intval(preg_match('/^um--(\d+)_/', $value, $matches) ? $matches[1] : 0), $files)));
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
	 * Generates a new Ultimate Menu sprite for any buttons that have a valid icon
	 *
	 * @return array
	 */
	private function um_sprite_generation($buttons = []): array
	{
		global $settings, $smcFunc;

		list($currentY, $currentX, $spriteWidth, $spriteHeight, $coordinates, $dir) = [0, 0, 0, 0, [], $settings['default_theme_dir'] . '/images/um_icons/'];

		foreach ($buttons as $imagePath) {
			$size = getimagesize($dir . $imagePath);
			$spriteWidth += $size[0];
			$spriteHeight = max($spriteHeight, $size[1]);
		}

		$sprite = imagecreatetruecolor($spriteWidth, $spriteHeight);
		imagesavealpha($sprite, true);
		$transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
		imagefill($sprite, 0, 0, $transparent);

		foreach ($buttons as $key => $imagePath) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($dir . $imagePath);
			$img = $mime == 'image/jpeg' ?  imagecreatefromjpeg($dir . $imagePath) : imagecreatefrompng($dir . $imagePath);
			$size = getimagesize($dir . $imagePath);
			imagecopy($sprite, $img, $currentX, 0, 0, 0, $size[0], $size[1]);
			$coordinates[$key] = $currentX;
			$currentX += $size[0];
			$currentY += $size[1];
			imagedestroy($img);
		}

		$um_sprite = imagepng($sprite, $dir . '/um_sprite/ultimate-menu-buttons.png', 0);
		imagedestroy($sprite);
		clearstatcache();

		return !empty($um_sprite) ? $coordinates : [];
	}

	/**
	 * Generates CSS files for the Ultimate Menu sprite
	 */
	private function um_css_generation($coordinates = []): void
	{
		global $settings;

		if (!empty($coordinates)) {
			$css = 'background:url(../images/um_icons/um_sprite/ultimate-menu-buttons.png) %dpx 0px no-repeat;';
			file_put_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons.css', '/* Ultimate-menu CSS */' . PHP_EOL);
			file_put_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons.min.css', '/* Ultimate-menu minified CSS */' . PHP_EOL);

			foreach ($coordinates as $key => $xVal) {
				$content = '.main_icons.' . $key . '::before, .um_icon_pseudo.' . $key . ' {' . PHP_EOL . "\t" . sprintf($css, (!$xVal ? 0 : -$xVal)) . "\n\twidth: 16px;\n\theight: 16px;\n}\n";
				file_put_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons.css', $content, FILE_APPEND | LOCK_EX);
				file_put_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons.min.css', $this->um_minify_css($content), FILE_APPEND | LOCK_EX);
			}

			file_put_contents($settings['default_theme_dir'] . '/css/ultimate-menu-buttons.min.css', PHP_EOL, FILE_APPEND | LOCK_EX);
			clearstatcache();
		}
	}

	/**
	 * Minfies data written to the ultimate-menu-buttons.min.css file
	 *
	 * @return string
	 */
	private function um_minify_css($css = ''): string
	{
		return str_replace("@importurl(", "@import url(", trim(
			preg_replace(
				array('/\s*(\w)\s*{\s*/', '/\s*(\S*:)(\s*)([^;]*)(\s|\n)*;(\n|\s)*/', '/\n/', '/\s*}\s*/'),
				array('$1{ ', '$1$3;', "", '} '),
				$css
			)
		));
	}

	/**
	 * Flattens an array to a single subset of values
	 *
	 * @return array
	 */
	private function um_flatten(array $array, int $i = 0): array
	{
		$result = [];
		foreach ($array as $key => $value) {
			$result[$key] = [$i, $value['title']];
			if (!empty($value['sub_buttons'])) {
				$result += $this->um_flatten($value['sub_buttons'], $i + 1);
			}
		}
		return $result;
	}
}
