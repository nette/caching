<?php

/**
 * Test: Nette\Caching\Storages\FileStorage call().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Mock
{
	public function mockFunction($x, $y)
	{
		$GLOBALS['called'] = true;
		return $x + $y;
	}


	public function __serialize()
	{
		throw new Exception;
	}
}


$cache = new Cache(new FileStorage(getTempDir()));
$mock = new Mock;

$called = false;
Assert::same(55, $cache->call([$mock, 'mockFunction'], 5, 50));
Assert::true($called);

$called = false;
Assert::same(55, $cache->call([$mock, 'mockFunction'], 5, 50));
Assert::false($called);
