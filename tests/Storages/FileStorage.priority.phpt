<?php

/**
 * Test: Nette\Caching\Storages\FileStorage priority test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(getTempDir(), new SQLiteJournal(getTempDir() . '/journal.s3db'));
$cache = new Cache($storage);


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
