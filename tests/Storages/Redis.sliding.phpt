<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage sliding expiration.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\RedisStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$cache = new Cache(createRedisStorage('nette-sliding:'));


$cache->save('key', 'rulez', [
	Cache::Expire => time() + 3,
	Cache::Sliding => true,
]);

for ($i = 0; $i < 5; $i++) {
	sleep(1);
	Assert::same('rulez', $cache->load('key'));
}

sleep(5);
Assert::null($cache->load('key'));


// an item overwritten between the read and the extension of its expiration keeps its own expiration
function overwrite(string $key): bool
{
	(new RedisStorage('127.0.0.1', prefix: 'nette-sliding:'))->write($key, 'new', []);
	return true;
}

$storage = $cache->getStorage();
$dependencies = [Cache::Expire => 100, Cache::Sliding => true, Cache::Callbacks => [['overwrite', 'key']]];

$storage->write('key', 'old', $dependencies);
Assert::same('old', $storage->read('key')); // the callback overwrites the item during the read
Assert::same(-1, $storage->getConnection()->ttl('nette-sliding:key'));

$storage->write('key', 'old', $dependencies);
Assert::same(['key' => 'old'], $storage->bulkRead(['key']));
Assert::same(-1, $storage->getConnection()->ttl('nette-sliding:key'));
