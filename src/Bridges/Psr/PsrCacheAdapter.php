<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\Psr;

use DateInterval;
use Nette;
use Psr;


/**
 * Adapts Nette Storage to the PSR-16 SimpleCache interface.
 */
class PsrCacheAdapter implements Psr\SimpleCache\CacheInterface
{
	public function __construct(
		private readonly Nette\Caching\Storage $storage,
	) {
	}


	public function get(string $key, mixed $default = null): mixed
	{
		return $this->storage->read($key) ?? $default;
	}


	public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
	{
		$ttl = self::ttlToSeconds($ttl);
		if ($ttl !== null && $ttl <= 0) { // according to PSR-16, zero or negative TTL means immediate deletion
			$this->storage->remove($key);
			return true;
		}

		$dependencies = [];
		if ($ttl !== null) {
			$dependencies[Nette\Caching\Cache::Expire] = $ttl;
		}

		$this->storage->write($key, $value, $dependencies);

		return true;
	}


	public function delete(string $key): bool
	{
		$this->storage->remove($key);
		return true;
	}


	public function clear(): bool
	{
		$this->storage->clean([Nette\Caching\Cache::All => true]);
		return true;
	}


	/**
	 * @param  iterable<string>  $keys
	 * @return \Generator<string, mixed>
	 */
	public function getMultiple(iterable $keys, mixed $default = null): \Generator
	{
		$keys = $keys instanceof \Traversable
			? iterator_to_array($keys, preserve_keys: false)
			: array_values($keys);

		$values = $this->storage instanceof Nette\Caching\BulkReader && $keys
			? $this->storage->bulkRead(array_map(strval(...), $keys))
			: null;

		return (function () use ($keys, $values, $default): \Generator {
			foreach ($keys as $key) {
				yield $key => ($values === null
					? $this->storage->read((string) $key)
					: $values[$key] ?? null
				) ?? $default;
			}
		})();
	}


	/**
	 * @param iterable<mixed, mixed>  $values
	 */
	public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
	{
		$ttl = self::ttlToSeconds($ttl);

		foreach ($values as $key => $value) {
			$this->set((string) $key, $value, $ttl);
		}

		return true;
	}


	/** @param  iterable<string>  $keys */
	public function deleteMultiple(iterable $keys): bool
	{
		foreach ($keys as $value) {
			$this->delete($value);
		}

		return true;
	}


	public function has(string $key): bool
	{
		return $this->storage->read($key) !== null;
	}


	private static function ttlToSeconds(int|DateInterval|null $ttl = null): ?int
	{
		if ($ttl instanceof DateInterval) {
			$now = new \DateTimeImmutable('', new \DateTimeZone('UTC'));
			return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
		}

		return $ttl;
	}
}
