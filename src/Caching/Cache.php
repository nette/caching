<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching;

use Nette;
use function count, func_get_args, is_array, is_object, is_scalar;


/**
 * Caching with namespace isolation, dependency tracking, and stampede prevention.
 */
class Cache
{
	/** dependency */
	public const
		Priority = 'priority',
		Expire = 'expire',
		Sliding = 'sliding',
		Tags = 'tags',
		Files = 'files',
		Items = 'items',
		Constants = 'consts',
		Callbacks = 'callbacks',
		Namespaces = 'namespaces',
		All = 'all';

	#[\Deprecated('use Cache::Priority, Dependencies::$priority or clean(priority: ...)')]
	public const PRIORITY = self::Priority;

	#[\Deprecated('use Cache::Expire or Dependencies::$expire')]
	public const EXPIRATION = self::Expire;

	#[\Deprecated('use Cache::Expire or Dependencies::$expire')]
	public const EXPIRE = self::Expire;

	#[\Deprecated('use Cache::Sliding or Dependencies::$sliding')]
	public const SLIDING = self::Sliding;

	#[\Deprecated('use Cache::Tags, Dependencies::$tags or clean(tags: ...)')]
	public const TAGS = self::Tags;

	#[\Deprecated('use Cache::Files or Dependencies::$files')]
	public const FILES = self::Files;

	#[\Deprecated('use Cache::Items or Dependencies::$items')]
	public const ITEMS = self::Items;

	#[\Deprecated('use Cache::Constants or Dependencies::$constants')]
	public const CONSTS = self::Constants;

	#[\Deprecated('use Cache::Callbacks or Dependencies::$callbacks')]
	public const CALLBACKS = self::Callbacks;

	#[\Deprecated('use Cache::Namespaces or clean(namespaces: ...)')]
	public const NAMESPACES = self::Namespaces;

	#[\Deprecated('use Cache::All or clean(all: true)')]
	public const ALL = self::All;

	/** @internal */
	public const NamespaceSeparator = "\x00";

	#[\Deprecated('use Cache::NamespaceSeparator')]
	public const NAMESPACE_SEPARATOR = self::NamespaceSeparator;

	/** keys of the conditions of clean(), which are also the names of its parameters */
	private const Conditions = [self::Tags, self::Priority, self::Namespaces, self::All];

	private readonly string $namespace;


	public function __construct(
		private readonly Storage $storage,
		?string $namespace = null,
	) {
		$this->namespace = $namespace . self::NamespaceSeparator;
	}


	final public function getStorage(): Storage
	{
		return $this->storage;
	}


	final public function getNamespace(): string
	{
		return substr($this->namespace, 0, -1);
	}


	/**
	 * Returns a new cache object scoped to a sub-namespace.
	 */
	public function derive(string $namespace): static
	{
		return new static($this->storage, $this->namespace . $namespace);
	}


	/**
	 * Returns a cached item, or generates and stores it using the given callback. The callback receives
	 * the dependencies as an object when its parameter is typed Dependencies, otherwise as an array by reference.
	 * @param ?(\Closure(mixed &$dependencies): mixed)  $generator
	 * @param-immediately-invoked-callable  $generator
	 * @param Dependencies|array<string, mixed>|null  $dependencies
	 */
	public function load(mixed $key, ?callable $generator = null, Dependencies|array|null $dependencies = null): mixed
	{
		return $generator
			? $this->readOrGenerate($key, fn() => $this->generate($key, $generator, [], $dependencies))
			: $this->storage->read($this->generateKey($key));
	}


	/**
	 * Returns multiple cached items at once, generating missing ones using the given callback, which receives
	 * the key and then the dependencies as load() does.
	 * @template TKey of int|string
	 * @param list<TKey>  $keys
	 * @param ?(\Closure(TKey $key, mixed &$dependencies): mixed)  $generator
	 * @param-immediately-invoked-callable  $generator
	 * @return array<TKey, mixed>
	 */
	public function bulkLoad(array $keys, ?callable $generator = null): array
	{
		if ($keys === []) {
			return [];
		}

		foreach ($keys as $key) {
			if (!is_scalar($key)) {
				throw new Nette\InvalidArgumentException('Only scalar keys are allowed in bulkLoad()');
			}
		}

		$result = [];
		if (!$this->storage instanceof BulkReader) {
			foreach ($keys as $key) {
				$result[$key] = $generator
					? $this->readOrGenerate($key, fn() => $this->generate($key, $generator, [$key], null))
					: $this->load($key);
			}

			return $result;
		}

		$storageKeys = array_map($this->generateKey(...), $keys);
		$cacheData = $this->storage->bulkRead($storageKeys);
		foreach ($keys as $i => $key) {
			$storageKey = $storageKeys[$i];
			if (isset($cacheData[$storageKey])) {
				$result[$key] = $cacheData[$storageKey];
			} elseif ($generator) {
				$result[$key] = $this->readOrGenerate($key, fn() => $this->generate($key, $generator, [$key], null));
			} else {
				$result[$key] = null;
			}
		}

		return $result;
	}


	/**
	 * Reads the item, or lets the storage run the generator, under its lock when it has one.
	 * @param  \Closure(): mixed  $generate
	 */
	private function readOrGenerate(mixed $key, \Closure $generate): mixed
	{
		$storageKey = $this->generateKey($key);
		return $this->storage instanceof LockingReader
			? $this->storage->readOrGenerate($storageKey, $generate)
			: $this->storage->read($storageKey) ?? $generate();
	}


	/**
	 * Runs the generator with the dependencies in the form it asks for and stores the result.
	 * @param  mixed[]  $args  arguments preceding the dependencies
	 * @param  Dependencies|array<string, mixed>|null  $dependencies
	 */
	private function generate(mixed $key, callable $generator, array $args, Dependencies|array|null $dependencies): mixed
	{
		$type = (Nette\Utils\Callback::toReflection($generator)->getParameters()[count($args)] ?? null)?->getType();
		if ($type instanceof \ReflectionNamedType && $type->getName() === Dependencies::class) {
			$dependencies = $dependencies instanceof Dependencies ? clone $dependencies : Dependencies::fromArray($dependencies ?? []);
			$data = $generator(...[...$args, $dependencies]);
		} else {
			$dependencies = $dependencies instanceof Dependencies ? $dependencies->toArray() : $dependencies;
			$data = $generator(...[...$args, &$dependencies]);
		}

		return $this->save($key, $data, $dependencies);
	}


	/**
	 * Stores item in the cache and returns it.
	 * Dependencies:
	 * - Cache::Priority => (int) priority
	 * - Cache::Expire => (string|int) expiration time, infinite if null
	 * - Cache::Sliding => (bool) use sliding expiration?
	 * - Cache::Tags => (array) tags
	 * - Cache::Files => (array|string) file paths
	 * - Cache::Items => (array|string) dependent cache keys
	 * - Cache::Constants => (array|string) PHP constant names
	 * @param Dependencies|array<string, mixed>|null  $dependencies
	 * @return mixed  value itself
	 * @throws Nette\InvalidArgumentException
	 */
	public function save(mixed $key, mixed $data, Dependencies|array|null $dependencies = null): mixed
	{
		$key = $this->generateKey($key);

		if ($data instanceof \Closure) {
			trigger_error(__METHOD__ . '() with a closure is deprecated, use load() with a generator.', E_USER_DEPRECATED);
			$data = $data(...[&$dependencies]);
		}

		if ($data === null) {
			$this->storage->remove($key);
			return null;
		} else {
			$dependencies = $this->completeDependencies($dependencies);
			if (isset($dependencies[self::Expire]) && $dependencies[self::Expire] <= 0) {
				$this->storage->remove($key);
			} else {
				$this->storage->write($key, $data, $dependencies);
			}

			return $data;
		}
	}


	/**
	 * Stores multiple items in the cache at once.
	 * @param mixed[]  $items
	 * @param Dependencies|array<string, mixed>|null  $dependencies
	 */
	public function bulkSave(array $items, Dependencies|array|null $dependencies = null): void
	{
		$write = $remove = [];

		if (!$this->storage instanceof BulkWriter) {
			foreach ($items as $key => $data) {
				$this->save($key, $data, $dependencies);
			}
			return;
		}

		$dependencies = $this->completeDependencies($dependencies);
		if (isset($dependencies[self::Expire]) && $dependencies[self::Expire] <= 0) {
			$this->storage->bulkRemove(array_map($this->generateKey(...), array_keys($items)));
			return;
		}

		foreach ($items as $key => $data) {
			$key = $this->generateKey($key);
			if ($data === null) {
				$remove[] = $key;
			} else {
				$write[$key] = $data;
			}
		}

		if ($remove) {
			$this->storage->bulkRemove($remove);
		}

		if ($write) {
			$this->storage->bulkWrite($write, $dependencies);
		}
	}


	/**
	 * Converts the dependencies to the array the storages get: expiration in seconds from now, files and constants
	 * as callbacks, items under their storage keys.
	 * @param Dependencies|array<string, mixed>|null  $dp
	 * @return array<string, mixed>
	 */
	private function completeDependencies(Dependencies|array|null $dp): array
	{
		$dp = $dp instanceof Dependencies ? $dp : Dependencies::fromArray($dp ?? []);

		$callbacks = $dp->callbacks;
		foreach (array_unique($dp->files) as $file) {
			$callbacks[] = [[self::class, 'checkFile'], $file, @filemtime($file) ?: null]; // @ file may not exist
		}

		foreach (array_unique($dp->constants) as $const) {
			$callbacks[] = [[self::class, 'checkConst'], $const, constant($const)];
		}

		return array_filter([
			self::Expire => $dp->expire === null ? null : Nette\Utils\DateTime::from($dp->expire)->getTimestamp() - time(),
			self::Sliding => $dp->sliding ?: null,
			self::Tags => $dp->tags ?: null,
			self::Priority => $dp->priority,
			self::Items => $dp->items ? array_unique(array_map($this->generateKey(...), $dp->items)) : null,
			self::Callbacks => $callbacks ?: null,
		], fn($value) => $value !== null);
	}


	public function remove(mixed $key): void
	{
		$this->save($key, null);
	}


	/**
	 * Removes items with any of the tags, with equal or lower priority, or in any of the namespaces;
	 * all removes the whole namespace of this cache, or the entire storage when it has none.
	 * The conditions can also be given as an array with Cache::* keys.
	 * @param ?array<string, mixed>  $conditions
	 * @param ?list<string>  $tags
	 * @param ?list<string>  $namespaces
	 */
	public function clean(
		?array $conditions = null,
		?array $tags = null,
		?int $priority = null,
		?array $namespaces = null,
		bool $all = false,
	): void
	{
		if ($conditions === null) {
			$this->cleanStorage($tags, $priority, $namespaces, $all);

		} elseif ($tags !== null || $priority !== null || $namespaces !== null || $all) {
			throw new Nette\InvalidArgumentException('Conditions can be given either as an array or as named arguments, not both.');

		} else {
			$this->cleanStorage(...self::convertConditions($conditions));
		}
	}


	/**
	 * @param ?array<string>  $tags
	 * @param ?array<string>  $namespaces
	 */
	private function cleanStorage(
		?array $tags = null,
		?int $priority = null,
		?array $namespaces = null,
		bool $all = false,
	): void
	{
		if (!$all && $priority === null && !$tags && !$namespaces) {
			if ($tags === null && $namespaces === null) {
				throw new Nette\InvalidArgumentException('Specify what to clean: tags, priority, namespaces or all; expired items are removed by Storage::clean([]).');
			}

			return; // only empty lists were given
		}

		if ($all) {
			$namespace = $this->getNamespace();
			$this->storage->clean($namespace === '' ? [self::All => true] : [self::Namespaces => [$namespace]]);
			return;
		}

		$this->storage->clean(array_filter([
			self::Tags => $tags === null ? null : array_values($tags),
			self::Priority => $priority,
			self::Namespaces => $namespaces === null ? null : array_values($namespaces),
		], fn($value) => $value !== null));
	}


	/**
	 * Converts the array with Cache::* keys to the named arguments of cleanStorage().
	 * @param  array<mixed>  $conditions
	 * @return array<string, mixed>
	 */
	private static function convertConditions(array $conditions): array
	{
		$args = [];
		foreach ($conditions as $key => $value) {
			if (!in_array($key, self::Conditions, true)) {
				throw self::createUnknownConditionException((string) $key);
			} elseif ($value !== null) {
				$args[$key] = ($key === self::Tags || $key === self::Namespaces) && !is_array($value) ? [$value] : $value;
			}
		}

		return $args;
	}


	private static function createUnknownConditionException(string $key): Nette\InvalidArgumentException
	{
		if (in_array($key, [self::Expire, self::Sliding, self::Files, self::Items, self::Constants, self::Callbacks], true)) {
			return new Nette\InvalidArgumentException("'$key' is a dependency of an item, not a condition of Cache::clean().");
		}

		$hint = Nette\Utils\Helpers::getSuggestion(self::Conditions, $key);
		return new Nette\InvalidArgumentException("Unknown condition '$key'" . ($hint ? ", did you mean '$hint'?" : '.'));
	}


	/**
	 * Calls a function and caches its result. Subsequent calls with the same arguments return the cached value.
	 * @param-immediately-invoked-callable  $function
	 */
	public function call(callable $function): mixed
	{
		$key = func_get_args();
		if (is_array($function) && is_object($function[0])) {
			$key[0][0] = $function[0]::class;
		}

		return $this->load($key, fn() => $function(...array_slice($key, 1)));
	}


	/**
	 * Returns a cached wrapper around a function. Each unique set of arguments is cached separately.
	 * @param  Dependencies|array<string, mixed>|null  $dependencies
	 * @return \Closure(mixed...): mixed
	 */
	public function wrap(callable $function, Dependencies|array|null $dependencies = null): \Closure
	{
		return function () use ($function, $dependencies) {
			$key = [$function, $args = func_get_args()];
			if (is_array($function) && is_object($function[0])) {
				$key[0][0] = $function[0]::class;
			}

			return $this->load($key, fn() => $function(...$args), $dependencies);
		};
	}


	/**
	 * Starts output buffering for caching. Returns null and echoes cached content if already cached,
	 * or returns an OutputHelper to capture and save new output.
	 */
	public function capture(mixed $key): ?OutputHelper
	{
		$data = $this->load($key);
		if ($data === null) {
			return new OutputHelper($this, $key);
		}

		echo $data;
		return null;
	}


	#[\Deprecated('use capture()')]
	public function start(mixed $key): ?OutputHelper
	{
		return $this->capture($key);
	}


	/**
	 * Generates a namespaced storage key from a user-provided key.
	 */
	protected function generateKey(mixed $key): string
	{
		return $this->namespace . hash('xxh128', is_scalar($key) ? (string) $key : serialize($key));
	}


	/********************* dependency checkers ****************d*g**/


	/**
	 * Validates all callback dependencies. Returns false if any callback returns false.
	 * @param list<array{0: callable, 1?: mixed, 2?: mixed}>  $callbacks
	 */
	public static function checkCallbacks(array $callbacks): bool
	{
		return array_all($callbacks, fn($callback) => array_shift($callback)(...$callback));
	}


	/**
	 * Checks whether a PHP constant still has the same value as when cached.
	 */
	private static function checkConst(string $const, mixed $value): bool
	{
		return defined($const) && constant($const) === $value;
	}


	/**
	 * Checks whether a file's modification time matches the recorded value.
	 */
	private static function checkFile(string $file, ?int $time): bool
	{
		return @filemtime($file) == $time; // @ file may not exist; intentionally ==, so that false equals null
	}
}
