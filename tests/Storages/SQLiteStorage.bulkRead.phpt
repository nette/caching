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


// more keys than SQLite allows query parameters
$keys = array_map(fn($i) => "key$i", range(1, 40000));
$cache->save('key40000', 'value', [Cache::Expire => 100, Cache::Sliding => true]);
$result = $cache->bulkLoad($keys);
Assert::count(40000, $result);
Assert::same('value', $result['key40000']);
