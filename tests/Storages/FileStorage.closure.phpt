<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// key and data with special chars
$key = '../' . implode('', range("\x00", "\x1F"));
$value = range("\x00", "\xFF");

$cache = new Cache(new FileStorage(getTempDir()));

Assert::null($cache->load($key));


// Writing cache using Closure, which is deprecated...
Assert::error(function () use ($cache, $key, $value, &$res) {
	$res = $cache->save($key, fn() => $value);
}, E_USER_DEPRECATED);

Assert::same($res, $value);

Assert::same($cache->load($key), $value);


// Removing from cache using null callback...
Assert::error(fn() => $cache->save($key, fn() => null), E_USER_DEPRECATED);

Assert::null($cache->load($key));
