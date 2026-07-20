<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemcachedStorage expiration test.
 */

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

$cache = new Cache(new MemcachedStorage);


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


// non-positive expiration removes the item (zero would mean no expiration in memcached)
$storage = $cache->getStorage();
$storage->write($key, $value, []);
$storage->write($key, $value, [Cache::Expire => 0]);
Assert::null($storage->read($key));

$storage->write($key, $value, []);
$storage->bulkWrite([$key => $value], [Cache::Expire => -1]);
Assert::null($storage->read($key));
