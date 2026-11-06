<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Cache save().
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


// save value with dependencies
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Tags => ['tag']];

$cache->save('key', 'value', $dependencies);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same($dependencies, $res['dependencies']);


// save callback return value, which is deprecated
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');

Assert::error(
	fn() => $cache->save('key', fn() => 'value'),
	E_USER_DEPRECATED,
	'Nette\Caching\Cache::save() with a closure is deprecated, use load() with a generator.',
);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same([], $res['dependencies']);


// save callback return value with dependencies
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Tags => ['tag']];

Assert::error(fn() => $cache->save('key', fn() => 'value', $dependencies), E_USER_DEPRECATED);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same($dependencies, $res['dependencies']);


// do not save already expired data
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::Expire => new DateTime];

Assert::same('value', $cache->save('key', 'value', $dependencies));
Assert::null($cache->load('key'));
