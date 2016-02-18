<?php

/**
 * Test: Nette\Caching\Storages\PeclMemcachedStorage tags dependency test.
 */

use Nette\Caching\Storages\PeclMemcachedStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!PeclMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcache-tags', TEMP_DIR);


$storage = new PeclMemcachedStorage('localhost', 11211, '', new SQLiteJournal(TEMP_DIR . '/journal-memcached.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcached-tags-key1', 'value1', [
	Cache::TAGS => ['one', 'two'],
]);

$cache->save('nette-memcached-tags-key2', 'value2', [
	Cache::TAGS => ['one', 'three'],
]);

$cache->save('nette-memcached-tags-key3', 'value3', [
	Cache::TAGS => ['two', 'three'],
]);

$cache->save('nette-memcached-tags-key4', 'value4');


// Cleaning by tags...
$cache->clean([
	Cache::TAGS => 'one',
]);

Assert::null($cache->load('nette-memcached-tags-key1'));
Assert::null($cache->load('nette-memcached-tags-key2'));
Assert::truthy($cache->load('nette-memcached-tags-key3'));
Assert::truthy($cache->load('nette-memcached-tags-key4'));
