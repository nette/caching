<?php

/**
 * Test: Nette\Caching\Storages\FileStorage clean with Cache::ALL
 */

use Nette\Caching\Storages\FileStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR);
$cacheA = new Cache($storage);
$cacheB = new Cache($storage,'B');

$cacheA->save('test1', 'David');
$cacheA->save('test2', 'Grudl');
$cacheB->save('test1', 'divaD');
$cacheB->save('test2', 'ldurG');

Assert::same( 'David Grudl divaD ldurG', implode(' ',[
	$cacheA->load('test1'),
	$cacheA->load('test2'),
	$cacheB->load('test1'),
	$cacheB->load('test2'),
]));

$storage->clean([Cache::ALL => TRUE]);

Assert::null( $cacheA->load('test1') );
Assert::null( $cacheA->load('test2') );
Assert::null( $cacheB->load('test1') );
Assert::null( $cacheB->load('test2') );
