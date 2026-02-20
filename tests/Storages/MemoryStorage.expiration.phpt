<?php

/**
 * Test: Nette\Caching\Storages\MemoryStorage expiration test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new MemoryStorage);


// Writing cache...
$cache->save($key, $value, [
	Cache::Expire => time() + 3,
]);


// Sleeping 1 second
sleep(1);
Assert::truthy($cache->load($key));


// Sleeping 3 seconds
sleep(3);
Assert::null($cache->load($key));
