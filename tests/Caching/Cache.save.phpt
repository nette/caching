<?php

/**
 * Test: Nette\Caching\Cache save().
 */

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


// save value with dependencies
$storage = new testStorage();
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::TAGS => ['tag']];

$cache->save('key', 'value', $dependencies);

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal($dependencies, $res['dependencies']);


// save callback return value
$storage = new testStorage();
$cache = new Cache($storage, 'ns');

$cache->save('key', function () {
	return 'value';
});

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal([], $res['dependencies']);


// save callback return value with dependencies
$storage = new testStorage();
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::TAGS => ['tag']];

$cache->save('key', function () {
	return 'value';
}, $dependencies);

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal($dependencies, $res['dependencies']);


// do not save already expired data
$storage = new testStorage();
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::EXPIRATION => new DateTime];

$res = $cache->save('key', function () {
	return 'value';
}, $dependencies);
Assert::equal('value', $res);

$res = $cache->load('key');
Assert::null($res);
