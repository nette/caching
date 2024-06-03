<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('get', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	Assert::null($cache->get('test'));
});

test('get with default', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	Assert::true($cache->get('test', true));
});
