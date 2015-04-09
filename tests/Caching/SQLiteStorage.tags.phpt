<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage tags dependency test.
 */

use Nette\Caching\Storages\SQLiteStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


$cache = new Cache(new SQLiteStorage);


// Writing cache...
$cache->save('key1', 'value1', array(
	Cache::TAGS => array('one', 'two'),
));

$cache->save('key2', 'value2', array(
	Cache::TAGS => array('one', 'three'),
));

$cache->save('key3', 'value3', array(
	Cache::TAGS => array('two', 'three'),
));

$cache->save('key4', 'value4');


// Cleaning by tags...
$cache->clean(array(
	Cache::TAGS => array('one', 'xx'),
));

Assert::null( $cache->load('key1') );
Assert::null( $cache->load('key2') );
Assert::truthy( $cache->load('key3') );
Assert::truthy( $cache->load('key4') );
