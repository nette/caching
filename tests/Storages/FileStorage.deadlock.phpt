<?php

/**
 * Test: Nette\Caching\Cache dead lock & exception test.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$storage = new FileStorage(getTempDir());
$cache = new Cache($storage);

try {
	$cache->load('key', function () {
		throw new Exception;
	});
} catch (Throwable $e) {
}

Assert::noError(
	fn() => $cache->load('key', function () {}),
);
