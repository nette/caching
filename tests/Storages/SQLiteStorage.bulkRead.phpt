<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\SQLiteStorage and bulk read.
 * @phpExtension pdo_sqlite
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new SQLiteStorage(':memory:'));
$cache->save('foo', 'bar');

Assert::same(['foo' => 'bar', 'lorem' => null], $cache->bulkLoad(['foo', 'lorem']));
