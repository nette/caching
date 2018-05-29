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
 * SQLite storage.
 */
class SQLiteStorage implements Nette\Caching\IStorage, Nette\Caching\IBulkReader
{
	use Nette\SmartObject;

	/** @var \PDO */
	private $pdo;


	public function __construct(string $path)
	{
		if ($path !== ':memory:' && !is_file($path)) {
			touch($path); // ensures ordinary file permissions
		}

		$this->pdo = new \PDO('sqlite:' . $path);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->pdo->exec('
			PRAGMA foreign_keys = ON;
			PRAGMA case_sensitive_like = ON;

			CREATE TABLE IF NOT EXISTS cache (
				key BLOB NOT NULL PRIMARY KEY,
				data BLOB NOT NULL,
				expire INTEGER,
				slide INTEGER
			);
			CREATE TABLE IF NOT EXISTS tags (
				key BLOB NOT NULL REFERENCES cache ON DELETE CASCADE,
				tag BLOB NOT NULL
			);
			CREATE INDEX IF NOT EXISTS cache_expire ON cache(expire);
			CREATE INDEX IF NOT EXISTS tags_key ON tags(key);
			CREATE INDEX IF NOT EXISTS tags_tag ON tags(tag);
			PRAGMA synchronous = OFF;
		');
	}


	public function read(string $key)
	{
		$key = self::sanitize($key);
		$stmt = $this->pdo->prepare('SELECT data, slide FROM cache WHERE key=? AND (expire IS NULL OR expire >= ?)');
		$stmt->execute([$key, time()]);
		if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			if ($row['slide'] !== null) {
				$this->pdo->prepare('UPDATE cache SET expire = ? + slide WHERE key=?')->execute([time(), $key]);
			}
			return unserialize($row['data']);
		}
	}


	public function bulkRead(array $keys): array
	{
		$keys = array_map([self::class, 'sanitize'], $keys);
		$stmt = $this->pdo->prepare('SELECT key, data, slide FROM cache WHERE key IN (?' . str_repeat(',?', count($keys) - 1) . ') AND (expire IS NULL OR expire >= ?)');
		$stmt->execute(array_merge($keys, [time()]));
		$result = [];
		$updateSlide = [];
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			if ($row['slide'] !== null) {
				$updateSlide[] = $row['key'];
			}
			$result[str_replace("\x01", Cache::NAMESPACE_SEPARATOR, $row['key'])] = unserialize($row['data']);
		}
		if (!empty($updateSlide)) {
			$stmt = $this->pdo->prepare('UPDATE cache SET expire = ? + slide WHERE key IN(?' . str_repeat(',?', count($updateSlide) - 1) . ')');
			$stmt->execute(array_merge([time()], $updateSlide));
		}
		return $result;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, $data, array $dependencies): void
	{
		$key = self::sanitize($key);
		$expire = isset($dependencies[Cache::EXPIRATION]) ? $dependencies[Cache::EXPIRATION] + time() : null;
		$slide = isset($dependencies[Cache::SLIDING]) ? $dependencies[Cache::EXPIRATION] : null;

		$this->pdo->exec('BEGIN TRANSACTION');
		$this->pdo->prepare('REPLACE INTO cache (key, data, expire, slide) VALUES (?, ?, ?, ?)')
			->execute([$key, serialize($data), $expire, $slide]);

		if (!empty($dependencies[Cache::TAGS])) {
			foreach ($dependencies[Cache::TAGS] as $tag) {
				$arr[] = $key;
				$arr[] = $tag;
			}
			$this->pdo->prepare('INSERT INTO tags (key, tag) SELECT ?, ?' . str_repeat('UNION SELECT ?, ?', count($arr) / 2 - 1))
				->execute($arr);
		}
		$this->pdo->exec('COMMIT');
	}


	public function remove(string $key): void
	{
		$this->pdo->prepare('DELETE FROM cache WHERE key=?')
			->execute([self::sanitize($key)]);
	}


	public function clean(array $conditions): void
	{
		if (!empty($conditions[Cache::ALL])) {
			$this->pdo->prepare('DELETE FROM cache')->execute();
			return;
		}

		$sql = 'DELETE FROM cache WHERE expire < ?';
		$args = [time()];

		if (!empty($conditions[Cache::TAGS])) {
			$tags = $conditions[Cache::TAGS];
			$sql .= ' OR key IN (SELECT key FROM tags WHERE tag IN (?' . str_repeat(',?', count($tags) - 1) . '))';
			$args = array_merge($args, $tags);
		}

		if (!empty($conditions[Cache::NAMESPACES])) {
			foreach ($conditions[Cache::NAMESPACES] as $namespace) {
				$sql .= ' OR key LIKE ?';
				$args[] = self::sanitize($namespace . Cache::NAMESPACE_SEPARATOR . '%');
			}
		}

		$this->pdo->prepare($sql)->execute($args);
	}


	private function sanitize($key)
	{
		return str_replace(Cache::NAMESPACE_SEPARATOR, "\x01", $key);
	}
}
