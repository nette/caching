<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage priority dependency.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$cache = new Cache(createRedisStorage('nette-priority:'));


test('cleaning by priority', function () use ($cache) {
	$cache->save('key1', 'value1', [Cache::Priority => 100]);
	$cache->save('key2', 'value2', [Cache::Priority => 200]);
	$cache->save('key3', 'value3', [Cache::Priority => 300]);
	$cache->save('key4', 'value4');

	$cache->clean([Cache::Priority => '200']);

	Assert::null($cache->load('key1'));
	Assert::null($cache->load('key2'));
	Assert::same('value3', $cache->load('key3'));
	Assert::same('value4', $cache->load('key4'));
});


test('priority zero', function () use ($cache) {
	$cache->save('key5', 'value5', [Cache::Priority => 0]);
	$cache->clean([Cache::Priority => 0]);
	Assert::null($cache->load('key5'));
});


test('overwrite removes old priority', function () use ($cache) {
	$cache->save('key6', 'value6', [Cache::Priority => 100]);
	$cache->save('key6', 'value6b');

	$cache->clean([Cache::Priority => 100]);
	Assert::same('value6b', $cache->load('key6'));
});
