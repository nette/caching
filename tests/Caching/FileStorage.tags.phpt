<?php

/**
 * Test: Nette\Caching\Storages\FileStorage tags dependency test.
 */

use Nette\Caching\Storages\FileStorage,
	Nette\Caching\Storages\FileJournal,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR, new FileJournal(TEMP_DIR));
$cache = new Cache($storage);


// Writing cache...
$cache->save('key1', 'value1', [
	Cache::TAGS => ['one', 'two'],
]);

$cache->save('key2', 'value2', [
	Cache::TAGS => ['one', 'three'],
]);

$cache->save('key3', 'value3', [
	Cache::TAGS => ['two', 'three'],
]);

$cache->save('key4', 'value4');


// Cleaning by tags...
$cache->clean([
	Cache::TAGS => 'one',
]);

Assert::null( $cache->load('key1') );
Assert::null( $cache->load('key2') );
Assert::truthy( $cache->load('key3') );
Assert::truthy( $cache->load('key4') );
