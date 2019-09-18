<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage and bulkRead
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-files', getTempDir());



$cache = new Cache(new MemcachedStorage('localhost'));

$cache->save('foo', 'bar');

Assert::same(['foo' => 'bar', 'lorem' => null], $cache->bulkLoad(['foo', 'lorem']));
