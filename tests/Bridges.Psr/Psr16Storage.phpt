<?php declare(strict_types=1);

/**
 * Test: Nette\Bridges\Psr\Psr16Storage adapts PSR-16 cache to Nette Storage.
 */

use Nette\Bridges\Psr\Psr16Storage;
use Nette\Caching\Cache;
use Nette\Caching\Storages\Journal;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class ArrayJournal implements Journal
{
	private array $tags = []; // key => list of tags
	private array $priorities = []; // key => priority


	public function write(string $key, array $dependencies): void
	{
		if (!empty($dependencies[Cache::Tags])) {
			$this->tags[$key] = $dependencies[Cache::Tags];
		}

		if (isset($dependencies[Cache::Priority])) {
			$this->priorities[$key] = $dependencies[Cache::Priority];
		}
	}


	public function clean(array $conditions): ?array
	{
		if (!empty($conditions[Cache::All])) {
			$this->tags = $this->priorities = [];
			return null;
		}

		$keys = [];
		foreach ((array) ($conditions[Cache::Tags] ?? []) as $tag) {
			foreach ($this->tags as $key => $tags) {
				if (in_array($tag, $tags, true)) {
					$keys[] = $key;
				}
			}
		}

		if (isset($conditions[Cache::Priority])) {
			foreach ($this->priorities as $key => $priority) {
				if ($priority <= $conditions[Cache::Priority]) {
					$keys[] = $key;
				}
			}
		}

		foreach ($keys as $key) {
			unset($this->tags[$key], $this->priorities[$key]);
		}

		return array_values(array_unique($keys));
	}
}


class ArraySimpleCache implements Psr\SimpleCache\CacheInterface
{
	public array $data = []; // key => [value, expiration timestamp|null]


	public function get(string $key, mixed $default = null): mixed
	{
		if (!isset($this->data[$key])) {
			return $default;
		}

		[$value, $expire] = $this->data[$key];
		if ($expire !== null && $expire <= time()) {
			unset($this->data[$key]);
			return $default;
		}

		return $value;
	}


	public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
	{
		$this->data[$key] = [$value, $ttl === null ? null : time() + $ttl];
		return true;
	}


	public function delete(string $key): bool
	{
		unset($this->data[$key]);
		return true;
	}


	public function clear(): bool
	{
		$this->data = [];
		return true;
	}


	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$result = [];
		foreach ($keys as $key) {
			$result[$key] = $this->get($key, $default);
		}

		return $result;
	}


	public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value, $ttl);
		}

		return true;
	}


	public function deleteMultiple(iterable $keys): bool
	{
		foreach ($keys as $key) {
			$this->delete($key);
		}

		return true;
	}


	public function has(string $key): bool
	{
		return $this->get($key) !== null;
	}
}


test('basic roundtrip', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));

	Assert::null($cache->load('key'));

	$value = ['hello', new stdClass];
	$cache->save('key', $value);
	Assert::equal($value, $cache->load('key'));

	$cache->remove('key');
	Assert::null($cache->load('key'));
});


test('expiration', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));
	$cache->save('key', 'value', [Cache::Expire => 2]);
	Assert::same('value', $cache->load('key'));

	sleep(3);
	Assert::null($cache->load('key'));
});


test('sliding expiration is not supported', function () {
	$psr = new ArraySimpleCache;
	$cache = new Cache(new Psr16Storage($psr));
	$dependencies = [Cache::Expire => 10, Cache::Sliding => true];
	Assert::exception(
		fn() => $cache->save('key', 'value', $dependencies),
		Nette\NotSupportedException::class,
		'Dependency Cache::Sliding is not supported by Nette\Bridges\Psr\Psr16Storage.',
	);
	Assert::exception(
		fn() => $cache->bulkSave(['key' => 'value'], $dependencies),
		Nette\NotSupportedException::class,
	);
	Assert::same([], $psr->data);
});


test('callbacks via Files dependency', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));

	$dependentFile = getTempDir() . '/spec-Psr16Storage.txt';
	@unlink($dependentFile); // @ file may not exist

	$cache->save('key', 'value', [Cache::Files => $dependentFile]);
	Assert::same('value', $cache->load('key'));

	file_put_contents($dependentFile, 'a');
	clearstatcache();
	Assert::null($cache->load('key'));
});


test('Items dependency is not supported', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Items => ['another']]),
		Nette\NotSupportedException::class,
		'Dependency Cache::Items is not supported by Nette\Bridges\Psr\Psr16Storage.',
	);
});


test('tags require journal', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Tags => ['tag']]),
		Nette\InvalidStateException::class,
		'CacheJournal has not been provided.',
	);
});


test('tags & priority via journal', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache, journal: new ArrayJournal));
	$cache->save('key1', 'value1', [Cache::Tags => ['one']]);
	$cache->save('key2', 'value2', [Cache::Tags => ['two'], Cache::Priority => 100]);
	$cache->save('key3', 'value3');

	$cache->clean([Cache::Tags => ['one']]);
	Assert::null($cache->load('key1'));
	Assert::same('value2', $cache->load('key2'));

	$cache->clean([Cache::Priority => 100]);
	Assert::null($cache->load('key2'));
	Assert::same('value3', $cache->load('key3'));
});


test('bulk operations', function () {
	$cache = new Cache(new Psr16Storage(new ArraySimpleCache));
	$cache->bulkSave(['a' => 1, 'b' => 2]);
	Assert::same(['a' => 1, 'b' => 2, 'c' => null], $cache->bulkLoad(['a', 'b', 'c']));

	$cache->bulkSave(['a' => null]);
	Assert::null($cache->load('a'));
});


test('clean all', function () {
	$psr = new ArraySimpleCache;
	$cache = new Cache(new Psr16Storage($psr));
	$cache->save('key', 'value');

	$cache->clean([Cache::All => true]);
	Assert::null($cache->load('key'));
	Assert::same([], $psr->data);
});


test('prefix isolation', function () {
	$psr = new ArraySimpleCache;
	$a = new Psr16Storage($psr, 'a.');
	$b = new Psr16Storage($psr, 'b.');

	$a->write('key', 'A', []);
	$b->write('key', 'B', []);
	Assert::same('A', $a->read('key'));
	Assert::same('B', $b->read('key'));

	$a->remove('key');
	Assert::null($a->read('key'));
	Assert::same('B', $b->read('key'));
});
