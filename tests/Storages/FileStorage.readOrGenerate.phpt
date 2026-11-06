<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage generates a missing item under the lock.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/** Lets another writer store the item right after the storage finds it missing, before it takes the lock. */
class RacingStorage extends FileStorage
{
	public ?Closure $onMiss = null;


	protected function readMetaAndLock(string $file, int $lock): ?array
	{
		$meta = parent::readMetaAndLock($file, $lock);
		if (!$meta && $this->onMiss) {
			$onMiss = $this->onMiss;
			$this->onMiss = null;
			$onMiss();
		}

		return $meta;
	}
}


function cacheFiles(string $dir): array
{
	return glob($dir . '/_*') ?: [];
}


test('item stored by another process while waiting for the lock is read, not generated again', function () {
	$dir = getTempDir() . '/racing';
	@mkdir($dir); // @ directory may already exist
	$storage = new RacingStorage($dir);
	$storage->onMiss = fn() => new Cache(new FileStorage($dir))->save('key', 'other');

	$value = new Cache($storage)->load('key', fn() => 'generated');
	Assert::same('other', $value);
	Assert::same('other', new Cache(new FileStorage($dir))->load('key'));
});


test('failed generator releases the lock and leaves no file behind', function () {
	$dir = getTempDir() . '/failing';
	@mkdir($dir); // @ directory may already exist
	$cache = new Cache(new FileStorage($dir));

	Assert::exception(
		fn() => $cache->load('key', fn() => throw new RuntimeException('failed')),
		RuntimeException::class,
		'failed',
	);
	Assert::same([], cacheFiles($dir));
	Assert::same('value', $cache->load('key', fn() => 'value'));
});


test('missing journal fails before the item file is created', function () {
	$dir = getTempDir() . '/no-journal';
	@mkdir($dir); // @ directory may already exist
	$cache = new Cache(new FileStorage($dir));

	Assert::exception(
		fn() => $cache->load('key', function (&$dependencies) {
			$dependencies[Cache::Tags] = ['tag'];
			return 'value';
		}),
		Nette\InvalidStateException::class,
		'CacheJournal has not been provided.',
	);
	Assert::same([], cacheFiles($dir));

	Assert::exception(
		fn() => $cache->save('key', 'value', [Cache::Tags => ['tag']]),
		Nette\InvalidStateException::class,
	);
	Assert::same([], cacheFiles($dir));
});


test('generator returning null stores nothing and releases the lock', function () {
	$dir = getTempDir() . '/null';
	@mkdir($dir); // @ directory may already exist
	$cache = new Cache(new FileStorage($dir));

	Assert::null($cache->load('key', fn() => null));
	Assert::same([], cacheFiles($dir));
	Assert::same('value', $cache->load('key', fn() => 'value'));
});


test('generator must not generate its own item', function () {
	$dir = getTempDir() . '/recursive';
	@mkdir($dir); // @ directory may already exist
	$cache = new Cache(new FileStorage($dir));

	Assert::exception(
		fn() => $cache->load('key', fn() => $cache->load('key', fn() => 'inner')),
		Nette\InvalidStateException::class,
		'Cache item is already being generated, its generator must not generate it again.',
	);
	Assert::same('value', $cache->load('key', fn() => 'value'));
});


test('generator reading its own item gets a miss instead of waiting forever', function () {
	$dir = getTempDir() . '/self-read';
	@mkdir($dir); // @ directory may already exist
	$cache = new Cache(new FileStorage($dir));

	$value = $cache->load('key', fn() => $cache->load('key') ?? 'value');
	Assert::same('value', $value);
	Assert::same('value', $cache->load('key'));
});
