<?php

/**
 * Test: Nette\Caching\Storages\MemoryStorage priority test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new MemoryStorage);


// Writing cache...
$cache->save('key1', 'value1', [
	Cache::Priority => 100,
]);

$cache->save('key2', 'value2', [
	Cache::Priority => 200,
]);

$cache->save('key3', 'value3', [
	Cache::Priority => 300,
]);

$cache->save('key4', 'value4');


// Cleaning by priority...
$cache->clean([
	Cache::Priority => '200',
]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::truthy($cache->load('key3'));
Assert::truthy($cache->load('key4'));
