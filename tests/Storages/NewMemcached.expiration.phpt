<?php

/**
 * Test: Nette\Caching\Storages\NewMemcachedStorage expiration test.
 */

use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!NewMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-expiration', TEMP_DIR);


$key = 'nette-memcached-expiration-key';
$value = 'rulez';

$cache = new Cache(new NewMemcachedStorage('localhost'));


// Writing cache...
$cache->save($key, $value, [
	Cache::EXPIRATION => time() + 3,
]);


// Sleeping 1 second
sleep(1);
Assert::truthy($cache->load($key));


// Sleeping 3 seconds
sleep(3);
Assert::null($cache->load($key));
