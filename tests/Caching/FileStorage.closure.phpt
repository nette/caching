<?php

/**
 * Test: Nette\Caching\Storages\FileStorage
 *
 * @author     David Grudl
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = '../' . implode('', range("\x00", "\x1F"));
$value = range("\x00", "\xFF");

$cache = new Cache(new FileStorage(TEMP_DIR));

Assert::false( isset($cache[$key]) );


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

Assert::false( isset($cache[$key]) );
