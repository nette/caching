<?php

/**
 * Test: Nette\Caching\Cache save().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


// save value with dependencies
$storage = new testStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Tags => ['tag']];

$cache->save('key', 'value', $dependencies);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same($dependencies, $res['dependencies']);


// save callback return value
$storage = new testStorage;
$cache = new Cache($storage, 'ns');

$cache->save('key', fn() => 'value');

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same([], $res['dependencies']);


// save callback return value with dependencies
$storage = new testStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Tags => ['tag']];

$cache->save('key', fn() => 'value', $dependencies);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same($dependencies, $res['dependencies']);


// do not save already expired data
$storage = new testStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Expire => new DateTime];

$res = $cache->save('key', fn() => 'value', $dependencies);
Assert::same('value', $res);

$res = $cache->load('key');
Assert::null($res);
