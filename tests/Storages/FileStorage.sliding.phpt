<?php

/**
 * Test: Nette\Caching\Storages\FileStorage sliding expiration test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()));


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
	Cache::Sliding => true,
]);


for ($i = '0'; $i < '5'; $i++) {
	// Sleeping 1 second
	sleep(1);
	clearstatcache();

	Assert::truthy($cache->load($key));
}

// Sleeping few seconds...
sleep(5);
clearstatcache();

Assert::null($cache->load($key));
