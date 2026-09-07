<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette;
use Nette\Caching\Cache;
use function count, extension_loaded, is_array, is_string, strlen;


/**
 * Redis storage using the redis extension, with native support for tags and priorities.
 */
class RedisStorage implements Nette\Caching\Storage, Nette\Caching\BulkReader, Nette\Caching\BulkWriter
{
	/** @internal cache structure */
	private const
		MetaData = 'data',
		MetaDelta = 'delta', // relative (sliding) expiration
		MetaCallbacks = 'callbacks'; // array of callbacks (function, args)

	/** @internal auxiliary structures, the \x01 byte prevents collision with cache keys */
	private const
		TagIndexPrefix = "\x01t:", // set of keys tagged by given tag
		KeyTagsPrefix = "\x01k:", // set of tags of given key
		PriorityKey = "\x01priority"; // sorted set of keys scored by priority

	/** length of the random version that precedes the serialized meta structure of every item */
	private const VersionLength = 8;

	/** extends the expiration of the items KEYS[i] to ARGV[2i] seconds, if they still have the version ARGV[2i-1] */
	private const SlideScript = <<<'LUA'
		for i = 1, #KEYS do
			if redis.call('GETRANGE', KEYS[i], 0, 7) == ARGV[i * 2 - 1] then
				redis.call('EXPIRE', KEYS[i], ARGV[i * 2])
			end
		end
		LUA;

	/** removes from the sorted set KEYS[1] the members ARGV[2..] whose items with prefix ARGV[1] do not exist */
	private const RemoveMissingScript = <<<'LUA'
		for i = 2, #ARGV do
			if redis.call('EXISTS', ARGV[1] .. ARGV[i]) == 0 then
				redis.call('ZREM', KEYS[1], ARGV[i])
			end
		end
		LUA;

	/** for the keys ARGV[4..] whose items with prefix ARGV[1] do not exist, removes their set of tags (ARGV[2])
	 * and their members of the tag indexes (ARGV[3]) */
	private const RemoveTagsOfMissingScript = <<<'LUA'
		for i = 4, #ARGV do
			if redis.call('EXISTS', ARGV[1] .. ARGV[i]) == 0 then
				local keyTags = ARGV[1] .. ARGV[2] .. ARGV[i]
				for _, tag in ipairs(redis.call('SMEMBERS', keyTags)) do
					redis.call('SREM', ARGV[1] .. ARGV[3] .. tag, ARGV[i])
				end
				redis.call('DEL', keyTags)
			end
		end
		LUA;

	/** Probability (0-1) that the garbage collector runs on each instantiation. */
	public static float $gcProbability = 0.001;

	private readonly \Redis $redis;


	public static function isAvailable(): bool
	{
		return extension_loaded('redis');
	}


	public function __construct(
		\Redis|string $host = 'localhost',
		int $port = 6379,
		/** prefix of all keys; an empty one means the whole database belongs to the cache */
		private readonly string $prefix = 'nette.cache:',
	) {
		if (!static::isAvailable()) {
			throw new Nette\NotSupportedException("PHP extension 'redis' is not loaded.");
		}

		if ($host instanceof \Redis) {
			if ($host->getOption(\Redis::OPT_SERIALIZER) !== \Redis::SERIALIZER_NONE) {
				throw new Nette\InvalidArgumentException('Redis connection must not use a serializer (Redis::OPT_SERIALIZER).');
			} elseif ($host->getOption(\Redis::OPT_PREFIX)) {
				throw new Nette\InvalidArgumentException('Redis connection must not use a prefix (Redis::OPT_PREFIX), use the parameter $prefix instead.');
			}

			$this->redis = $host;

		} else {
			$this->redis = new \Redis;
			try {
				$connected = $this->redis->connect($host, $port);
			} catch (\RedisException $e) {
				throw new Nette\InvalidStateException("Cannot connect to Redis server $host:$port.", 0, $e);
			}

			if (!$connected) {
				throw new Nette\InvalidStateException("Cannot connect to Redis server $host:$port.");
			}
		}

		if (mt_rand() / mt_getrandmax() < static::$gcProbability) {
			$this->clean([]);
		}
	}


	public function getConnection(): \Redis
	{
		return $this->redis;
	}


	public function read(string $key): mixed
	{
		$value = $this->redis->get($this->prefix . $key);
		$meta = is_string($value) ? unserialize(substr($value, self::VersionLength)) : null;
		if (!is_array($meta)) {
			return null;
		}

		// verify dependencies
		if (!empty($meta[self::MetaCallbacks]) && !Cache::checkCallbacks($meta[self::MetaCallbacks])) {
			$this->remove($key);
			return null;
		}

		if (!empty($meta[self::MetaDelta])) {
			$this->runScript(self::SlideScript, [$this->prefix . $key, substr($value, 0, self::VersionLength), $meta[self::MetaDelta]], 1);
		}

		return $meta[self::MetaData];
	}


	public function bulkRead(array $keys): array
	{
		$prefixedKeys = [];
		foreach ($keys as $key) {
			$prefixedKeys[] = $this->prefix . $key;
		}

		$values = $this->redis->mget($prefixedKeys);
		if (!is_array($values)) {
			return [];
		}

		$values = array_values($values);
		$result = $slideKeys = $slideArgs = [];
		$i = 0; // the caller's array keys may not be a sequence, mget() replies are
		foreach ($keys as $key) {
			$value = $values[$i++] ?? false;
			$meta = is_string($value) ? unserialize(substr($value, self::VersionLength)) : null;
			if (!is_array($meta)) {
				continue;

			} elseif (!empty($meta[self::MetaCallbacks]) && !Cache::checkCallbacks($meta[self::MetaCallbacks])) {
				$this->remove($key);
				continue;
			}

			if (!empty($meta[self::MetaDelta])) {
				$slideKeys[] = $this->prefix . $key;
				$slideArgs[] = substr($value, 0, self::VersionLength);
				$slideArgs[] = $meta[self::MetaDelta];
			}

			$result[$key] = $meta[self::MetaData];
		}

		if ($slideKeys) {
			$this->runScript(self::SlideScript, [...$slideKeys, ...$slideArgs], count($slideKeys));
		}

		return $result;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, mixed $data, array $dependencies): void
	{
		if (isset($dependencies[Cache::Items])) {
			throw new Nette\NotSupportedException('Dependency Cache::Items is not supported by ' . static::class . '.');
		}

		$meta = [
			self::MetaData => $data,
		];

		$ttl = null;
		if (isset($dependencies[Cache::Expire])) {
			$ttl = (int) $dependencies[Cache::Expire];
			if ($ttl <= 0) {
				$this->remove($key);
				return;
			}

			if (!empty($dependencies[Cache::Sliding])) {
				$meta[self::MetaDelta] = $ttl;
			}
		}

		if (isset($dependencies[Cache::Callbacks])) {
			$meta[self::MetaCallbacks] = $dependencies[Cache::Callbacks];
		}

		$tags = array_values((array) ($dependencies[Cache::Tags] ?? []));
		$keyTags = $this->prefix . self::KeyTagsPrefix . $key;

		$attempt = 0;
		do {
			$this->redis->watch($keyTags); // aborts the transaction when a concurrent write changes the tag index
			$oldTags = $this->redis->sMembers($keyTags);

			$redis = $this->redis->multi();
			foreach (is_array($oldTags) ? $oldTags : [] as $tag) {
				$redis->srem($this->prefix . self::TagIndexPrefix . $tag, $key);
			}

			$redis->del($keyTags);
			$redis->zRem($this->prefix . self::PriorityKey, $key);

			$value = random_bytes(self::VersionLength) . serialize($meta);
			if ($ttl === null) {
				$redis->set($this->prefix . $key, $value);
			} else {
				$redis->setex($this->prefix . $key, $ttl, $value);
			}

			if ($tags) {
				foreach ($tags as $tag) {
					$redis->sAdd($this->prefix . self::TagIndexPrefix . $tag, $key);
				}

				$redis->sAdd($keyTags, ...$tags); // does not expire with the item, so a later write still finds the old tags
			}

			if (isset($dependencies[Cache::Priority])) {
				$redis->zAdd($this->prefix . self::PriorityKey, (int) $dependencies[Cache::Priority], $key);
			}
		} while ($redis->exec() === false && ++$attempt < 3);
	}


	public function bulkWrite(array $items, array $dependencies): void
	{
		foreach ($items as $key => $data) {
			$this->write((string) $key, $data, $dependencies);
		}
	}


	public function remove(string $key): void
	{
		$this->bulkRemove([$key]);
	}


	public function bulkRemove(array $keys): void
	{
		if (!$keys) {
			return;
		}

		$pipe = $this->redis->multi(\Redis::PIPELINE);
		foreach ($keys as $key) {
			$pipe->sMembers($this->prefix . self::KeyTagsPrefix . $key);
		}

		$tagLists = $pipe->exec();

		$redis = $this->redis->multi();
		$i = 0; // the caller's array keys may not be a sequence, pipeline replies are
		foreach ($keys as $key) {
			$tags = $tagLists[$i++] ?? null;
			foreach (is_array($tags) ? $tags : [] as $tag) {
				$redis->srem($this->prefix . self::TagIndexPrefix . $tag, $key);
			}

			$redis->del($this->prefix . self::KeyTagsPrefix . $key);
			$redis->zRem($this->prefix . self::PriorityKey, $key);
			$redis->del($this->prefix . $key);
		}

		$redis->exec();
	}


	public function clean(array $conditions): void
	{
		if (!empty($conditions[Cache::All])) {
			if ($this->prefix === '') {
				$this->redis->flushDB();
			} else {
				foreach ($this->scan('') as $found) {
					$this->redis->del($found);
				}
			}

			return;

		} elseif (!$conditions) {
			$this->collectGarbage();
			return;
		}

		$keys = [];
		foreach ((array) ($conditions[Cache::Tags] ?? []) as $tag) {
			$members = $this->redis->sMembers($this->prefix . self::TagIndexPrefix . $tag);
			if (is_array($members)) {
				$keys = [...$keys, ...$members];
			}
		}

		if (isset($conditions[Cache::Priority])) {
			$members = $this->redis->zRangeByScore($this->prefix . self::PriorityKey, '-inf', (string) (int) $conditions[Cache::Priority]);
			if (is_array($members)) {
				$keys = [...$keys, ...$members];
			}
		}

		foreach ((array) ($conditions[Cache::Namespaces] ?? []) as $namespace) {
			$keys = [...$keys, ...$this->findNamespaceKeys($namespace)];
		}

		if ($keys) {
			$this->bulkRemove(array_values(array_unique($keys)));
		}
	}


	/**
	 * Removes from the indexes of tags and priorities the items that no longer exist, atomically
	 * per batch, so that a key written in between does not lose its index entries.
	 */
	private function collectGarbage(): void
	{
		$keyTagsLength = strlen($this->prefix . self::KeyTagsPrefix);
		foreach ($this->scan(self::KeyTagsPrefix) as $found) {
			$keys = array_map(fn($keyTags) => substr($keyTags, $keyTagsLength), $found);
			$this->runScript(self::RemoveTagsOfMissingScript, [$this->prefix, self::KeyTagsPrefix, self::TagIndexPrefix, ...$keys], 0);
		}

		$priorityKey = $this->prefix . self::PriorityKey;
		$members = $this->redis->zRange($priorityKey, 0, -1);
		foreach (array_chunk(is_array($members) ? $members : [], 1000) as $chunk) {
			$this->runScript(self::RemoveMissingScript, [$priorityKey, $this->prefix, ...$chunk], 1);
		}
	}


	/**
	 * Returns keys belonging directly to the namespace (not to nested namespaces).
	 * @return list<string>
	 */
	private function findNamespaceKeys(string $namespace): array
	{
		$prefixLen = strlen($this->prefix);
		$innerLen = strlen($namespace) + strlen(Cache::NamespaceSeparator);
		$keys = [];
		foreach ($this->scan($namespace . Cache::NamespaceSeparator) as $found) {
			foreach ($found as $prefixedKey) {
				$key = substr($prefixedKey, $prefixLen);
				if (!str_contains(substr($key, $innerLen), Cache::NamespaceSeparator)) {
					$keys[] = $key;
				}
			}
		}

		return $keys;
	}


	/**
	 * Iterates over the keys beginning with prefix + suffix in batches, without blocking the server.
	 * @return \Generator<string[]>
	 */
	private function scan(string $suffix): \Generator
	{
		$pattern = addcslashes($this->prefix . $suffix, '?*[]^\\') . '*';
		$iterator = null;
		do {
			$found = $this->redis->scan($iterator, $pattern, 1000);
			if (is_array($found) && $found) {
				yield $found;
			}
		} while ($iterator);
	}


	/**
	 * Runs the Lua script by its SHA1, sending its text only when the server does not have it cached.
	 * @param  mixed[]  $args  keys followed by other arguments
	 */
	private function runScript(string $script, array $args, int $numKeys): void
	{
		if ($this->redis->evalsha(sha1($script), $args, $numKeys) === false
			&& str_starts_with((string) $this->redis->getLastError(), 'NOSCRIPT')
		) {
			$this->redis->clearLastError();
			$this->redis->eval($script, $args, $numKeys);
		}
	}
}
