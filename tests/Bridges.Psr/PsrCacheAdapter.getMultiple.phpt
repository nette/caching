<?php

declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('get multiple', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	$x = iterator_to_array($cache->getMultiple(['test', 'test1']));

	Assert::same([
		'test' => null,
		'test1' => null,
	], $x);
});

test('get multiple with default', function () {
	$cache = new PsrCacheAdapter(new DevNullStorage);
	$x = iterator_to_array($cache->getMultiple(['test', 'test1'], true));

	Assert::same([
		'test' => true,
		'test1' => true,
	], $x);
});
