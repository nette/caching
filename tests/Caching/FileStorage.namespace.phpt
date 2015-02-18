<?php

/**
 * Test: Nette\Caching\Storages\FileStorage & namespace test.
 */

use Nette\Caching\Storages\FileStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR);
$cacheA = new Cache($storage, 'a');
$cacheB = new Cache($storage, 'b');


// Writing cache...
$cacheA->save('key', 'hello');
$cacheB->save('key', 'world');

Assert::same( $cacheA->load('key'), 'hello' );
Assert::same( $cacheB->load('key'), 'world' );


// Removing from cache #2 using remove()...
$cacheB->remove('key');

Assert::truthy( $cacheA->load('key') );
Assert::null( $cacheB->load('key') );
