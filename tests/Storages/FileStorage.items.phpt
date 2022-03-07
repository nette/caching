<?php

/**
 * Test: Nette\Caching\Storages\FileStorage items dependency test.
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
	Cache::Items => ['dependent'],
]);

Assert::truthy($cache->load($key));


// Modifing dependent cached item
$cache->save('dependent', 'hello world');

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Items => 'dependent',
]);

Assert::truthy($cache->load($key));


// Modifing dependent cached item
sleep(2);
$cache->save('dependent', 'hello europe');

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Items => 'dependent',
]);

Assert::truthy($cache->load($key));


// Deleting dependent cached item
$cache->save('dependent', null);

Assert::null($cache->load($key));
