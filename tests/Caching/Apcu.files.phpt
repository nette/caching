<?php

/**
 * Test: Nette\Caching\Storages\ApcuStorage files dependency test.
 */

use Nette\Caching\Storages\ApcuStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!ApcuStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Apcu.');
}


$key = 'nette-files-key';
$value = 'rulez';

$cache = new Cache(new ApcuStorage());


$dependentFile = TEMP_DIR . '/spec.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, array(
	Cache::FILES => array(
		__FILE__,
		$dependentFile,
	),
));

Assert::true( isset($cache[$key]) );


// Modifing dependent file
file_put_contents($dependentFile, 'a');

Assert::false( isset($cache[$key]) );


// Writing cache...
$cache->save($key, $value, array(
	Cache::FILES => $dependentFile,
));

Assert::true( isset($cache[$key]) );


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::false( isset($cache[$key]) );
