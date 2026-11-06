<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Dependencies as the typed form of dependencies.
 */

use Nette\Caching\{Cache, Dependencies};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Cache.php';


test('generator typed Dependencies gets the object', function () {
	$storage = new TestStorage;
	$cache = new Cache($storage);

	$value = $cache->load('key', function (Dependencies $dp) {
		$dp->tags[] = 'tag';
		$dp->priority = 0;
		return 'value';
	});
	Assert::same('value', $value);
	Assert::same([Cache::Tags => ['tag'], Cache::Priority => 0], $cache->load('key')['dependencies']);
});


test('untyped generator still gets the array', function () {
	$cache = new Cache(new TestStorage);
	$cache->load('key', function (&$dp) {
		Assert::same([Cache::Tags => ['initial']], $dp);
		$dp[Cache::Priority] = 10;
		return 'value';
	}, new Dependencies(tags: ['initial']));
	Assert::same([Cache::Tags => ['initial'], Cache::Priority => 10], $cache->load('key')['dependencies']);
});


test('generator gets a copy of the Dependencies passed to load()', function () {
	$cache = new Cache(new TestStorage);
	$initial = new Dependencies(tags: ['initial']);
	$cache->load('key', function (Dependencies $dp) {
		Assert::same(['initial'], $dp->tags);
		$dp->tags[] = 'added';
		return 'value';
	}, $initial);
	Assert::same(['initial'], $initial->tags);
	Assert::same([Cache::Tags => ['initial', 'added']], $cache->load('key')['dependencies']);
});


test('typed generator gets the array passed to load() as the object and may change it', function () {
	$cache = new Cache(new TestStorage);
	$cache->load('key', function (Dependencies $dp) {
		Assert::equal(new Dependencies(tags: ['array'], priority: 5), $dp);
		$dp->tags = [];
		return 'value';
	}, [Cache::Tags => 'array', Cache::Priority => 5]);
	Assert::same([Cache::Priority => 5], $cache->load('key')['dependencies']);
});


test('fromArray() accepts single values for lists and skips nulls', function () {
	Assert::equal(
		new Dependencies(
			expire: '1 hour',
			sliding: true,
			tags: ['tag'],
			priority: 10,
			files: ['file'],
			items: ['item'],
			constants: ['CONST'],
		),
		Dependencies::fromArray([
			Cache::Expire => '1 hour',
			Cache::Sliding => true,
			Cache::Tags => 'tag',
			Cache::Priority => 10,
			Cache::Files => 'file',
			Cache::Items => 'item',
			Cache::Constants => 'CONST',
			Cache::Callbacks => null,
		]),
	);
});


test('fromArray() keeps item keys that are objects or arrays intact', function () {
	$key = new stdClass;
	Assert::same([$key], Dependencies::fromArray([Cache::Items => $key])->items);
	Assert::same([['a', 'b']], Dependencies::fromArray([Cache::Items => [['a', 'b']]])->items);
});


test('fromArray() rejects what is not a dependency', function () {
	Assert::exception(
		fn() => Dependencies::fromArray(['expires' => '1 hour']),
		Nette\InvalidArgumentException::class,
		"Unknown dependency 'expires', did you mean 'expire'?",
	);
	Assert::exception(
		fn() => Dependencies::fromArray(['unrelated' => true]),
		Nette\InvalidArgumentException::class,
		"Unknown dependency 'unrelated'.",
	);
	Assert::exception(
		fn() => Dependencies::fromArray([Cache::Namespaces => ['ns']]),
		Nette\InvalidArgumentException::class,
		"'namespaces' is a condition of Cache::clean(), not a dependency of an item.",
	);
	Assert::exception(
		fn() => Dependencies::fromArray([Cache::All => true]),
		Nette\InvalidArgumentException::class,
		"'all' is a condition of Cache::clean(), not a dependency of an item.",
	);
	Assert::exception(
		fn() => Dependencies::fromArray([Cache::Priority => '10']),
		TypeError::class,
		'Cannot assign string to property Nette\Caching\Dependencies::$priority of type ?int',
	);
	Assert::exception(
		fn() => Dependencies::fromArray([Cache::Sliding => 1]),
		TypeError::class,
	);
	Assert::exception(
		fn() => new Cache(new TestStorage)->save('key', 'value', ['expires' => '1 hour']),
		Nette\InvalidArgumentException::class,
	);
});


test('empty lists are left out of what the storage gets', function () {
	$cache = new Cache(new TestStorage);
	$cache->save('key', 'value', [Cache::Tags => [], Cache::Items => [], Cache::Sliding => false]);
	Assert::same([], $cache->load('key')['dependencies']);
});


test('bulkLoad() passes the key first', function () {
	$cache = new Cache(new TestStorage);
	$values = $cache->bulkLoad(['a', 'b'], function ($key, Dependencies $dp) {
		$dp->tags[] = "tag-$key";
		return "value-$key";
	});
	Assert::same(['a' => 'value-a', 'b' => 'value-b'], $values);
	Assert::same([Cache::Tags => ['tag-b']], $cache->load('b')['dependencies']);
});


test('save(), bulkSave(), wrap() and OutputHelper accept Dependencies', function () {
	$cache = new Cache(new TestStorage);
	$dp = new Dependencies(tags: ['tag']);

	$cache->save('save', 'value', $dp);
	Assert::same([Cache::Tags => ['tag']], $cache->load('save')['dependencies']);

	$cache->bulkSave(['bulk' => 'value'], $dp);
	Assert::same([Cache::Tags => ['tag']], $cache->load('bulk')['dependencies']);

	Assert::same('A', $cache->wrap('strtoupper', $dp)('a'));
	Assert::same([Cache::Tags => ['tag']], $cache->load(['strtoupper', ['a']])['dependencies']);

	ob_start();
	$cache->capture('output')->end($dp);
	ob_end_clean();
	Assert::same([Cache::Tags => ['tag']], $cache->load('output')['dependencies']);
});


test('files and constants are desugared to callbacks', function () {
	$cache = new Cache(new TestStorage);
	$cache->save('key', 'value', new Dependencies(files: [__FILE__], constants: ['PHP_VERSION']));
	$callbacks = $cache->load('key')['dependencies'][Cache::Callbacks];
	Assert::count(2, $callbacks);
	Assert::true(Cache::checkCallbacks($callbacks));
});


test('toArray() leaves out what is not set', function () {
	Assert::same([], (new Dependencies)->toArray());
	Assert::same(
		[Cache::Expire => '20 minutes', Cache::Sliding => true, Cache::Priority => 0],
		new Dependencies(expire: '20 minutes', sliding: true, priority: 0)->toArray(),
	);
});
