<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage expiration.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$cache = new Cache(createRedisStorage('nette-expiration:'));


$cache->save('key', 'rulez', [
	Cache::Expire => time() + 2,
]);

Assert::same('rulez', $cache->load('key'));

sleep(3);
Assert::null($cache->load('key'));
