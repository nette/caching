<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage tags dependency test.
 */

use Nette\Caching\Storages\MemcachedStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcache.');
}

Tester\Environment::lock('memcached-tags', TEMP_DIR);


$storage = new MemcachedStorage('localhost', 11211, '', new SQLiteJournal(TEMP_DIR . '/journal-memcache.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcache-tags-key1', 'value1', [
	Cache::TAGS => ['one', 'two'],
]);

$cache->save('nette-memcache-tags-key2', 'value2', [
	Cache::TAGS => ['one', 'three'],
]);

$cache->save('nette-memcache-tags-key3', 'value3', [
	Cache::TAGS => ['two', 'three'],
]);

$cache->save('nette-memcache-tags-key4', 'value4');


// Cleaning by tags...
$cache->clean([
	Cache::TAGS => 'one',
]);

Assert::null($cache->load('nette-memcache-tags-key1'));
Assert::null($cache->load('nette-memcache-tags-key2'));
Assert::truthy($cache->load('nette-memcache-tags-key3'));
Assert::truthy($cache->load('nette-memcache-tags-key4'));
