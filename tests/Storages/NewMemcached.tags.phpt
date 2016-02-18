<?php

/**
 * Test: Nette\Caching\Storages\NewMemcachedStorage tags dependency test.
 */

use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Storages\FileJournal;
use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!NewMemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-tags', TEMP_DIR);


$storage = new NewMemcachedStorage('localhost', 11211, '', new FileJournal(TEMP_DIR));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-memcached-tags-key1', 'value1', array(
	Cache::TAGS => array('one', 'two'),
));

$cache->save('nette-memcached-tags-key2', 'value2', array(
	Cache::TAGS => array('one', 'three'),
));

$cache->save('nette-memcached-tags-key3', 'value3', array(
	Cache::TAGS => array('two', 'three'),
));

$cache->save('nette-memcached-tags-key4', 'value4');


// Cleaning by tags...
$cache->clean(array(
	Cache::TAGS => 'one',
));

Assert::null($cache->load('nette-memcached-tags-key1'));
Assert::null($cache->load('nette-memcached-tags-key2'));
Assert::truthy($cache->load('nette-memcached-tags-key3'));
Assert::truthy($cache->load('nette-memcached-tags-key4'));
