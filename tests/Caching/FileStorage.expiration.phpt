<?php

/**
 * Test: Nette\Caching\Storages\FileStorage expiration test.
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
	Cache::EXPIRATION => time() + 3,
]);


// Sleeping 1 second
sleep(1);
clearstatcache();
Assert::truthy( $cache->load($key) );


// Sleeping 3 seconds
sleep(3);
clearstatcache();
Assert::null( $cache->load($key) );
