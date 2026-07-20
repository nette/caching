<?php declare(strict_types=1);

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

test('set ttl zero or negative deletes item', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$cache->set('test', '1');
	$cache->set('test', '2', 0);
	Assert::null($storage->read('test'));

	$cache->set('test', '1');
	$cache->set('test', '2', -1);
	Assert::null($storage->read('test'));

	$cache->set('test', '1');
	$interval = new DateInterval('PT1S');
	$interval->invert = 1;
	$cache->set('test', '2', $interval);
	Assert::null($storage->read('test'));
});

test('set ttl DateInterval', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);

	$interval = new DateInterval('P3Y6M4DT12H30M5S');
	$cache->set('test', '3', $interval);
	$dt = new DateTimeImmutable('', new DateTimeZone('UTC'));
	$expire = $dt->add($interval)->getTimestamp() - $dt->getTimestamp();
	Assert::same([
		'data' => '3',
		'dependencies' => [
			Caching\Cache::Expire => $expire,
		],
	], $storage->read('test'));
});
