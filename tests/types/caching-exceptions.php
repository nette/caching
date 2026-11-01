<?php declare(strict_types=1);

/**
 * PHPStan test: checked exceptions thrown by callbacks that Cache invokes immediately belong to the caller.
 */

use Nette\Caching\Cache;


function testLoadGeneratorThrows(Cache $cache): void
{
	$cache->load('key', function () {
		throw new Exception;
	});
}


/** @param list<string> $keys */
function testBulkLoadGeneratorThrows(Cache $cache, array $keys): void
{
	$cache->bulkLoad($keys, function () {
		throw new Exception;
	});
}


function testCallFunctionThrows(Cache $cache): void
{
	$cache->call(function () {
		throw new Exception;
	});
}


function testWrapFunctionIsInvokedLater(Cache $cache): void
{
	$cache->wrap(function () {
		throw new Exception;
	});
}
