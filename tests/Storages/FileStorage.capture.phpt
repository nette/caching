<?php

/**
 * Test: Nette\Caching\Storages\FileStorage capture().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$cache = new Cache(new FileStorage(getTempDir()));


ob_start();
$capture = $cache->capture('key');
Assert::type(Nette\Caching\OutputHelper::class, $capture);
echo 'Hello';
$capture->end();
Assert::same('Hello', ob_get_clean());


Assert::same('Hello', $cache->load('key'));


ob_start();
Assert::null($cache->capture('key'));
Assert::same('Hello', ob_get_clean());



ob_start();
$capture = $cache->capture('key2');
Assert::type(Nette\Caching\OutputHelper::class, $capture);
echo 'Hello';
$capture->rollback();
Assert::same('Hello', ob_get_clean());

Assert::same(null, $cache->load('key2'));
