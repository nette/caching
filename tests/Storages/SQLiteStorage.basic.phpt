<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage basic usage.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


// key and data with special chars
$key = [1, TRUE];
$value = range("\x00", "\xFF");

$cache = new Cache(new SQLiteStorage(':memory:'));

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value);

Assert::same($cache->load($key), $value);


// Removing from cache using remove()...
$cache->remove($key);

Assert::null($cache->load($key));


// Removing from cache using set NULL...
$cache->save($key, $value);
$cache->save($key, NULL);

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value);

Assert::same($cache->load($key), $value);
