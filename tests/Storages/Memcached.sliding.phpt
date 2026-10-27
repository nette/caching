<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemcachedStorage sliding expiration test.
 */

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

$cache = new Cache(new MemcachedStorage);


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


// an item overwritten between the read and the extension of its expiration is not stored back
function overwrite(string $key): bool
{
	(new Cache(new MemcachedStorage))->save($key, 'new');
	return true;
}

$cache->save($key, 'old', [Cache::Expire => 100, Cache::Sliding => true, Cache::Callbacks => [['overwrite', $key]]]);
Assert::same('old', $cache->load($key)); // the callback overwrites the item during the read
Assert::same('new', $cache->load($key));

$cache->save($key, 'old', [Cache::Expire => 100, Cache::Sliding => true, Cache::Callbacks => [['overwrite', $key]]]);
Assert::same([$key => 'old'], $cache->bulkLoad([$key]));
Assert::same('new', $cache->load($key));
