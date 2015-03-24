<?php

/**
 * Test: Nette\Bridges\CacheLatte\CacheMacro createCache()
 */

use Nette\Bridges\CacheLatte\CacheMacro,
	Tester\Assert,
	Nette\Caching\Storages\DevNullStorage,
	Nette\Caching\Cache;


require __DIR__ . '/../bootstrap.php';

test(function() {
	$parents = array();
	$dp = array(Cache::TAGS => array('rum', 'cola'));
	$outputHelper = CacheMacro::createCache(new DevNullStorage(), 'test', $parents, $dp);
	Assert::type('\Nette\Caching\OutputHelper', $outputHelper);
	Assert::same($dp + array('expire' => '+ 7 days'), $outputHelper->dependencies);
});

test(function() {
	$parents = array();
	$dp = array(Cache::TAGS => array('rum', 'cola'));
	//time consuming operation
	$dpFallback = function() use ($dp) {
		return $dp;
	};
	$outputHelper = CacheMacro::createCache(new DevNullStorage(), 'test', $parents, array('dependencies' => $dpFallback));
	Assert::same($dp + array('expire' => '+ 7 days'), $outputHelper->dependencies);
});
