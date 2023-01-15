<?php

declare(strict_types=1);

use Nette\Bridges\CacheLatte\Runtime;
use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '<')) {
	Tester\Environment::skip('Test for Latte 3');
}


test('', function () {
	$runtime = new Runtime(new DevNullStorage);
	$dp = [Cache::Tags => ['rum', 'cola']];
	Assert::true($runtime->createCache('test', $dp));
	$stack = Assert::with($runtime, fn() => $this->stack);
	$runtime->end();
	Assert::same($dp + [Cache::Expire => '+ 7 days'], $stack[0]->dependencies);
});

test('', function () {
	$runtime = new Runtime(new DevNullStorage);
	$dp = [Cache::Tags => ['rum', 'cola']];
	$dpFallback = fn() => $dp;
	Assert::true($runtime->createCache('test', ['dependencies' => $dpFallback]));
	$stack = Assert::with($runtime, fn() => $this->stack);
	$runtime->end();
	Assert::same($dp + [Cache::Expire => '+ 7 days'], $stack[0]->dependencies);
});

test('', function () {
	$runtime = new Runtime(new DevNullStorage);
	$dp = [
		Cache::Tags => ['rum', 'cola'],
		Cache::Expire => '+ 1 days',
	];
	$dpFallback = fn() => $dp;
	Assert::true($runtime->createCache('test', ['dependencies' => $dpFallback]));
	$stack = Assert::with($runtime, fn() => $this->stack);
	$runtime->end();
	Assert::same($dp, $stack[0]->dependencies);
});
