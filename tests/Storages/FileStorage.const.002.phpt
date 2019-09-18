<?php

/**
 * Test: Nette\Caching\Storages\FileStorage constant dependency test (continue...).
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$key = 'nette';
$value = 'rulez';


$cache = new Cache(new FileStorage(getTempDir()));


// Deleting dependent const

Assert::null($cache->load($key));
