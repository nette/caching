<?php

/**
 * Test: Nette\Caching\Cache load().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


// load twice with fallback
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');

$value = $cache->load('key', function () {
	return 'value';
});
Assert::same('value', $value);

$data = $cache->load('key', function () {
	return "won't load this value"; // will read from storage
});
Assert::same('value', $data['data']);


// load twice with closure fallback, pass dependencies
$dependencies = [Cache::Tags => ['tag']];
$storage = new TestStorage;
$cache = new Cache($storage, 'ns');

$value = $cache->load('key', function (&$deps) use ($dependencies) {
	$deps = $dependencies;
	return 'value';
});
Assert::same('value', $value);

$data = $cache->load('key', function () {
	return "won't load this value"; // will read from storage
});
Assert::same('value', $data['data']);
Assert::same($dependencies, $data['dependencies']);


// load twice with fallback, pass dependencies
function fallback(&$deps)
{
	global $dependencies;
	$deps = $dependencies;
	return 'value';
}


$value = $cache->load('key2', 'fallback');
Assert::same('value', $value);
$data = $cache->load('key2');
Assert::same('value', $data['data']);
Assert::same($dependencies, $data['dependencies']);
