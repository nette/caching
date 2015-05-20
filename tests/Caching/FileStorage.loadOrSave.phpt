<?php

/**
 * Test: Nette\Caching\Storages\FileStorage & load or save.
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
$res = $cache->load($key, function(& $dp) use ($value) {
	$dp = [
		Cache::EXPIRATION => time() + 2,
	];
	return $value;
});

Assert::same( $res, $value );

Assert::same( $cache->load($key), $value );

// Sleeping 3 seconds
sleep(3);
clearstatcache();
Assert::null( $cache->load($key) );
