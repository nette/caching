<?php

/**
 * Test: Nette\Caching\Storages\ApcuStorage tags dependency test.
 */

use Nette\Caching\Storages\ApcuStorage,
	Nette\Caching\Storages\FileJournal,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!ApcuStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Apcu.');
}


$storage = new ApcuStorage('', new FileJournal(TEMP_DIR));
$cache = new Cache($storage);


// Writing cache...
$cache->save('nette-tags-key1', 'value1', array(
	Cache::TAGS => array('one', 'two'),
));

$cache->save('nette-tags-key2', 'value2', array(
	Cache::TAGS => array('one', 'three'),
));

$cache->save('nette-tags-key3', 'value3', array(
	Cache::TAGS => array('two', 'three'),
));

$cache['nette-tags-key4'] = 'value4';


// Cleaning by tags...
$cache->clean(array(
	Cache::TAGS => 'one',
));

Assert::false( isset($cache['nette-tags-key1']) );
Assert::false( isset($cache['nette-tags-key2']) );
Assert::true( isset($cache['nette-tags-key3']) );
Assert::true( isset($cache['nette-tags-key4']) );
