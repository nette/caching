<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage and bulk read.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new SQLiteStorage(':memory:'));
$cache->save('foo', 'bar');

Assert::same(['foo' => 'bar', 'lorem' => null], $cache->bulkLoad(['foo', 'lorem']));
