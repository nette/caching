<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Caching/Cache.php';


test('has', function () {
	$storage = new TestStorage;
	$cache = new PsrCacheAdapter($storage);
	$cache->set('test1', '1');

	Assert::true($cache->has('test1'));
	Assert::false($cache->has('test2'));
});
