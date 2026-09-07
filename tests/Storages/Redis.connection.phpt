<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage connection and prefix.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\RedisStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$redis = createRedisStorage('nette-connection:')->getConnection();


test('keys are prefixed by default and clean(All) keeps foreign keys', function () use ($redis) {
	$redis->set('nette-connection-foreign', 'value');
	$storage = new RedisStorage($redis);
	$storage->write('key', 'value', []);
	Assert::true((bool) $redis->exists('nette.cache:key'));

	$storage->clean([Cache::All => true]);
	Assert::false((bool) $redis->exists('nette.cache:key'));
	Assert::same('value', $redis->get('nette-connection-foreign'));
	$redis->del('nette-connection-foreign');
});


test('connection with a prefix is rejected', function () {
	$redis = new Redis;
	$redis->connect('127.0.0.1');
	$redis->setOption(Redis::OPT_PREFIX, 'app:');
	Assert::exception(
		fn() => new RedisStorage($redis),
		Nette\InvalidArgumentException::class,
		'Redis connection must not use a prefix (Redis::OPT_PREFIX), use the parameter $prefix instead.',
	);
});
