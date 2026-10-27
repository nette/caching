<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemcachedStorage reports a failed write.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}


$cache = new Cache(new MemcachedStorage);

Assert::error(
	fn() => $cache->save('nette-memcached-too-big', random_bytes(2 * 1024 * 1024)), // over the default item size limit of 1 MB
	E_USER_WARNING,
	'Memcached::set() failed: ITEM TOO BIG.',
);
Assert::null($cache->load('nette-memcached-too-big'));
