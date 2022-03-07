<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage expiration test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-expiration', getTempDir());


$key = 'nette-memcached-expiration-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage('localhost'));


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
]);


// Sleeping 1 second
sleep(1);
Assert::truthy($cache->load($key));


// Sleeping 3 seconds
sleep(3);
Assert::null($cache->load($key));
