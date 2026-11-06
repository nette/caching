<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage generates a missing item under the lock.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\RedisStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';


/** Lets another process store the item on the given read of it. */
class RacingRedisStorage extends RedisStorage
{
	public int $reads = 0;
	public ?int $storeOnRead = null;


	public function read(string $key): mixed
	{
		if (++$this->reads === $this->storeOnRead) {
			new RedisStorage('127.0.0.1', prefix: 'nette-lock:')->write($key, 'other', []);
		}

		return parent::read($key);
	}
}


$storage = createRedisStorage('nette-lock:');
$redis = $storage->getConnection();
$lockKey = fn(string $key) => "nette-lock:\x01l:" . $key;


test('generates the missing item and releases the lock', function () use ($storage, $redis, $lockKey) {
	$calls = 0;
	$value = $storage->readOrGenerate('a', function () use ($storage, $redis, $lockKey, &$calls) {
		$calls++;
		Assert::true((bool) $redis->exists($lockKey('a')));
		$storage->write('a', 'generated', []);
		return 'generated';
	});

	Assert::same('generated', $value);
	Assert::same(1, $calls);
	Assert::same(0, $redis->exists($lockKey('a')));
	Assert::same('generated', $storage->read('a'));
});


test('item stored by another process before taking the lock is read, not generated again', function () {
	$storage = new RacingRedisStorage('127.0.0.1', prefix: 'nette-lock:');
	$storage->storeOnRead = 2; // the read right after taking the lock
	$value = $storage->readOrGenerate('b', fn() => 'generated');
	Assert::same('other', $value);
});


test('waits for the lock of another process and reads its result', function () use ($redis, $lockKey) {
	$redis->set($lockKey('c'), 'foreign', ['PX' => 5000]);
	$storage = new RacingRedisStorage('127.0.0.1', prefix: 'nette-lock:');
	$storage->storeOnRead = 3; // while polling
	$value = $storage->readOrGenerate('c', fn() => 'generated');
	Assert::same('other', $value);
	Assert::same('foreign', $redis->get($lockKey('c')));
	$redis->del($lockKey('c'));
});


test('generates the item when the lock of a crashed process expires', function () use ($redis, $lockKey) {
	$redis->set($lockKey('d'), 'crashed', ['PX' => 300]);
	$storage = new RedisStorage('127.0.0.1', prefix: 'nette-lock:', lockTimeout: 5);
	$start = microtime(true);
	$value = $storage->readOrGenerate('d', fn() => 'generated');
	Assert::same('generated', $value);
	Assert::true(microtime(true) - $start >= 0.25);
	Assert::same(0, $redis->exists($lockKey('d')));
});


test('stops waiting after the timeout and generates without the lock', function () use ($redis, $lockKey) {
	$redis->set($lockKey('e'), 'slow', ['PX' => 10000]);
	$storage = new RedisStorage('127.0.0.1', prefix: 'nette-lock:', lockTimeout: 1);
	$start = microtime(true);
	$value = $storage->readOrGenerate('e', fn() => 'generated');
	Assert::same('generated', $value);
	Assert::true(microtime(true) - $start >= 0.9);
	Assert::same('slow', $redis->get($lockKey('e')));
	$redis->del($lockKey('e'));
});


test('does not release a lock that expired and was taken over by another process', function () use ($storage, $redis, $lockKey) {
	$storage->readOrGenerate('f', function () use ($redis, $lockKey) {
		$redis->set($lockKey('f'), 'successor');
		return 'generated';
	});
	Assert::same('successor', $redis->get($lockKey('f')));
	$redis->del($lockKey('f'));
});


test('failed generator releases the lock', function () use ($storage, $redis, $lockKey) {
	Assert::exception(
		fn() => $storage->readOrGenerate('g', fn() => throw new RuntimeException('failed')),
		RuntimeException::class,
	);
	Assert::same(0, $redis->exists($lockKey('g')));
});


test('generator must not generate its own item', function () use ($storage) {
	$cache = new Cache($storage);
	Assert::exception(
		fn() => $cache->load('h', fn() => $cache->load('h', fn() => 'inner')),
		Nette\InvalidStateException::class,
		'Cache item is already being generated, its generator must not generate it again.',
	);
	Assert::same('value', $cache->load('h', fn() => 'value'));
});


test('generator must not generate its own item even without the lock', function () use ($redis, $lockKey) {
	$redis->set($lockKey('i'), 'slow', ['PX' => 10000]);
	$cache = new Cache(new RedisStorage('127.0.0.1', prefix: 'nette-lock:', lockTimeout: 1));
	Assert::exception(
		fn() => $cache->load('i', fn() => $cache->load('i', fn() => 'inner')),
		Nette\InvalidStateException::class,
	);
	$redis->del($lockKey('i'));
});


test('lock timeout must be positive', function () {
	Assert::exception(
		fn() => new RedisStorage('127.0.0.1', prefix: 'nette-lock:', lockTimeout: 0),
		Nette\InvalidArgumentException::class,
		'Lock timeout must be at least 1 second, 0 given.',
	);
});
