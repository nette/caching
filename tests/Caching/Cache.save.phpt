<?php

/**
 * Test: Nette\Caching\Cache save().
 */

use Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.inc';


// save value with dependencies
$storage = new testStorage();
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::TAGS => 'tag'];

$cache->save('key', 'value', $dependencies);

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal($dependencies, $res['dependencies']);


// save callback return value
$storage = new testStorage();
$cache = new Cache($storage, 'ns');

$cache->save('key', function() {
	return 'value';
});

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal([], $res['dependencies']);


// save callback return value with dependencies
$storage = new testStorage();
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::TAGS => 'tag'];

$cache->save('key', function() {
	return 'value';
}, $dependencies);

$res = $cache->load('key');
Assert::equal('value', $res['data']);
Assert::equal($dependencies, $res['dependencies']);
