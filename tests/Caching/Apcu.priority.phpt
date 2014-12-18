<?php

/**
 * Test: Nette\Caching\Storages\ApcuStorage priority test.
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
$cache->save('nette-priority-key1', 'value1', array(
	Cache::PRIORITY => 100,
));

$cache->save('nette-priority-key2', 'value2', array(
	Cache::PRIORITY => 200,
));

$cache->save('nette-priority-key3', 'value3', array(
	Cache::PRIORITY => 300,
));

$cache['nette-priority-key4'] = 'value4';


// Cleaning by priority...
$cache->clean(array(
	Cache::PRIORITY => '200',
));

Assert::false( isset($cache['nette-priority-key1']) );
Assert::false( isset($cache['nette-priority-key2']) );
Assert::true( isset($cache['nette-priority-key3']) );
Assert::true( isset($cache['nette-priority-key4']) );
