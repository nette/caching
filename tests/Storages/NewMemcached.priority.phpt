<?php

/**
 * Test: Nette\Caching\Storages\NewMemcachedStorage priority test.
 */

use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Storages\FileJournal;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!NewMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-priority', TEMP_DIR);


$storage = new NewMemcachedStorage('localhost', 11211, '', new FileJournal(TEMP_DIR));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcached-priority-key1', 'value1', array(
	Cache::PRIORITY => 100,
));

$cache->save('nette-memcached-priority-key2', 'value2', array(
	Cache::PRIORITY => 200,
));

$cache->save('nette-memcached-priority-key3', 'value3', array(
	Cache::PRIORITY => 300,
));

$cache->save('nette-memcached-priority-key4', 'value4');


// Cleaning by priority...
$cache->clean(array(
	Cache::PRIORITY => '200',
));

Assert::null($cache->load('nette-memcached-priority-key1'));
Assert::null($cache->load('nette-memcached-priority-key2'));
Assert::truthy($cache->load('nette-memcached-priority-key3'));
Assert::truthy($cache->load('nette-memcached-priority-key4'));
