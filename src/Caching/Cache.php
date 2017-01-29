<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Caching;

use Nette;
use Nette\Utils\Callback;


/**
 * Implements the cache for a application.
 */
class Cache
{
	use Nette\SmartObject;

	/** dependency */
	const PRIORITY = 'priority',
		EXPIRATION = 'expire',
		EXPIRE = 'expire',
		SLIDING = 'sliding',
		TAGS = 'tags',
		FILES = 'files',
		ITEMS = 'items',
		CONSTS = 'consts',
		CALLBACKS = 'callbacks',
		ALL = 'all';

	/** @internal */
	const NAMESPACE_SEPARATOR = "\x00";

	/** @var IStorage */
	private $storage;

	/** @var string */
	private $namespace;


	public function __construct(IStorage $storage, $namespace = NULL)
	{
		$this->storage = $storage;
		$this->namespace = $namespace . self::NAMESPACE_SEPARATOR;
	}


	/**
	 * Returns cache storage.
	 */
	public function getStorage(): IStorage
	{
		return $this->storage;
	}


	/**
	 * Returns cache namespace.
	 */
	public function getNamespace(): string
	{
		return (string) substr($this->namespace, 0, -1);
	}


	/**
	 * Returns new nested cache object.
	 * @return static
	 */
	public function derive(string $namespace)
	{
		$derived = new static($this->storage, $this->namespace . $namespace);
		return $derived;
	}


	/**
	 * Reads the specified item from the cache or generate it.
	 * @param  mixed
	 * @return mixed
	 */
	public function load($key, callable $fallback = NULL)
	{
		$data = $this->storage->read($this->generateKey($key));
		if ($data === NULL && $fallback) {
			return $this->save($key, function (&$dependencies) use ($fallback) {
				return $fallback(...[&$dependencies]);
			});
		}
		return $data;
	}


	/**
	 * Reads multiple items from the cache.
	 */
	public function bulkLoad(array $keys, callable $fallback = NULL): array
	{
		if (count($keys) === 0) {
			return [];
		}
		foreach ($keys as $key) {
			if (!is_scalar($key)) {
				throw new Nette\InvalidArgumentException('Only scalar keys are allowed in bulkLoad()');
			}
		}
		$storageKeys = array_map([$this, 'generateKey'], $keys);
		if (!$this->storage instanceof IBulkReader) {
			$result = array_combine($keys, array_map([$this->storage, 'read'], $storageKeys));
			if ($fallback !== NULL) {
				foreach ($result as $key => $value) {
					if ($value === NULL) {
						$result[$key] = $this->save($key, function (&$dependencies) use ($key, $fallback) {
							return $fallback(...[$key, &$dependencies]);
						});
					}
				}
			}
			return $result;
		}

		$cacheData = $this->storage->bulkRead($storageKeys);
		$result = [];
		foreach ($keys as $i => $key) {
			$storageKey = $storageKeys[$i];
			if (isset($cacheData[$storageKey])) {
				$result[$key] = $cacheData[$storageKey];
			} elseif ($fallback) {
				$result[$key] = $this->save($key, function (&$dependencies) use ($key, $fallback) {
					return $fallback(...[$key, &$dependencies]);
				});
			} else {
				$result[$key] = NULL;
			}
		}
		return $result;
	}


	/**
	 * Writes item into the cache.
	 * Dependencies are:
	 * - Cache::PRIORITY => (int) priority
	 * - Cache::EXPIRATION => (timestamp) expiration
	 * - Cache::SLIDING => (bool) use sliding expiration?
	 * - Cache::TAGS => (array) tags
	 * - Cache::FILES => (array|string) file names
	 * - Cache::ITEMS => (array|string) cache items
	 * - Cache::CONSTS => (array|string) cache items
	 *
	 * @param  mixed
	 * @param  mixed
	 * @return mixed  value itself
	 * @throws Nette\InvalidArgumentException
	 */
	public function save($key, $data, array $dependencies = NULL)
	{
		$key = $this->generateKey($key);

		if ($data instanceof \Closure) {
			$this->storage->lock($key);
			try {
				$data = $data(...[&$dependencies]);
			} catch (\Throwable $e) {
				$this->storage->remove($key);
				throw $e;
			}
		}

		if ($data === NULL) {
			$this->storage->remove($key);
		} else {
			$dependencies = $this->completeDependencies($dependencies);
			if (isset($dependencies[Cache::EXPIRATION]) && $dependencies[Cache::EXPIRATION] <= 0) {
				$this->storage->remove($key);
			} else {
				$this->storage->write($key, $data, $dependencies);
			}
			return $data;
		}
	}


	private function completeDependencies(?array $dp): array
	{
		// convert expire into relative amount of seconds
		if (isset($dp[self::EXPIRATION])) {
			$dp[self::EXPIRATION] = Nette\Utils\DateTime::from($dp[self::EXPIRATION])->format('U') - time();
		}

		// make list from TAGS
		if (isset($dp[self::TAGS])) {
			$dp[self::TAGS] = array_values((array) $dp[self::TAGS]);
		}

		// convert FILES into CALLBACKS
		if (isset($dp[self::FILES])) {
			foreach (array_unique((array) $dp[self::FILES]) as $item) {
				$dp[self::CALLBACKS][] = [[__CLASS__, 'checkFile'], $item, @filemtime($item) ?: NULL]; // @ - stat may fail
			}
			unset($dp[self::FILES]);
		}

		// add namespaces to items
		if (isset($dp[self::ITEMS])) {
			$dp[self::ITEMS] = array_unique(array_map([$this, 'generateKey'], (array) $dp[self::ITEMS]));
		}

		// convert CONSTS into CALLBACKS
		if (isset($dp[self::CONSTS])) {
			foreach (array_unique((array) $dp[self::CONSTS]) as $item) {
				$dp[self::CALLBACKS][] = [[__CLASS__, 'checkConst'], $item, constant($item)];
			}
			unset($dp[self::CONSTS]);
		}

		if (!is_array($dp)) {
			$dp = [];
		}
		return $dp;
	}


	/**
	 * Removes item from the cache.
	 * @param  mixed
	 */
	public function remove($key): void
	{
		$this->save($key, NULL);
	}


	/**
	 * Removes items from the cache by conditions.
	 * Conditions are:
	 * - Cache::PRIORITY => (int) priority
	 * - Cache::TAGS => (array) tags
	 * - Cache::ALL => TRUE
	 */
	public function clean(array $conditions = NULL): void
	{
		$conditions = (array) $conditions;
		if (isset($conditions[self::TAGS])) {
			$conditions[self::TAGS] = array_values((array) $conditions[self::TAGS]);
		}
		$this->storage->clean($conditions);
	}


	/**
	 * Caches results of function/method calls.
	 * @param  mixed
	 * @return mixed
	 */
	public function call($function)
	{
		$key = func_get_args();
		if (is_array($function) && is_object($function[0])) {
			$key[0][0] = get_class($function[0]);
		}
		return $this->load($key, function () use ($function, $key) {
			return Callback::invokeArgs($function, array_slice($key, 1));
		});
	}


	/**
	 * Caches results of function/method calls.
	 * @param  mixed
	 */
	public function wrap($function, array $dependencies = NULL): \Closure
	{
		return function () use ($function, $dependencies) {
			$key = [$function, func_get_args()];
			if (is_array($function) && is_object($function[0])) {
				$key[0][0] = get_class($function[0]);
			}
			$data = $this->load($key);
			if ($data === NULL) {
				$data = $this->save($key, Callback::invokeArgs($function, $key[1]), $dependencies);
			}
			return $data;
		};
	}


	/**
	 * Starts the output cache.
	 * @param  mixed
	 */
	public function start($key): ?OutputHelper
	{
		$data = $this->load($key);
		if ($data === NULL) {
			return new OutputHelper($this, $key);
		}
		echo $data;
		return NULL;
	}


	/**
	 * Generates internal cache key.
	 */
	protected function generateKey($key): string
	{
		return $this->namespace . md5(is_scalar($key) ? (string) $key : serialize($key));
	}


	/********************* dependency checkers ****************d*g**/


	/**
	 * Checks CALLBACKS dependencies.
	 */
	public static function checkCallbacks(array $callbacks): bool
	{
		foreach ($callbacks as $callback) {
			if (!array_shift($callback)(...$callback)) {
				return FALSE;
			}
		}
		return TRUE;
	}


	/**
	 * Checks CONSTS dependency.
	 */
	private static function checkConst(string $const, $value): bool
	{
		return defined($const) && constant($const) === $value;
	}


	/**
	 * Checks FILES dependency.
	 */
	private static function checkFile(string $file, ?int $time): bool
	{
		return @filemtime($file) == $time; // @ - stat may fail
	}

}
