<?php

/**
 * Test: Nette\Caching\Storages\FileStorage
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = '../' . implode('', range("\x00", "\x1F"));
$value = range("\x00", "\xFF");

$cache = new Cache(new FileStorage(getTempDir()));

Assert::null($cache->load($key));


// Writing cache using Closure...
$res = $cache->save($key, function () use ($value) {
	return $value;
});

Assert::same($res, $value);

Assert::same($cache->load($key), $value);


// Removing from cache using null callback...
$cache->save($key, function () {
	return null;
});

Assert::null($cache->load($key));
