<?php

/**
 * Test: Nette\Caching\Storages\FileStorage tags dependency test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(TEMP_DIR, new SQLiteJournal(TEMP_DIR . '/journal.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('key1', 'value1', [
	Cache::TAGS => ['one', 'two'],
]);

$cache->save('key2', 'value2', [
	Cache::TAGS => ['one', 'three'],
]);

$cache->save('key3', 'value3', [
	Cache::TAGS => ['foo' => 'one', 'bar' => 'two'],
]);

$cache->save('key4', 'value4', [
	Cache::TAGS => 'one',
]);

$cache->save('key5', 'value5', [
	Cache::TAGS => ['two', 'three'],
]);

$cache->save('key6', 'value6', [
	Cache::TAGS => ['foo' => 'two', 'bar' => 'three'],
]);

$cache->save('key7', 'value7', [
	Cache::TAGS => 'two',
]);

$cache->save('key8', 'value8');


// Cleaning by tags...
$cache->clean([
	Cache::TAGS => [
		0 => 'non-existent1',
		1 => 'non-existent2',
		3 => 'one',
		5 => 'non-existent3'
	]
]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::null($cache->load('key3'));
Assert::null($cache->load('key4'));
Assert::truthy($cache->load('key5'));
Assert::truthy($cache->load('key6'));
Assert::truthy($cache->load('key7'));
Assert::truthy($cache->load('key8'));
