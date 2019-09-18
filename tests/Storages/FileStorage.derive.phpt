<?php

/**
 * Test: Nette\Caching\Storages\FileStorage derive test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new FileStorage(getTempDir()), 'ns1');
$cache = $cache->derive('ns2');

$cache->save($key, $value);
Assert::same($cache->load($key), $value);
