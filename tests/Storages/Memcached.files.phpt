<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage files dependency test.
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


$key = 'nette-memcached-files-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage('localhost'));


$dependentFile = getTempDir() . '/spec-memcached.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, [
	Cache::Files => [
		__FILE__,
		$dependentFile,
	],
]);

Assert::truthy($cache->load($key));


// Modifing dependent file
file_put_contents($dependentFile, 'a');

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Files => $dependentFile,
]);

Assert::truthy($cache->load($key));


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::null($cache->load($key));
