<?php

/**
 * Test: Nette\Caching\Cache save().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


test('storage without bulk write support', function () {
	$storage = new TestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1, 2], $cache->bulkSave([1, 2]), 'data');
	Assert::same([1 => 'value1', 2 => 'value2'], $cache->bulkSave([1 => 'value1', 2 => 'value2']), 'data');

	$data = $cache->bulkLoad([1, 2]);
	Assert::same('value1', $data[1]['data']);
	Assert::same('value2', $data[2]['data']);
});

test('storage with bulk write support', function () {
	$storage = new BulkWriteTestStorage;
	$cache = new Cache($storage, 'ns');
	Assert::same([1, 2], $cache->bulkSave([1, 2]), 'data');
	Assert::same([1 => 'value1', 2 => 'value2'], $cache->bulkSave([1 => 'value1', 2 => 'value2']), 'data');

	$data = $cache->bulkLoad([1, 2]);
	Assert::same('value1', $data[1]['data']);
	Assert::same('value2', $data[2]['data']);
});

test('dependencies', function () {
	$storage = new BulkWriteTestStorage;
	$cache = new Cache($storage, 'ns');
	$dependencies = [Cache::Tags => ['tag']];
	$cache->bulkSave([1 => 'value1', 2 => 'value2'], $dependencies);

	$data = $cache->bulkLoad([1, 2]);
	Assert::same($dependencies, $data[1]['dependencies']);
	Assert::same($dependencies, $data[2]['dependencies']);

	$cache->clean($dependencies);

	Assert::same([1 => null, 2 => null], $cache->bulkLoad([1, 2]));
});
