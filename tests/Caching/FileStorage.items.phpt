<?php

/**
 * Test: Nette\Caching\Storages\FileStorage items dependency test.
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(TEMP_DIR));


// Writing cache...
$cache->save($key, $value, [
	Cache::ITEMS => ['dependent'],
]);

Assert::truthy( $cache->load($key) );


// Modifing dependent cached item
$cache->save('dependent', 'hello world');

Assert::null( $cache->load($key) );


// Writing cache...
$cache->save($key, $value, [
	Cache::ITEMS => 'dependent',
]);

Assert::truthy( $cache->load($key) );


// Modifing dependent cached item
sleep(2);
$cache->save('dependent', 'hello europe');

Assert::null( $cache->load($key) );


// Writing cache...
$cache->save($key, $value, [
	Cache::ITEMS => 'dependent',
]);

Assert::truthy( $cache->load($key) );


// Deleting dependent cached item
$cache->save('dependent', NULL);

Assert::null( $cache->load($key) );
