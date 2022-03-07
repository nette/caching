<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage expiration test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new SQLiteStorage(':memory:'));


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
	Cache::Sliding => true,
]);


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);

	Assert::truthy($cache->load($key));
}

// Sleeping few seconds...
sleep(5);

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
	Cache::Sliding => true,
]);


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);

	Assert::truthy($cache->bulkLoad([$key])[$key]);
}

// Sleeping few seconds...
sleep(5);

Assert::null($cache->bulkLoad([$key])[$key]);
