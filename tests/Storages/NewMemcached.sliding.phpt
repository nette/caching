<?php

/**
 * Test: Nette\Caching\Storages\NewMemcachedStorage sliding expiration test.
 */

use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!NewMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-sliding', TEMP_DIR);


$key = 'nette-memcached-sliding-key';
$value = 'rulez';

$cache = new Cache(new NewMemcachedStorage('localhost'));


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
