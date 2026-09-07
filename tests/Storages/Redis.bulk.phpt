<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage bulk operations.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$storage = createRedisStorage('nette-bulk:');
$cache = new Cache($storage);


test('bulkSave & bulkLoad', function () use ($cache) {
	$cache->bulkSave(['a' => 1, 'b' => 2, 'c' => 3]);
	Assert::same(['a' => 1, 'b' => 2, 'c' => 3, 'd' => null], $cache->bulkLoad(['a', 'b', 'c', 'd']));
});


test('bulkSave with tags & bulkRemove via clean', function () use ($cache) {
	$cache->bulkSave(['e' => 5, 'f' => 6], [Cache::Tags => ['bulk']]);
	Assert::same(['e' => 5, 'f' => 6], $cache->bulkLoad(['e', 'f']));

	$cache->clean([Cache::Tags => ['bulk']]);
	Assert::same(['e' => null, 'f' => null], $cache->bulkLoad(['e', 'f']));
});


test('bulkSave null values removes items', function () use ($cache) {
	$cache->bulkSave(['g' => 7]);
	$cache->bulkSave(['g' => null]);
	Assert::null($cache->load('g'));
});


test('bulkRead directly', function () use ($storage) {
	$storage->write('x', 'value', []);
	Assert::same(['x' => 'value'], $storage->bulkRead(['x', 'missing']));
});
