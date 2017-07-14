<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage basic usage.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = [1, true];
$value = range("\x00", "\xFF");

$cache = new Cache(new SQLiteStorage(':memory:'));

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value);

Assert::same($cache->load($key), $value);


// Removing from cache using remove()...
$cache->remove($key);

Assert::null($cache->load($key));


// Removing from cache using set null...
$cache->save($key, $value);
$cache->save($key, null);

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value);

Assert::same($cache->load($key), $value);
