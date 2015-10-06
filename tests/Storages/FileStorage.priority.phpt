<?php

/**
 * Test: Nette\Caching\Storages\FileStorage priority test.
 */

use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR, new SQLiteJournal(TEMP_DIR . '/journal.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('key1', 'value1', [
	Cache::PRIORITY => 100,
]);

$cache->save('key2', 'value2', [
	Cache::PRIORITY => 200,
]);

$cache->save('key3', 'value3', [
	Cache::PRIORITY => 300,
]);

$cache->save('key4', 'value4');


// Cleaning by priority...
$cache->clean([
	Cache::PRIORITY => '200',
]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::truthy($cache->load('key3'));
Assert::truthy($cache->load('key4'));
