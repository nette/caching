<?php declare(strict_types=1);

/**
 * PHPStan type tests for Caching.
 */

use Nette\Caching\{Cache, Dependencies};
use function PHPStan\Testing\assertType;


function testCacheLoad(Cache $cache): void
{
	$value = $cache->load('key', function (&$dependencies) {
		assertType('mixed', $dependencies);
		return 'value';
	});
	assertType('mixed', $value);
}


function testCacheLoadWithDependencies(Cache $cache): void
{
	$cache->load('key', function (Dependencies $dependencies) {
		assertType(Dependencies::class, $dependencies);
		assertType('list<string>', $dependencies->tags);
		return 'value';
	}, new Dependencies(expire: '20 minutes'));
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
