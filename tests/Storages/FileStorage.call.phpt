<?php

/**
 * Test: Nette\Caching\Storages\FileStorage call().
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Mock
{
	function mockFunction($x, $y)
	{
		$GLOBALS['called'] = true;
		return $x + $y;
	}


	function __sleep()
	{
		throw new Exception;
	}
}


$cache = new Cache(new FileStorage(TEMP_DIR));
$mock = new Mock;

$called = false;
Assert::same(55, $cache->call([$mock, 'mockFunction'], 5, 50));
Assert::true($called);

$called = false;
Assert::same(55, $cache->call([$mock, 'mockFunction'], 5, 50));
Assert::false($called);
