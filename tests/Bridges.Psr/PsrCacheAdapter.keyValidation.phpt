<?php declare(strict_types=1);

use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cache = new PsrCacheAdapter(new DevNullStorage);


test('empty key is rejected', function () use ($cache) {
	Assert::exception(
		fn() => $cache->get(''),
		Nette\Bridges\Psr\InvalidArgumentException::class,
		'Cache key must be a non-empty string.',
	);
});

test('reserved characters are rejected in all methods', function () use ($cache) {
	foreach (str_split('{}()/\@:') as $char) {
		$key = 'a' . $char . 'b';
		$methods = [
			fn() => $cache->get($key),
			fn() => $cache->set($key, 1),
			fn() => $cache->delete($key),
			fn() => $cache->has($key),
			fn() => $cache->getMultiple([$key]),
			fn() => $cache->setMultiple([$key => 1]),
			fn() => $cache->deleteMultiple([$key]),
		];
		foreach ($methods as $method) {
			Assert::exception($method, Nette\Bridges\Psr\InvalidArgumentException::class);
		}
	}
});

test('exception implements PSR-16 interface', function () use ($cache) {
	Assert::exception(
		fn() => $cache->get('{'),
		Psr\SimpleCache\InvalidArgumentException::class,
	);
});

test('valid keys pass, integer array keys are tolerated', function () use ($cache) {
	Assert::noError(function () use ($cache) {
		$cache->set('AbC19_.', 1);
		$cache->setMultiple(['123' => 'x']); // PHP casts the key to int
		$cache->getMultiple(['AbC19_.', '123']);
	});
});
