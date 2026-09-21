<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\Psr;

use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storages\Journal;
use Psr;
use function is_array;


/**
 * Adapts any PSR-16 SimpleCache implementation to the Nette Storage interface,
 * making all PSR-16 backends usable as Nette cache storage.
 *
 * Keys are hashed to fit the character set that PSR-16 guarantees to support,
 * the optional prefix must therefore contain only characters A-Z a-z 0-9 _ and dot.
 * Note that clean(Cache::All) calls clear() on the underlying PSR-16 cache, so regardless of the prefix
 * it also wipes the data of anyone else sharing that cache.
 */
class Psr16Storage implements Nette\Caching\Storage, Nette\Caching\BulkReader, Nette\Caching\BulkWriter
{
	/** @internal cache structure */
	private const
		MetaData = 'data',
		MetaCallbacks = 'callbacks'; // array of callbacks (function, args)


	public function __construct(
		private readonly Psr\SimpleCache\CacheInterface $cache,
		private readonly string $prefix = '',
		private readonly ?Journal $journal = null,
	) {
	}


	public function read(string $key): mixed
	{
		$key = $this->formatKey($key);
		$meta = $this->cache->get($key);
		if (!is_array($meta)) {
			return null;
		}

		// verify dependencies
		if (!empty($meta[self::MetaCallbacks]) && !Cache::checkCallbacks($meta[self::MetaCallbacks])) {
			$this->cache->delete($key);
			return null;
		}

		return $meta[self::MetaData];
	}


	public function bulkRead(array $keys): array
	{
		$formattedKeys = [];
		foreach ($keys as $key) {
			$formattedKeys[$this->formatKey($key)] = $key;
		}

		$result = [];
		foreach ($this->cache->getMultiple(array_keys($formattedKeys)) as $formattedKey => $meta) {
			if (!is_array($meta)) {
				continue;

			} elseif (!empty($meta[self::MetaCallbacks]) && !Cache::checkCallbacks($meta[self::MetaCallbacks])) {
				$this->cache->delete($formattedKey);
				continue;
			}

			$result[$formattedKeys[$formattedKey]] = $meta[self::MetaData];
		}

		return $result;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, mixed $data, array $dependencies): void
	{
		$this->checkDependencies($dependencies);
		$key = $this->formatKey($key);
		[$meta, $ttl] = $this->buildMeta($data, $dependencies);
		if ($ttl !== null && $ttl <= 0) {
			$this->cache->delete($key);
			return;
		}

		if (isset($dependencies[Cache::Tags]) || isset($dependencies[Cache::Priority])) {
			if (!$this->journal) {
				throw new Nette\InvalidStateException('CacheJournal has not been provided.');
			}

			$this->journal->write($key, $dependencies);
		}

		$this->cache->set($key, $meta, $ttl);
	}


	public function bulkWrite(array $items, array $dependencies): void
	{
		$this->checkDependencies($dependencies);
		$ttl = isset($dependencies[Cache::Expire]) ? (int) $dependencies[Cache::Expire] : null;
		if ($ttl !== null && $ttl <= 0) {
			$this->bulkRemove(array_map(strval(...), array_keys($items)));
			return;
		}

		$records = [];
		foreach ($items as $key => $data) {
			$key = $this->formatKey((string) $key);
			[$records[$key]] = $this->buildMeta($data, $dependencies);

			if (isset($dependencies[Cache::Tags]) || isset($dependencies[Cache::Priority])) {
				if (!$this->journal) {
					throw new Nette\InvalidStateException('CacheJournal has not been provided.');
				}

				$this->journal->write($key, $dependencies);
			}
		}

		$this->cache->setMultiple($records, $ttl);
	}


	/**
	 * Rejects the dependencies it cannot honor. Sliding expiration would need to extend the TTL without writing
	 * the item again, which PSR-16 cannot do: a read followed by set() would store back an item removed in between.
	 * @param  array<string, mixed>  $dependencies
	 */
	private function checkDependencies(array $dependencies): void
	{
		if (isset($dependencies[Cache::Items])) {
			throw new Nette\NotSupportedException('Dependency Cache::Items is not supported by ' . static::class . '.');
		} elseif (isset($dependencies[Cache::Expire]) && !empty($dependencies[Cache::Sliding])) {
			throw new Nette\NotSupportedException('Dependency Cache::Sliding is not supported by ' . static::class . '.');
		}
	}


	/**
	 * @param  array<string, mixed>  $dp
	 * @return array{array<string, mixed>, ?int}  meta structure and TTL in seconds
	 */
	private function buildMeta(mixed $data, array $dp): array
	{
		$meta = [
			self::MetaData => $data,
		];

		$ttl = isset($dp[Cache::Expire]) ? (int) $dp[Cache::Expire] : null;
		if (isset($dp[Cache::Callbacks])) {
			$meta[self::MetaCallbacks] = $dp[Cache::Callbacks];
		}

		return [$meta, $ttl];
	}


	public function remove(string $key): void
	{
		$this->cache->delete($this->formatKey($key));
	}


	public function bulkRemove(array $keys): void
	{
		$formattedKeys = [];
		foreach ($keys as $key) {
			$formattedKeys[] = $this->formatKey($key);
		}

		$this->cache->deleteMultiple($formattedKeys);
	}


	public function clean(array $conditions): void
	{
		if (isset($conditions[Cache::Namespaces])) {
			throw new Nette\NotSupportedException('Cleaning by Cache::Namespaces is not supported by ' . static::class . '.');
		}

		if (!empty($conditions[Cache::All])) {
			$this->cache->clear();
			$this->journal?->clean($conditions);

		} elseif ($this->journal) {
			$keys = $this->journal->clean($conditions) ?? [];
			if ($keys) {
				$this->cache->deleteMultiple($keys);
			}
		}
	}


	/**
	 * Converts the key to characters that PSR-16 implementations are guaranteed to support.
	 */
	private function formatKey(string $key): string
	{
		return $this->prefix . hash('xxh128', $key);
	}
}
