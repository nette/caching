<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage expiration test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new SQLiteStorage(':memory:'));


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
]);


// Sleeping 1 second
sleep(1);
clearstatcache();
Assert::truthy($cache->load($key));


// Sleeping 3 seconds
sleep(3);
clearstatcache();
Assert::null($cache->load($key));
