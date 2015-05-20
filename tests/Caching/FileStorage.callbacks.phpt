<?php

/**
 * Test: Nette\Caching\Storages\FileStorage callbacks dependency.
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(TEMP_DIR));


function dependency($val)
{
	return $val;
}


// Writing cache...
$cache->save($key, $value, [
	Cache::CALLBACKS => [['dependency', 1]],
]);

Assert::truthy( $cache->load($key) );


// Writing cache...
$cache->save($key, $value, [
	Cache::CALLBACKS => [['dependency', 0]],
]);

Assert::null( $cache->load($key) );
