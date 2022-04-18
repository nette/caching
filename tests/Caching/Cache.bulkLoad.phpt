<?php

/**
 * Test: Nette\Caching\Cache load().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';

test('storage without bulk load support', function () {
	$storage = new TestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1 => null, 2 => null], $cache->bulkLoad([1, 2]), 'data');

	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], fn($key) => $key));

	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
});

test('storage with bulk load support', function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1 => null, 2 => null], $cache->bulkLoad([1, 2]));

	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], fn($key) => $key));

	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
});

test('dependencies', function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	$dependencies = [Cache::Tags => ['tag']];
	$cache->bulkLoad([1], function ($key, &$deps) use ($dependencies) {
		$deps = $dependencies;
		return $key;
	});

	$data = $cache->bulkLoad([1, 2]);
	Assert::same($dependencies, $data[1]['dependencies']);
});

test('', function () {
	Assert::exception(function () {
		$cache = new Cache(new BulkReadTestStorage);
		$cache->bulkLoad([[1]]);
	}, Nette\InvalidArgumentException::class, 'Only scalar keys are allowed in bulkLoad()');
});
