<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\SQLiteStorage rejects unsupported dependencies.
 * @phpExtension pdo_sqlite
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new SQLiteStorage(':memory:'));


test('Files dependency is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Files => __FILE__]),
		Nette\NotSupportedException::class,
		'Dependencies Cache::Callbacks, Cache::Files and Cache::Constants are not supported by Nette\Caching\Storages\SQLiteStorage.',
	);
});

test('Priority dependency is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Priority => 100]),
		Nette\NotSupportedException::class,
		'Dependency Cache::Priority is not supported by Nette\Caching\Storages\SQLiteStorage.',
	);
});

test('Items dependency is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Items => ['another']]),
		Nette\NotSupportedException::class,
		'Dependency Cache::Items is not supported by Nette\Caching\Storages\SQLiteStorage.',
	);
});

test('cleaning by priority is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->clean([Cache::Priority => 100]),
		Nette\NotSupportedException::class,
		'Cleaning by Cache::Priority is not supported by Nette\Caching\Storages\SQLiteStorage.',
	);
});

test('supported dependencies do not throw', function () use ($cache) {
	Assert::noError(function () use ($cache) {
		$cache->save('key', 'value', [
			Cache::Expire => 100,
			Cache::Sliding => true,
			Cache::Tags => ['tag'],
		]);
		$cache->clean([Cache::Tags => ['tag']]);
		$cache->clean([Cache::All => true]);
	});
});
