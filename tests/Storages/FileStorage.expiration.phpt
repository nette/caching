<?php

/**
 * Test: Nette\Caching\Storages\FileStorage expiration test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()));


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
