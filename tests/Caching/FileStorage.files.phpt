<?php

/**
 * Test: Nette\Caching\Storages\FileStorage files dependency test.
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\FileStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(TEMP_DIR));


$dependentFile = TEMP_DIR . '/spec.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, [
	Cache::FILES => [
		__FILE__,
		$dependentFile,
	],
]);

Assert::truthy( $cache->load($key) );


// Modifing dependent file
file_put_contents($dependentFile, 'a');

Assert::null( $cache->load($key) );


// Writing cache...
$cache->save($key, $value, [
	Cache::FILES => $dependentFile,
]);

Assert::truthy( $cache->load($key) );


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::null( $cache->load($key) );
