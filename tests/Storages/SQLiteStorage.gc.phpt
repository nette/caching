<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\SQLiteStorage garbage collector.
 * @phpExtension pdo_sqlite
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$file = getTempDir() . '/sqlite-gc.s3db';
$pdo = new PDO('sqlite:' . $file);
$countRows = fn() => (int) $pdo->query('SELECT COUNT(*) FROM cache')->fetchColumn();

SQLiteStorage::$gcProbability = 0;
$cache = new Cache(new SQLiteStorage($file));
$cache->save('expired', 'value', [Cache::Expire => 1]);
$cache->save('valid', 'value');

sleep(2);
new SQLiteStorage($file);
Assert::same(2, $countRows());

SQLiteStorage::$gcProbability = 1;
new SQLiteStorage($file);
Assert::same(1, $countRows());
Assert::same('value', $cache->load('valid'));
