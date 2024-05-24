<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Caching/Cache.php';

test('set ttl unlimited', function () {
	$storage = new TestStorage();
	$cache = new PsrCacheAdapter($storage);
	$cache->set('test', '1');
	Assert::same([
		'data' => '1',
		'dependencies' => [],
	], $storage->read('test'));
});

test('set ttl int', function () {
	$storage = new TestStorage();
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
	$storage = new TestStorage();
	$cache = new PsrCacheAdapter($storage);

	$cache->set('test', '3', new DateInterval('P3Y6M4DT12H30M5S'));
	Assert::same([
		'data' => '3',
		'dependencies' => [
			Caching\Cache::Expire => 110_899_805,
		],
	], $storage->read('test'));

	$cache->set('test', '4', (new DateTime('1978-01-23 05:06:07'))->diff(new DateTime('1986-12-30 07:08:09')));
	Assert::same([
		'data' => '4',
		'dependencies' => [
			Caching\Cache::Expire => 282_016_922,
		],
	], $storage->read('test'));

	$cache->set('test', '5', (new DateTime('1986-12-30 07:08:09'))->diff(new DateTime('1978-01-23 05:06:07')));
	Assert::same([
		'data' => '5',
		'dependencies' => [
			Caching\Cache::Expire => -282_016_922,
		],
	], $storage->read('test'));
});
