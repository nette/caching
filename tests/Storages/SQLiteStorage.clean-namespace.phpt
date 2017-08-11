<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage clean with Cache::NAMESPACE
 */


declare(strict_types=1);

use Nette\Caching\{
	Cache, Storages\SQLiteStorage
};
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$storage = new SQLiteStorage(':memory:');

/*
 * Create SQLiteStorage cache without namespace and some with namespaces
 */
$cacheA = new Cache($storage);
$cacheB = new Cache($storage, 'B');
$cacheC = new Cache($storage, 'C');
$cacheD = new Cache($storage, 'D');

/*
 * Fill with data
 */
$cacheA->save('test1', 'David');
$cacheA->save('test2', 'Grudl');

$cacheB->save('test1', 'Barry');
$cacheB->save('test2', 'Allen');

$cacheC->save('test1', 'Oliver');
$cacheC->save('test2', 'Queen');

$cacheD->save('test1', 'Bruce');
$cacheD->save('test2', 'Wayne');


/*
 * Check if fill wass successfull
 */
Assert::same('David Grudl', implode(' ', [
	$cacheA->load('test1'),
	$cacheA->load('test2')
]));

Assert::same('Barry Allen', implode(' ', [
	$cacheB->load('test1'),
	$cacheB->load('test2')
]));

Assert::same('Oliver Queen', implode(' ', [
	$cacheC->load('test1'),
	$cacheC->load('test2')
]));

Assert::same('Bruce Wayne', implode(' ', [
	$cacheD->load('test1'),
	$cacheD->load('test2')
]));


/*
 * Clean one namespace
 */
$storage->clean([Cache::NAMESPACES => 'B']);

Assert::same('David Grudl', implode(' ', [
	$cacheA->load('test1'),
	$cacheA->load('test2')
]));

// Only these should be null now
Assert::null($cacheB->load('test1'));
Assert::null($cacheB->load('test2'));

Assert::same('Oliver Queen', implode(' ', [
	$cacheC->load('test1'),
	$cacheC->load('test2')
]));

Assert::same('Bruce Wayne', implode(' ', [
	$cacheD->load('test1'),
	$cacheD->load('test2')
]));


/*
 * Test cleaning multiple namespaces
 */
$storage->clean([Cache::NAMESPACES => ['C', 'D']]);

Assert::same('David Grudl', implode(' ', [
	$cacheA->load('test1'),
	$cacheA->load('test2')
]));

// All other should be null
Assert::null($cacheB->load('test1'));
Assert::null($cacheB->load('test2'));

Assert::null($cacheC->load('test1'));
Assert::null($cacheC->load('test2'));

Assert::null($cacheD->load('test1'));
Assert::null($cacheD->load('test2'));



