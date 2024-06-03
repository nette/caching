<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Caching/Cache.php';


test('set ttl unlimited', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);
	$cache->set('test', '1');
	Assert::same([
		'data' => '1',
		'dependencies' => [],
	], $storage->read('test'));
});

test('set ttl int', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$cache->set('test', '2', 1);
	Assert::same([
		'data' => '2',
		'dependencies' => [
			Caching\Cache::Expire => 1,
		],
	], $storage->read('test'));
});

test('set ttl DateInterval', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$interval = new DateInterval('P3Y6M4DT12H30M5S');
	$cache->set('test', '3', $interval);
	Assert::same([
		'data' => '3',
		'dependencies' => [
			Caching\Cache::Expire => (new DateTime)->add($interval)->getTimestamp() - (new DateTime)->getTimestamp(),
		],
	], $storage->read('test'));
});
