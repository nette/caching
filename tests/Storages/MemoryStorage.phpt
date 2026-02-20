<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemoryStorage dependencies test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic', function () {
	$cache = new Cache(new MemoryStorage);
	Assert::null($cache->load('key'));

	$cache->save('key', 'value');
	Assert::same('value', $cache->load('key'));

	$cache->remove('key');
	Assert::null($cache->load('key'));
});


test('expiration and sliding expiration', function () {
	$cache = new Cache(new MemoryStorage);
	$cache->save('fixed', 'value', [
		Cache::Expire => 2,
	]);
	$cache->save('sliding', 'value', [
		Cache::Expire => 2,
		Cache::Sliding => true,
	]);

	Assert::same('value', $cache->load('fixed'));

	for ($i = 0; $i < 3; $i++) {
		sleep(1);
		Assert::same('value', $cache->load('sliding'));
	}

	Assert::null($cache->load('fixed'));

	sleep(3);
	Assert::null($cache->load('sliding'));
});


test('sliding without expiration is ignored', function () {
	$cache = new Cache(new MemoryStorage);
	Assert::noError(fn() => $cache->save('key', 'value', [Cache::Sliding => true]));
	Assert::same('value', $cache->load('key'));
});


test('tags', function () {
	$cache = new Cache(new MemoryStorage);
	$cache->save('key1', 'value1', [Cache::Tags => ['one', 'two']]);
	$cache->save('key2', 'value2', [Cache::Tags => ['one', 'three']]);
	$cache->save('key3', 'value3', [Cache::Tags => ['two', 'three']]);
	$cache->save('key4', 'value4');

	$cache->clean([Cache::Tags => ['one', 'xx']]);

	Assert::null($cache->load('key1'));
	Assert::null($cache->load('key2'));
	Assert::same('value3', $cache->load('key3'));
	Assert::same('value4', $cache->load('key4'));
});


test('priority', function () {
	$cache = new Cache(new MemoryStorage);
	$cache->save('key0', 'value0', [Cache::Priority => 0]);
	$cache->save('key1', 'value1', [Cache::Priority => 100]);
	$cache->save('key2', 'value2', [Cache::Priority => 200]);
	$cache->save('key3', 'value3', [Cache::Priority => 300]);
	$cache->save('key4', 'value4');

	$cache->clean([Cache::Priority => '200']);

	Assert::null($cache->load('key0'));
	Assert::null($cache->load('key1'));
	Assert::null($cache->load('key2'));
	Assert::same('value3', $cache->load('key3'));
	Assert::same('value4', $cache->load('key4'));
});


test('clean all', function () {
	$cache = new Cache(new MemoryStorage);
	$cache->save('key1', 'value1');
	$cache->save('key2', 'value2', [Cache::Tags => ['one']]);

	$cache->clean([Cache::All => true]);

	Assert::null($cache->load('key1'));
	Assert::null($cache->load('key2'));
});
