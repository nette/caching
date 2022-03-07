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
	$dpFallback = function () use ($dp) {
		return $dp;
	};
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
	$dpFallback = function () use ($dp) {
		return $dp;
	};
	$outputHelper = CacheMacro::createCache(new DevNullStorage, 'test', $parents);
	CacheMacro::endCache($parents, ['dependencies' => $dpFallback]);
	Assert::same($dp, $outputHelper->dependencies);
});
