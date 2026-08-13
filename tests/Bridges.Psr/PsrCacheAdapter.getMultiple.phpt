<?php declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\{BulkReader, Storage};
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class BulkTestStorage implements Storage, BulkReader
{
	public array $data = [];
	public int $singleReads = 0;


	public function read(string $key): mixed
	{
		$this->singleReads++;
		return $this->data[$key] ?? null;
	}


	public function bulkRead(array $keys): array
	{
		$result = [];
		foreach ($keys as $key) {
			if (isset($this->data[$key])) {
				$result[$key] = $this->data[$key];
			}
		}

		return $result;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, mixed $data, array $dependencies): void
	{
		$this->data[$key] = $data;
	}


	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}


	public function clean(array $conditions): void
	{
	}
}


test('get multiple', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	$x = iterator_to_array($cache->getMultiple(['test', 'test1']));

	Assert::same([
		'test' => null,
		'test1' => null,
	], $x);
});

test('get multiple with default', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	$x = iterator_to_array($cache->getMultiple(['test', 'test1'], true));

	Assert::same([
		'test' => true,
		'test1' => true,
	], $x);
});

test('empty key lists are accepted', function () {
	$cache = new PsrCacheAdapter(new BulkTestStorage);
	Assert::same([], iterator_to_array($cache->getMultiple([])));
	Assert::same([], iterator_to_array($cache->getMultiple(new ArrayIterator)));
});

test('get multiple uses bulk reading', function () {
	$storage = new BulkTestStorage;
	$cache = new PsrCacheAdapter($storage);
	$cache->set('a', 1);
	$cache->set('b', 2);

	Assert::same([
		'a' => 1,
		'b' => 2,
		'c' => 'default',
	], iterator_to_array($cache->getMultiple(['a', 'b', 'c'], 'default')));

	Assert::same(0, $storage->singleReads);
});

test('numeric string keys are preserved', function () {
	$cache = new PsrCacheAdapter(new BulkTestStorage);
	foreach ($cache->getMultiple(['5']) as $key => $value) {
		Assert::same('5', $key);
	}
});
