<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Caching\Storages;

use Nette;
use Nette\Caching\Cache;


/**
 * Cache file storage.
 */
class FileStorage implements Nette\Caching\Storage
{
	/**
	 * Atomic thread safe logic:
	 *
	 * 1) reading: open(r+b), lock(SH), read
	 *     - delete?: delete*, close
	 * 2) deleting: delete*
	 * 3) writing: open(r+b || wb), lock(EX), truncate*, write data, write meta, close
	 *
	 * delete* = try unlink, if fails (on NTFS) { lock(EX), truncate, close, unlink } else close (on ext3)
	 */

	/** @internal cache file structure: meta-struct size + serialized meta-struct + data */
	private const
		MetaHeaderLen = 6,
	// meta structure: array of
		MetaTime = 'time', // timestamp
		MetaSerialized = 'serialized', // is content serialized?
		MetaExpire = 'expire', // expiration timestamp
		MetaDelta = 'delta', // relative (sliding) expiration
		MetaItems = 'di', // array of dependent items (file => timestamp)
		MetaCallbacks = 'callbacks'; // array of callbacks (function, args)

	/** additional cache structure */
	private const
		File = 'file',
		Handle = 'handle';

	/** probability that the clean() routine is started */
	public static float $gcProbability = 0.001;

	private string $dir;
	private ?Journal $journal;
	private array $locks;


	public function __construct(string $dir, ?Journal $journal = null)
	{
		if (!is_dir($dir) || !Nette\Utils\FileSystem::isAbsolute($dir)) {
			throw new Nette\DirectoryNotFoundException("Directory '$dir' not found or is not absolute.");
		}

		$this->dir = $dir;
		$this->journal = $journal;

		if (mt_rand() / mt_getrandmax() < static::$gcProbability) {
			$this->clean([]);
		}
	}


	public function read(string $key): mixed
	{
		$meta = $this->readMetaAndLock($this->getCacheFile($key), LOCK_SH);
		return $meta && $this->verify($meta)
			? $this->readData($meta) // calls fclose()
			: null;
	}


	/**
	 * Verifies dependencies.
	 */
	private function verify(array $meta): bool
	{
		do {
			if (!empty($meta[self::MetaDelta])) {
				// meta[file] was added by readMetaAndLock()
				if (filemtime($meta[self::File]) + $meta[self::MetaDelta] < time()) {
					break;
				}

				touch($meta[self::File]);

			} elseif (!empty($meta[self::MetaExpire]) && $meta[self::MetaExpire] < time()) {
				break;
			}

			if (!empty($meta[self::MetaCallbacks]) && !Cache::checkCallbacks($meta[self::MetaCallbacks])) {
				break;
			}

			if (!empty($meta[self::MetaItems])) {
				foreach ($meta[self::MetaItems] as $depFile => $time) {
					$m = $this->readMetaAndLock($depFile, LOCK_SH);
					if (($m[self::MetaTime] ?? null) !== $time || ($m && !$this->verify($m))) {
						break 2;
					}
				}
			}

			return true;
		} while (false);

		$this->delete($meta[self::File], $meta[self::Handle]); // meta[handle] & meta[file] was added by readMetaAndLock()
		return false;
	}


	public function lock(string $key): void
	{
		$cacheFile = $this->getCacheFile($key);
		if (!is_dir($dir = dirname($cacheFile))) {
			@mkdir($dir); // @ - directory may already exist
		}

		$handle = fopen($cacheFile, 'c+b');
		if (!$handle) {
			return;
		}

		$this->locks[$key] = $handle;
		flock($handle, LOCK_EX);
	}


	public function write(string $key, $data, array $dp): void
	{
		$meta = [
			self::MetaTime => microtime(),
		];

		if (isset($dp[Cache::Expire])) {
			if (empty($dp[Cache::Sliding])) {
				$meta[self::MetaExpire] = $dp[Cache::Expire] + time(); // absolute time
			} else {
				$meta[self::MetaDelta] = (int) $dp[Cache::Expire]; // sliding time
			}
		}

		if (isset($dp[Cache::Items])) {
			foreach ($dp[Cache::Items] as $item) {
				$depFile = $this->getCacheFile($item);
				$m = $this->readMetaAndLock($depFile, LOCK_SH);
				$meta[self::MetaItems][$depFile] = $m[self::MetaTime] ?? null;
				unset($m);
			}
		}

		if (isset($dp[Cache::Callbacks])) {
			$meta[self::MetaCallbacks] = $dp[Cache::Callbacks];
		}

		if (!isset($this->locks[$key])) {
			$this->lock($key);
			if (!isset($this->locks[$key])) {
				return;
			}
		}

		$handle = $this->locks[$key];
		unset($this->locks[$key]);

		$cacheFile = $this->getCacheFile($key);

		if (isset($dp[Cache::Tags]) || isset($dp[Cache::Priority])) {
			if (!$this->journal) {
				throw new Nette\InvalidStateException('CacheJournal has not been provided.');
			}

			$this->journal->write($cacheFile, $dp);
		}

		ftruncate($handle, 0);

		if (!is_string($data)) {
			$data = serialize($data);
			$meta[self::MetaSerialized] = true;
		}

		$head = serialize($meta);
		$head = str_pad((string) strlen($head), 6, '0', STR_PAD_LEFT) . $head;
		$headLen = strlen($head);

		do {
			if (fwrite($handle, str_repeat("\x00", $headLen)) !== $headLen) {
				break;
			}

			if (fwrite($handle, $data) !== strlen($data)) {
				break;
			}

			fseek($handle, 0);
			if (fwrite($handle, $head) !== $headLen) {
				break;
			}

			flock($handle, LOCK_UN);
			fclose($handle);
			return;
		} while (false);

		$this->delete($cacheFile, $handle);
	}


	public function remove(string $key): void
	{
		unset($this->locks[$key]);
		$this->delete($this->getCacheFile($key));
	}


	public function clean(array $conditions): void
	{
		$all = !empty($conditions[Cache::All]);
		$collector = empty($conditions);
		$namespaces = $conditions[Cache::Namespaces] ?? null;

		// cleaning using file iterator
		if ($all || $collector) {
			$now = time();
			foreach (Nette\Utils\Finder::find('_*')->from($this->dir)->childFirst() as $entry) {
				$path = (string) $entry;
				if ($entry->isDir()) { // collector: remove empty dirs
					@rmdir($path); // @ - removing dirs is not necessary
					continue;
				}

				if ($all) {
					$this->delete($path);

				} else { // collector
					$meta = $this->readMetaAndLock($path, LOCK_SH);
					if (!$meta) {
						continue;
					}

					if ((!empty($meta[self::MetaDelta]) && filemtime($meta[self::File]) + $meta[self::MetaDelta] < $now)
						|| (!empty($meta[self::MetaExpire]) && $meta[self::MetaExpire] < $now)
					) {
						$this->delete($path, $meta[self::Handle]);
						continue;
					}

					flock($meta[self::Handle], LOCK_UN);
					fclose($meta[self::Handle]);
				}
			}

			if ($this->journal) {
				$this->journal->clean($conditions);
			}

			return;

		} elseif ($namespaces) {
			foreach ($namespaces as $namespace) {
				$dir = $this->dir . '/_' . urlencode($namespace);
				if (!is_dir($dir)) {
					continue;
				}

				foreach (Nette\Utils\Finder::findFiles('_*')->in($dir) as $entry) {
					$this->delete((string) $entry);
				}

				@rmdir($dir); // may already contain new files
			}
		}

		// cleaning using journal
		if ($this->journal) {
			foreach ($this->journal->clean($conditions) as $file) {
				$this->delete($file);
			}
		}
	}


	/**
	 * Reads cache data from disk.
	 */
	protected function readMetaAndLock(string $file, int $lock): ?array
	{
		$handle = @fopen($file, 'r+b'); // @ - file may not exist
		if (!$handle) {
			return null;
		}

		flock($handle, $lock);

		$size = (int) stream_get_contents($handle, self::MetaHeaderLen);
		if ($size) {
			$meta = stream_get_contents($handle, $size, self::MetaHeaderLen);
			$meta = unserialize($meta);
			$meta[self::File] = $file;
			$meta[self::Handle] = $handle;
			return $meta;
		}

		flock($handle, LOCK_UN);
		fclose($handle);
		return null;
	}


	/**
	 * Reads cache data from disk and closes cache file handle.
	 */
	protected function readData(array $meta): mixed
	{
		$data = stream_get_contents($meta[self::Handle]);
		flock($meta[self::Handle], LOCK_UN);
		fclose($meta[self::Handle]);

		return empty($meta[self::MetaSerialized]) ? $data : unserialize($data);
	}


	/**
	 * Returns file name.
	 */
	protected function getCacheFile(string $key): string
	{
		$file = urlencode($key);
		if ($a = strrpos($file, '%00')) { // %00 = urlencode(Nette\Caching\Cache::NamespaceSeparator)
			$file = substr_replace($file, '/_', $a, 3);
		}

		return $this->dir . '/_' . $file;
	}


	/**
	 * Deletes and closes file.
	 * @param  resource  $handle
	 */
	private static function delete(string $file, $handle = null): void
	{
		if (@unlink($file)) { // @ - file may not already exist
			if ($handle) {
				flock($handle, LOCK_UN);
				fclose($handle);
			}

			return;
		}

		if (!$handle) {
			$handle = @fopen($file, 'r+'); // @ - file may not exist
		}

		if (!$handle) {
			return;
		}

		flock($handle, LOCK_EX);
		ftruncate($handle, 0);
		flock($handle, LOCK_UN);
		fclose($handle);
		@unlink($file); // @ - file may not already exist
	}
}
