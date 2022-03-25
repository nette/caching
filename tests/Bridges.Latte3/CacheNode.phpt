<?php

declare(strict_types=1);

use Nette\Bridges\CacheLatte\Nodes\CacheNode;
use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '<')) {
	Tester\Environment::skip('Test for Latte 3');
}


test('', function () {
	$parents = [];
	$dp = [Cache::Tags => ['rum', 'cola']];
	$outputHelper = CacheNode::createCache(new DevNullStorage, 'test', $parents, $dp);
	Assert::type(Nette\Caching\OutputHelper::class, $outputHelper);
	CacheNode::endCache($parents);
	Assert::same($dp + [Cache::Expire => '+ 7 days'], $outputHelper->dependencies);
});

test('', function () {
	$parents = [];
	$dp = [Cache::Tags => ['rum', 'cola']];
	$dpFallback = function () use ($dp) {
		return $dp;
	};
	$outputHelper = CacheNode::createCache(new DevNullStorage, 'test', $parents, ['dependencies' => $dpFallback]);
	CacheNode::endCache($parents);
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
	$outputHelper = CacheNode::createCache(new DevNullStorage, 'test', $parents, ['dependencies' => $dpFallback]);
	CacheNode::endCache($parents);
	Assert::same($dp, $outputHelper->dependencies);
});
