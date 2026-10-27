<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage dependency on an item that is being generated.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createCache(): Cache
{
	static $counter = 0;
	$dir = getTempDir() . '/' . ++$counter;
	mkdir($dir);
	return new Cache(new FileStorage($dir));
}


// loads 'parent', whose generator caches 'child' that depends on 'parent'
function loadParent(Cache $cache, int &$calls, string $result = 'parent'): mixed
{
	return $cache->load('parent', function () use ($cache, &$calls, $result) {
		$cache->load('child', function (&$dependencies) use (&$calls) {
			$dependencies[Cache::Items] = ['parent'];
			$calls++;
			return 'child';
		});

		if ($result === 'throw') {
			throw new Exception;
		}

		return $result;
	});
}


test('dependent item stays cached with its parent', function () {
	$cache = createCache();
	$calls = 0;
	Assert::same('parent', loadParent($cache, $calls));
	Assert::same('child', $cache->load('child'));
	Assert::same('parent', loadParent($cache, $calls));
	Assert::same(1, $calls);
});


test('dependent item is invalidated by a change of its parent', function () {
	$cache = createCache();
	$calls = 0;
	loadParent($cache, $calls);
	$cache->save('parent', 'new');
	Assert::null($cache->load('child'));

	loadParent($cache, $calls);
	$cache->remove('parent');
	Assert::null($cache->load('child'));
});


test('dependent item does not survive a failed generator of its parent', function () {
	$cache = createCache();
	$calls = 0;
	Assert::exception(fn() => loadParent($cache, $calls, 'throw'), Exception::class);
	Assert::null($cache->load('child'));
});


test('dependent item is regenerated when its parent is', function () {
	$cache = createCache();
	$calls = 0;
	loadParent($cache, $calls);
	$cache->remove('parent');
	Assert::same('new', loadParent($cache, $calls, 'new'));
	Assert::same('child', $cache->load('child'));
	Assert::same(2, $calls);
});


test('items depending on each other', function () {
	$cache = createCache();
	$cache->load('a', function (&$dependencies) use ($cache) {
		$cache->load('b', function (&$dependencies) {
			$dependencies[Cache::Items] = ['a'];
			return 'b';
		});
		$dependencies[Cache::Items] = ['b'];
		return 'a';
	});

	Assert::same('a', $cache->load('a'));
	Assert::same('b', $cache->load('b'));

	$cache->save('a', 'new');
	Assert::null($cache->load('b'));
});
