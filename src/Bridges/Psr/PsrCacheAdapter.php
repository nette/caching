<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
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


	public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
	{
		$dependencies = [];
		if ($ttl !== null) {
			$dependencies[Nette\Caching\Cache::Expire] = self::ttlToSeconds($ttl);
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
	 * @return \Generator<string, mixed>
	 */
	public function getMultiple(iterable $keys, mixed $default = null): \Generator
	{
		foreach ($keys as $name) {
			yield $name => $this->get($name, $default);
		}
	}


	/**
	 * @param iterable<mixed, mixed>  $values
	 */
	public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
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


	private static function ttlToSeconds(null|int|DateInterval $ttl = null): ?int
	{
		if ($ttl instanceof DateInterval) {
			$now = new \DateTimeImmutable('', new \DateTimeZone('UTC'));
			return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
		}

		return $ttl;
	}
}
