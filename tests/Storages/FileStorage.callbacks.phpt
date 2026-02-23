<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage callbacks dependency.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()));


function dependency($val)
{
	return $val;
}


// Writing cache...
$cache->save($key, $value, [
	Cache::Callbacks => [['dependency', 1]],
]);

Assert::truthy($cache->load($key));


// Writing cache...
$cache->save($key, $value, [
	Cache::Callbacks => [['dependency', 0]],
]);

Assert::null($cache->load($key));
