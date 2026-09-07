<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage tags dependency.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$cache = new Cache(createRedisStorage('nette-tags:'));


test('cleaning by tags', function () use ($cache) {
	$cache->save('key1', 'value1', [Cache::Tags => ['one', 'two']]);
	$cache->save('key2', 'value2', [Cache::Tags => ['one', 'three']]);
	$cache->save('key3', 'value3', [Cache::Tags => ['two', 'three']]);
	$cache->save('key4', 'value4');

	$cache->clean([Cache::Tags => ['one']]);

	Assert::null($cache->load('key1'));
	Assert::null($cache->load('key2'));
	Assert::same('value3', $cache->load('key3'));
	Assert::same('value4', $cache->load('key4'));
});


test('overwrite removes old tags', function () use ($cache) {
	$cache->save('key5', 'value5', [Cache::Tags => ['old']]);
	$cache->save('key5', 'value5b', [Cache::Tags => ['new']]);

	$cache->clean([Cache::Tags => ['old']]);
	Assert::same('value5b', $cache->load('key5'));

	$cache->clean([Cache::Tags => ['new']]);
	Assert::null($cache->load('key5'));
});


test('remove() cleans tag indexes', function () use ($cache) {
	$cache->save('key6', 'value6', [Cache::Tags => ['six']]);
	$cache->remove('key6');
	$cache->save('key6', 'value6b');

	$cache->clean([Cache::Tags => ['six']]);
	Assert::same('value6b', $cache->load('key6'));
});


test('cleaning by multiple tags at once', function () use ($cache) {
	$cache->save('a', 'value', [Cache::Tags => ['gamma']]);
	$cache->save('b', 'value', [Cache::Tags => ['alpha', 'beta', 'gamma']]);

	$cache->clean([Cache::Tags => ['alpha', 'beta', 'gamma']]);
	Assert::null($cache->load('a'));
	Assert::null($cache->load('b'));
});


test('item expired with a tag and written again without it is not cleaned by that tag', function () use ($cache) {
	$cache->save('key7', 'old', [Cache::Expire => 1, Cache::Tags => ['old']]);
	sleep(2);
	$cache->save('key7', 'new', [Cache::Tags => ['new']]);

	$cache->clean([Cache::Tags => ['old']]);
	Assert::same('new', $cache->load('key7'));
});
