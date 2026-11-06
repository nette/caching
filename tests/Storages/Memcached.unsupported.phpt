<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemcachedStorage rejects unsupported dependencies.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}


$cache = new Cache(new MemcachedStorage);


test('Items dependency is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Items => ['another']]),
		Nette\NotSupportedException::class,
		'Dependency Cache::Items is not supported by Nette\Caching\Storages\MemcachedStorage.',
	);
	Assert::exception(
		fn() => $cache->bulkSave(['key' => 'value'], [Cache::Items => ['another']]),
		Nette\NotSupportedException::class,
		'Dependency Cache::Items is not supported by Nette\Caching\Storages\MemcachedStorage.',
	);
});

test('cleaning by namespaces is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->clean([Cache::Namespaces => ['ns']]),
		Nette\NotSupportedException::class,
		'Cleaning by Cache::Namespaces is not supported by Nette\Caching\Storages\MemcachedStorage.',
	);
	Assert::exception(
		fn() => $cache->derive('ns')->clean(all: true),
		Nette\NotSupportedException::class,
		'Cleaning by Cache::Namespaces is not supported by Nette\Caching\Storages\MemcachedStorage.',
	);
});
