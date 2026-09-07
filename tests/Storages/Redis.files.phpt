<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage files dependency (via callbacks).
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$cache = new Cache(createRedisStorage('nette-files:'));

$dependentFile = getTempDir() . '/spec-Redis.files.txt';
@unlink($dependentFile); // @ file may not exist

$cache->save('key', 'value', [
	Cache::Files => $dependentFile,
]);

Assert::same('value', $cache->load('key'));

// dependent file was created
file_put_contents($dependentFile, 'a');
clearstatcache();

Assert::null($cache->load('key'));
