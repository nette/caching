<?php

/**
 * Test: Nette\Caching\Storages\FileStorage wrap().
 */

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


$cache = new Cache(new FileStorage(TEMP_DIR));

$called = false;
Assert::same(55, call_user_func($cache->wrap('mockFunction'), 5, 50));
Assert::true($called);

$called = false;
Assert::same(55, call_user_func($cache->wrap('mockFunction'), 5, 50));
Assert::false($called);


$called = false;
$callback = [new Test, 'mockMethod'];
Assert::same(55, call_user_func($cache->wrap($callback), 5, 50));
Assert::true($called);

$called = false;
Assert::same(55, call_user_func($cache->wrap($callback), 5, 50));
Assert::false($called);
