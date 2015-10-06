<?php

/**
 * Test: Nette\Caching\Storages\FileStorage sliding expiration test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(TEMP_DIR));


// Writing cache...
$cache->save($key, $value, [
	Cache::EXPIRATION => time() + 3,
	Cache::SLIDING => TRUE,
]);


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);
	clearstatcache();

	Assert::truthy($cache->load($key));

}

// Sleeping few seconds...
sleep(5);
clearstatcache();

Assert::null($cache->load($key));
