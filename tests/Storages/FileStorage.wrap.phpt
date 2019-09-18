<?php

/**
 * Test: Nette\Caching\Storages\FileStorage wrap().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function mockFunction($x, $y)
{
	$GLOBALS['called'] = true;
	return $x + $y;
}


class Test
{
	public function mockMethod($x, $y)
	{
		$GLOBALS['called'] = true;
		return $x + $y;
	}
}


$cache = new Cache(new FileStorage(getTempDir()));

$called = false;
Assert::same(55, $cache->wrap('mockFunction')(5, 50));
Assert::true($called);

$called = false;
Assert::same(55, $cache->wrap('mockFunction')(5, 50));
Assert::false($called);


$called = false;
$callback = [new Test, 'mockMethod'];
Assert::same(55, $cache->wrap($callback)(5, 50));
Assert::true($called);

$called = false;
Assert::same(55, $cache->wrap($callback)(5, 50));
Assert::false($called);
