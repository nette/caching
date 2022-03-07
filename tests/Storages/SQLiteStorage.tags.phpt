<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage tags dependency test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new SQLiteStorage(':memory:'));


// Writing cache...
$cache->save('key1', 'value1', [
	Cache::Tags => ['one', 'two'],
]);

$cache->save('key2', 'value2', [
	Cache::Tags => ['one', 'three'],
]);

$cache->save('key3', 'value3', [
	Cache::Tags => ['two', 'three'],
]);

$cache->save('key4', 'value4');


// Cleaning by tags...
$cache->clean([
	Cache::Tags => ['one', 'xx'],
]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::truthy($cache->load('key3'));
Assert::truthy($cache->load('key4'));
