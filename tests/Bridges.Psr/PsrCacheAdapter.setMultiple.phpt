<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Caching/Cache.php';


test('set multiple ttl unlimited', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$cache->setMultiple(['test1' => '1', 'test2' => '2']);

	Assert::same([
		'data' => '1',
		'dependencies' => [],
	], $storage->read('test1'));
	Assert::same([
		'data' => '2',
		'dependencies' => [],
	], $storage->read('test2'));
});

test('set multiple ttl int', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$cache->setMultiple(['test1' => '1', 'test2' => '2'], 1);

	Assert::same([
		'data' => '1',
		'dependencies' => [
			Caching\Cache::Expire => 1,
		],
	], $storage->read('test1'));

	Assert::same([
		'data' => '2',
		'dependencies' => [
			Caching\Cache::Expire => 1,
		],
	], $storage->read('test2'));
});

test('set multiple ttl DateInterval', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$interval = new DateInterval('P3Y6M4DT12H30M5S');
	$cache->setMultiple(['test1' => '1', 'test2' => '2'], $interval);
	Assert::same([
		'data' => '1',
		'dependencies' => [
			Caching\Cache::Expire => (new DateTime)->add($interval)->getTimestamp() - (new DateTime)->getTimestamp(),
		],
	], $storage->read('test1'));

	Assert::same([
		'data' => '2',
		'dependencies' => [
			Caching\Cache::Expire => (new DateTime)->add($interval)->getTimestamp() - (new DateTime)->getTimestamp(),
		],
	], $storage->read('test2'));
});
