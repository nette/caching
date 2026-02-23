<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage constant dependency test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()));


define('ANY_CONST', 10);


// Writing cache...
$cache->save($key, $value, [
	Cache::Constants => 'ANY_CONST',
]);

Assert::truthy($cache->load($key));
