<?php

/**
 * Test: Nette\Caching\Storages\PeclMemcachedStorage files dependency test.
 */

use Nette\Caching\Storages\PeclMemcachedStorage;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!PeclMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcache-files', TEMP_DIR);


$key = 'nette-memcached-files-key';
$value = 'rulez';

$cache = new Cache(new PeclMemcachedStorage('localhost'));


$dependentFile = TEMP_DIR . '/spec-memcached.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, [
	Cache::FILES => [
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
	Cache::FILES => $dependentFile,
]);

Assert::truthy($cache->load($key));


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::null($cache->load($key));
