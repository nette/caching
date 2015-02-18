<?php

/**
 * Test: Nette\Caching\Storages\FileStorage
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = '../' . implode('', range("\x00", "\x1F"));
$value = range("\x00", "\xFF");

$cache = new Cache(new FileStorage(TEMP_DIR));

Assert::null( $cache->load($key) );


// Writing cache using Closure...
$res = $cache->save($key, function() use ($value) {
	return $value;
});

Assert::same( $res, $value );

Assert::same( $cache->load($key), $value );


// Removing from cache using NULL callback...
$cache->save($key, function() {
	return NULL;
});

Assert::null( $cache->load($key) );
