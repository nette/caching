<?php

/**
 * Test: Nette\Caching\Storages\FileStorage clean with Cache::NAMESPACE
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR);
$cacheA = new Cache($storage);
$cacheB = new Cache($storage, 'B');
$cacheC = new Cache($storage, 'C');

$cacheA->save('test1', 'David');
$cacheA->save('test2', 'Grudl');
$cacheB->save('test1', 'divaD');
$cacheB->save('test2', 'ldurG');
$cacheC->save('test1', 'Forst');
$cacheC->save('test2', 'tsroF');

Assert::same('David Grudl', implode(' ', [
	$cacheA->load('test1'),
	$cacheA->load('test2'),
]));

Assert::same('divaD ldurG', implode(' ', [
	$cacheB->load('test1'),
	$cacheB->load('test2'),
]));

Assert::same('Forst tsroF', implode(' ', [
	$cacheC->load('test1'),
	$cacheC->load('test2'),
]));


$storage->clean([Cache::NAMESPACE => 'C']);


Assert::same('David Grudl', implode(' ', [
	$cacheA->load('test1'),
	$cacheA->load('test2'),
]));

Assert::same('divaD ldurG', implode(' ', [
	$cacheB->load('test1'),
	$cacheB->load('test2'),
]));


Assert::null($cacheC->load('test1'));
Assert::null($cacheC->load('test2'));
