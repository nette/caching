<?php

/**
 * Test: Nette\Caching\Storages\FileStorage files dependency test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()));


$dependentFile = getTempDir() . '/spec.file';
@unlink($dependentFile);

// Writing cache...
$cache->save($key, $value, [
	Cache::Files => [
		__FILE__,
		$dependentFile,
	],
]);

Assert::truthy($cache->load($key));


// Modifing dependent file
file_put_contents($dependentFile, 'a');

Assert::null($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Files => $dependentFile,
]);

Assert::truthy($cache->load($key));


// Modifing dependent file
sleep(2);
file_put_contents($dependentFile, 'b');
clearstatcache();

Assert::null($cache->load($key));
