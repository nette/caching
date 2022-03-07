<?php

/**
 * Test: Nette\Caching\Storages\FileStorage tags dependency test.
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
	Cache::Tags => ['one', 'two'],
]);

$cache->save('key2', 'value2', [
	Cache::Tags => ['one', 'three'],
]);

$cache->save('key3', 'value3', [
	Cache::Tags => ['foo' => 'one', 'bar' => 'two'],
]);

$cache->save('key4', 'value4', [
	Cache::Tags => 'one',
]);

$cache->save('key5', 'value5', [
	Cache::Tags => ['two', 'three'],
]);

$cache->save('key6', 'value6', [
	Cache::Tags => ['foo' => 'two', 'bar' => 'three'],
]);

$cache->save('key7', 'value7', [
	Cache::Tags => 'two',
]);

$cache->save('key8', 'value8');


// Cleaning by tags...
$cache->clean([
	Cache::Tags => [
		0 => 'non-existent1',
		1 => 'non-existent2',
		3 => 'one',
		5 => 'non-existent3',
	],
]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::null($cache->load('key3'));
Assert::null($cache->load('key4'));
Assert::truthy($cache->load('key5'));
Assert::truthy($cache->load('key6'));
Assert::truthy($cache->load('key7'));
Assert::truthy($cache->load('key8'));
