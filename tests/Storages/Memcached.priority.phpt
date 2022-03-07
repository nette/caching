<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage priority test.
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

Tester\Environment::lock('memcached-priority', getTempDir());


$storage = new MemcachedStorage('localhost', 11211, '', new SQLiteJournal(getTempDir() . '/journal-memcached.s3db'));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcached-priority-key1', 'value1', [
	Cache::Priority => 100,
]);

$cache->save('nette-memcached-priority-key2', 'value2', [
	Cache::Priority => 200,
]);

$cache->save('nette-memcached-priority-key3', 'value3', [
	Cache::Priority => 300,
]);

$cache->save('nette-memcached-priority-key4', 'value4');


// Cleaning by priority...
$cache->clean([
	Cache::Priority => '200',
]);

Assert::null($cache->load('nette-memcached-priority-key1'));
Assert::null($cache->load('nette-memcached-priority-key2'));
Assert::truthy($cache->load('nette-memcached-priority-key3'));
Assert::truthy($cache->load('nette-memcached-priority-key4'));
