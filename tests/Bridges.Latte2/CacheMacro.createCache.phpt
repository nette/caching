<?php

/**
 * Test: Nette\Bridges\CacheLatte\CacheMacro createCache()
 */

declare(strict_types=1);

use Nette\Bridges\CacheLatte\CacheMacro;
use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '>')) {
	Tester\Environment::skip('Test for Latte 2');
}


test('', function () {
	$parents = [];
	$dp = [Cache::Tags => ['rum', 'cola']];
	$outputHelper = CacheMacro::createCache(new DevNullStorage, 'test', $parents);
	Assert::type(Nette\Caching\OutputHelper::class, $outputHelper);
	CacheMacro::endCache($parents, $dp);
	Assert::same($dp + [Cache::Expire => '+ 7 days'], $outputHelper->dependencies);
});

test('', function () {
	$parents = [];
	$dp = [Cache::Tags => ['rum', 'cola']];
	$dpFallback = fn() => $dp;
	$outputHelper = CacheMacro::createCache(new DevNullStorage, 'test', $parents);
	CacheMacro::endCache($parents, ['dependencies' => $dpFallback]);
	Assert::same($dp + [Cache::Expire => '+ 7 days'], $outputHelper->dependencies);
});

test('', function () {
	$parents = [];
	$dp = [
		Cache::Tags => ['rum', 'cola'],
		Cache::Expire => '+ 1 days',
	];
	$dpFallback = fn() => $dp;
	$outputHelper = CacheMacro::createCache(new DevNullStorage, 'test', $parents);
	CacheMacro::endCache($parents, ['dependencies' => $dpFallback]);
	Assert::same($dp, $outputHelper->dependencies);
});
