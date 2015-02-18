<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage files dependency test.
 */

use Nette\Caching\Storages\MemcachedStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcache.');
}


$key = 'nette-files-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage('localhost'));


$dependentFile = TEMP_DIR . '/spec.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, array(
	Cache::FILES => array(
		__FILE__,
		$dependentFile,
	),
));

Assert::truthy( $cache->load($key) );


// Modifing dependent file
file_put_contents($dependentFile, 'a');

Assert::null( $cache->load($key) );


// Writing cache...
$cache->save($key, $value, array(
	Cache::FILES => $dependentFile,
));

Assert::truthy( $cache->load($key) );


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::null( $cache->load($key) );
