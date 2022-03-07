<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage sliding expiration test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-sliding', getTempDir());


$key = 'nette-memcached-sliding-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage('localhost'));


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


// Bulk

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

Assert::null($cache->load([$key]));
