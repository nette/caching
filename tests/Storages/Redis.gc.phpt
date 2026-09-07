<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage garbage collector of the indexes.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$storage = createRedisStorage('nette-gc:');
$redis = $storage->getConnection();
$cache = new Cache($storage);

for ($i = 0; $i < 10; $i++) {
	$cache->save("expired$i", 'value', [Cache::Expire => 1, Cache::Tags => ["tag$i", 'shared'], Cache::Priority => 10]);
}

$cache->save('valid', 'value', [Cache::Tags => ['shared'], Cache::Priority => 10]);
sleep(2);


test('garbage collector removes expired items from the indexes', function () use ($storage, $redis) {
	Assert::count(11, $redis->keys("nette-gc:\x01t:*"));
	Assert::count(11, $redis->keys("nette-gc:\x01k:*"));
	Assert::same(11, $redis->zCard("nette-gc:\x01priority"));

	$storage->clean([]);

	Assert::same(["nette-gc:\x01t:shared"], $redis->keys("nette-gc:\x01t:*"));
	Assert::same(1, $redis->scard("nette-gc:\x01t:shared"));
	Assert::count(1, $redis->keys("nette-gc:\x01k:*"));
	Assert::same(1, $redis->zCard("nette-gc:\x01priority"));
});


test('items alive are still cleaned by tag and priority', function () use ($cache) {
	Assert::same('value', $cache->load('valid'));
	$cache->clean([Cache::Tags => ['shared']]);
	Assert::null($cache->load('valid'));

	$cache->save('valid', 'value', [Cache::Priority => 10]);
	$cache->clean([Cache::Priority => 10]);
	Assert::null($cache->load('valid'));
});
