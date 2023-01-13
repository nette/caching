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
 * SQLite based journal.
 */
class SQLiteJournal implements Journal
{
	use Nette\SmartObject;

	/** @string */
	private $path;
	private \PDO $pdo;


	public function __construct(string $path)
	{
		if (!extension_loaded('pdo_sqlite')) {
			throw new Nette\NotSupportedException('SQLiteJournal requires PHP extension pdo_sqlite which is not loaded.');
		}

		$this->path = $path;
	}


	private function open(): void
	{
		if ($this->path !== ':memory:' && !is_file($this->path)) {
			touch($this->path); // ensures ordinary file permissions
		}

		$this->pdo = new \PDO('sqlite:' . $this->path);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->pdo->exec('
			PRAGMA foreign_keys = OFF;
			PRAGMA journal_mode = WAL;
			CREATE TABLE IF NOT EXISTS tags (
				key BLOB NOT NULL,
				tag BLOB NOT NULL
			);
			CREATE TABLE IF NOT EXISTS priorities (
				key BLOB NOT NULL,
				priority INT NOT NULL
			);
			CREATE INDEX IF NOT EXISTS idx_tags_tag ON tags(tag);
			CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_key_tag ON tags(key, tag);
			CREATE UNIQUE INDEX IF NOT EXISTS idx_priorities_key ON priorities(key);
			CREATE INDEX IF NOT EXISTS idx_priorities_priority ON priorities(priority);
			PRAGMA synchronous = NORMAL;
		');
	}


	public function write(string $key, array $dependencies): void
	{
		if (!isset($this->pdo)) {
			$this->open();
		}

		$this->pdo->exec('BEGIN');

		if (!empty($dependencies[Cache::Tags])) {
			$this->pdo->prepare('DELETE FROM tags WHERE key = ?')->execute([$key]);

			foreach ($dependencies[Cache::Tags] as $tag) {
				$arr[] = $key;
				$arr[] = $tag;
			}

			$this->pdo->prepare('INSERT INTO tags (key, tag) SELECT ?, ?' . str_repeat('UNION SELECT ?, ?', count($arr) / 2 - 1))
				->execute($arr);
		}

		if (!empty($dependencies[Cache::Priority])) {
			$this->pdo->prepare('REPLACE INTO priorities (key, priority) VALUES (?, ?)')
				->execute([$key, (int) $dependencies[Cache::Priority]]);
		}

		$this->pdo->exec('COMMIT');
	}


	public function clean(array $conditions): ?array
	{
		if (!isset($this->pdo)) {
			$this->open();
		}

		if (!empty($conditions[Cache::All])) {
			$this->pdo->exec('
				BEGIN;
				DELETE FROM tags;
				DELETE FROM priorities;
				COMMIT;
			');

			return null;
		}

		$unions = $args = [];
		if (!empty($conditions[Cache::Tags])) {
			$tags = (array) $conditions[Cache::Tags];
			$unions[] = 'SELECT DISTINCT key FROM tags WHERE tag IN (?' . str_repeat(', ?', count($tags) - 1) . ')';
			$args = $tags;
		}

		if (!empty($conditions[Cache::Priority])) {
			$unions[] = 'SELECT DISTINCT key FROM priorities WHERE priority <= ?';
			$args[] = (int) $conditions[Cache::Priority];
		}

		if (empty($unions)) {
			return [];
		}

		$unionSql = implode(' UNION ', $unions);

		$this->pdo->exec('BEGIN IMMEDIATE');

		$stmt = $this->pdo->prepare($unionSql);
		$stmt->execute($args);
		$keys = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

		if (empty($keys)) {
			$this->pdo->exec('COMMIT');
			return [];
		}

		$this->pdo->prepare("DELETE FROM tags WHERE key IN ($unionSql)")->execute($args);
		$this->pdo->prepare("DELETE FROM priorities WHERE key IN ($unionSql)")->execute($args);
		$this->pdo->exec('COMMIT');

		return $keys;
	}
}
