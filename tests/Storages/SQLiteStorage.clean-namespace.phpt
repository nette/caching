<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage clean with Cache::NAMESPACE
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$storage = new SQLiteStorage(':memory:');

/*
 * Create SQLiteStorage cache without namespace and some with namespaces
 */
$cacheA = new Cache($storage);
$cacheB = new Cache($storage, 'C' . Cache::NAMESPACE_SEPARATOR . 'A');
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
Assert::same('David', $cacheA->load('test1'));
Assert::same('Grudl', $cacheA->load('test2'));

Assert::same('Barry', $cacheB->load('test1'));
Assert::same('Allen', $cacheB->load('test2'));

Assert::same('Oliver', $cacheC->load('test1'));
Assert::same('Queen', $cacheC->load('test2'));

Assert::same('Bruce', $cacheD->load('test1'));
Assert::same('Wayne', $cacheD->load('test2'));


/*
 * Clean one namespace
 */
$storage->clean([Cache::NAMESPACES => [$cacheB->getNamespace()]]);

Assert::same('David', $cacheA->load('test1'));
Assert::same('Grudl', $cacheA->load('test2'));

// Only these should be null now
Assert::null($cacheB->load('test1'));
Assert::null($cacheB->load('test2'));

Assert::same('Oliver', $cacheC->load('test1'));
Assert::same('Queen', $cacheC->load('test2'));

Assert::same('Bruce', $cacheD->load('test1'));
Assert::same('Wayne', $cacheD->load('test2'));


/*
 * Test cleaning multiple namespaces
 */
$storage->clean([Cache::NAMESPACES => [$cacheC->getNamespace(), $cacheD->getNamespace()]]);

Assert::same('David', $cacheA->load('test1'));
Assert::same('Grudl', $cacheA->load('test2'));

// All other should be null
Assert::null($cacheB->load('test1'));
Assert::null($cacheB->load('test2'));

Assert::null($cacheC->load('test1'));
Assert::null($cacheC->load('test2'));

Assert::null($cacheD->load('test1'));
Assert::null($cacheD->load('test2'));
