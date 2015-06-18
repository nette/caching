<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage expiration test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new SQLiteStorage);


// Writing cache...
$cache->save($key, $value, [
	Cache::EXPIRATION => time() + 3,
	Cache::SLIDING => TRUE,
]);


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);

	Assert::truthy($cache->load($key));
}

// Sleeping few seconds...
sleep(5);

Assert::null($cache->load($key));
