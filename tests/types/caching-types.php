<?php declare(strict_types=1);

/**
 * PHPStan type tests for Caching.
 */

use Nette\Caching\Cache;
use function PHPStan\Testing\assertType;


function testCacheLoad(Cache $cache): void
{
	$value = $cache->load('key', function (&$dependencies) {
		assertType('mixed', $dependencies);
		return 'value';
	});
	assertType('mixed', $value);
}


/** @param list<string> $keys */
function testCacheBulkLoad(Cache $cache, array $keys): void
{
	$values = $cache->bulkLoad($keys, function ($key, &$dependencies) {
		assertType('string', $key);
		assertType('mixed', $dependencies);
		return 'value';
	});
	assertType('array<string, mixed>', $values);
}


/** @param list<int> $keys */
function testCacheBulkLoadIntKeys(Cache $cache, array $keys): void
{
	$values = $cache->bulkLoad($keys, function ($key, &$dependencies) {
		assertType('int', $key);
		return 'value';
	});
	assertType('array<int, mixed>', $values);
}
