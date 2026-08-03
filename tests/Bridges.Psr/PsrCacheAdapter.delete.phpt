<?php declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('delete', function () {
	$cache = new PsrCacheAdapter(new MemoryStorage);
	$cache->set('test', '1');
	Assert::true($cache->has('test'));

	Assert::true($cache->delete('test'));
	Assert::false($cache->has('test'));
	Assert::null($cache->get('test'));

	// deleting missing key is also true
	Assert::true($cache->delete('missing'));
});

test('deleteMultiple', function () {
	$cache = new PsrCacheAdapter(new MemoryStorage);
	$cache->set('a', 1);
	$cache->set('b', 2);
	$cache->set('c', 3);

	Assert::true($cache->deleteMultiple(['a', 'b', 'missing']));
	Assert::false($cache->has('a'));
	Assert::false($cache->has('b'));
	Assert::true($cache->has('c'));
});

test('clear', function () {
	$cache = new PsrCacheAdapter(new MemoryStorage);
	$cache->set('a', 1);
	$cache->set('b', 2);

	Assert::true($cache->clear());
	Assert::false($cache->has('a'));
	Assert::same('default', $cache->get('b', 'default'));
});
