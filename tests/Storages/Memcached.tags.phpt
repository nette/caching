<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage tags dependency test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-tags', getTempDir());


$storage = new MemcachedStorage('localhost', 11211, '', new SQLiteJournal(getTempDir() . '/journal-memcached.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcached-tags-key1', 'value1', [
	Cache::Tags => ['one', 'two'],
]);

$cache->save('nette-memcached-tags-key2', 'value2', [
	Cache::Tags => ['one', 'three'],
]);

$cache->save('nette-memcached-tags-key3', 'value3', [
	Cache::Tags => ['two', 'three'],
]);

$cache->save('nette-memcached-tags-key4', 'value4');


// Cleaning by tags...
$cache->clean([
	Cache::Tags => 'one',
]);

Assert::null($cache->load('nette-memcached-tags-key1'));
Assert::null($cache->load('nette-memcached-tags-key2'));
Assert::truthy($cache->load('nette-memcached-tags-key3'));
Assert::truthy($cache->load('nette-memcached-tags-key4'));
