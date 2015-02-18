<?php

/**
 * Test: Nette\Caching\Storages\FileStorage int keys.
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = 0;
$value = range("\x00", "\xFF");

$cache = new Cache(new FileStorage(TEMP_DIR));

Assert::null( $cache->load($key) );


// Writing cache...
$cache->save($key, $value);

Assert::same( $cache->load($key), $value );


// Removing from cache using remove()...
$cache->remove($key);

Assert::null( $cache->load($key) );


// Removing from cache using set NULL...
$cache->save($key, $value);
$cache->save($key, NULL);

Assert::null( $cache->load($key) );
