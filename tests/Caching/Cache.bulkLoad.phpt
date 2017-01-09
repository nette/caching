<?php

/**
 * Test: Nette\Caching\Cache load().
 */

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';

// storage without bulk load support
test(function () {
	$storage = new TestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1 => NULL, 2 => NULL], $cache->bulkLoad([1, 2]), 'data');

	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], function ($key) {
		return $key;
	}));

	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
});

// storage with bulk load support
test(function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1 => NULL, 2 => NULL], $cache->bulkLoad([1, 2]));

	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], function ($key) {
		return $key;
	}));

	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
});

// dependencies
test(function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	$dependencies = [Cache::TAGS => ['tag']];
	$cache->bulkLoad([1], function ($key, &$deps) use ($dependencies) {
		$deps = $dependencies;
		return $key;
	});

	$data = $cache->bulkLoad([1, 2]);
	Assert::same($dependencies, $data[1]['dependencies']);
});

test(function () {
	Assert::exception(function () {
		$cache = new Cache(new BulkReadTestStorage());
		$cache->bulkLoad([[1]]);
	}, Nette\InvalidArgumentException::class, 'Only scalar keys are allowed in bulkLoad()');
});
