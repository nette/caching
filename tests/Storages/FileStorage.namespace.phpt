<?php

/**
 * Test: Nette\Caching\Storages\FileStorage & namespace test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(getTempDir());
$cacheA = new Cache($storage, 'a');
$cacheB = new Cache($storage, 'b');


// Writing cache...
$cacheA->save('key', 'hello');
$cacheB->save('key', 'world');

Assert::same($cacheA->load('key'), 'hello');
Assert::same($cacheB->load('key'), 'world');


// Removing from cache #2 using remove()...
$cacheB->remove('key');

Assert::truthy($cacheA->load('key'));
Assert::null($cacheB->load('key'));
