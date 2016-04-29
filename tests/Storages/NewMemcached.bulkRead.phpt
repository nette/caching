<?php

/**
 * Test: Nette\Caching\Storages\NewMemcachedStorage and bulkRead
 */

use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!NewMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-files', TEMP_DIR);



$cache = new Cache(new NewMemcachedStorage('localhost'));

$cache->save('foo', 'bar');

Assert::same(['foo' => 'bar', 'lorem' => NULL], $cache->bulkLoad(['foo', 'lorem']));
