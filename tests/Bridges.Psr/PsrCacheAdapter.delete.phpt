<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Caching/Cache.php';

test('delete', function () {
	$storage = new TestStorage();
	$cache = new PsrCacheAdapter($storage);
	$cache->set('test1', '1');
	$cache->set('test2', '2');

	$cache->delete('test1');

	Assert::null($storage->read('test1'));
	Assert::same([
		'data' => '2',
		'dependencies' => [],
	], $storage->read('test2'));
});
