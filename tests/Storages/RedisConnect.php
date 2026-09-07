<?php declare(strict_types=1);

/**
 * Test helper: creates connected RedisStorage or skips the test.
 */

use Nette\Caching\Storages\RedisStorage;


function createRedisStorage(string $prefix): RedisStorage
{
	if (!RedisStorage::isAvailable()) {
		Tester\Environment::skip('Requires PHP extension redis.');
	}

	try {
		$storage = new RedisStorage('127.0.0.1', prefix: $prefix);
	} catch (Nette\InvalidStateException) {
		Tester\Environment::skip('Redis server is not running on 127.0.0.1.');
	}

	$storage->clean([Nette\Caching\Cache::All => true]); // removes leftovers from previous runs, deletes only keys with the prefix
	return $storage;
}
