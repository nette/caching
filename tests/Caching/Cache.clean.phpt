<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Cache::clean() and its conditions.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Cache.php';


class CleanSpyStorage extends TestStorage
{
	public array $conditions = [];


	public function clean(array $conditions): void
	{
		$this->conditions[] = $conditions;
	}
}


/** Returns the conditions the storage received. */
function clean(mixed ...$args): array
{
	$storage = new CleanSpyStorage;
	new Cache($storage)->clean(...$args);
	return $storage->conditions;
}


test('named arguments', function () {
	Assert::same([[Cache::Tags => ['a', 'b']]], clean(tags: ['a', 'b']));
	Assert::same([[Cache::Priority => 100]], clean(priority: 100));
	Assert::same([[Cache::Namespaces => ['ns']]], clean(namespaces: ['ns']));
	Assert::same([[Cache::All => true]], clean(all: true));
	Assert::same([[Cache::Tags => ['a'], Cache::Priority => 50]], clean(tags: ['a'], priority: 50));
});


test('tags and namespaces are passed as lists', function () {
	Assert::same([[Cache::Tags => ['a'], Cache::Namespaces => ['ns']]], clean(tags: ['x' => 'a'], namespaces: ['y' => 'ns']));
});


test('empty lists clean nothing', function () {
	Assert::same([], clean(tags: []));
	Assert::same([], clean(tags: [], namespaces: []));
	Assert::same([[Cache::Tags => [], Cache::Priority => 0]], clean(tags: [], priority: 0));
});


test('no condition throws', function () {
	foreach ([[], [[]], [[Cache::Tags => null]], [[Cache::All => false]]] as $args) {
		Assert::exception(
			fn() => clean(...$args),
			Nette\InvalidArgumentException::class,
			'Specify what to clean: tags, priority, namespaces or all; expired items are removed by Storage::clean([]).',
		);
	}
});


test('array of conditions', function () {
	Assert::same([[Cache::Tags => ['a'], Cache::Priority => 50]], clean([Cache::Priority => 50, Cache::Tags => ['a']]));
	Assert::same([[Cache::All => true]], clean([Cache::All => true]));
	Assert::same([[Cache::Priority => 5]], clean([Cache::Tags => null, Cache::Priority => 5]));
	Assert::same([], clean([Cache::Tags => []]));
});


test('single tag or namespace in array is wrapped into list', function () {
	Assert::same([[Cache::Tags => ['a'], Cache::Namespaces => ['ns']]], clean([Cache::Tags => 'a', Cache::Namespaces => 'ns']));
});


test('unknown key in array throws', function () {
	Assert::exception(
		fn() => clean(['tag' => ['a']]),
		Nette\InvalidArgumentException::class,
		"Unknown condition 'tag', did you mean 'tags'?",
	);
	Assert::exception(
		fn() => clean([['a']]),
		Nette\InvalidArgumentException::class,
		"Unknown condition '0'.",
	);
	Assert::exception(
		fn() => clean([Cache::Expire => '20 minutes']),
		Nette\InvalidArgumentException::class,
		"'expire' is a dependency of an item, not a condition of Cache::clean().",
	);
});


test('value of wrong type in array throws', function () {
	Assert::exception(
		fn() => clean([Cache::Priority => '100']),
		TypeError::class,
		'%a%($priority) must be of type ?int, string given%a%',
	);
	Assert::exception(
		fn() => clean([Cache::All => 1]),
		TypeError::class,
		'%a%($all) must be of type bool, int given%a%',
	);
});


test('array and named arguments cannot be combined', function () {
	Assert::exception(
		fn() => clean([Cache::Tags => ['a']], priority: 50),
		Nette\InvalidArgumentException::class,
		'Conditions can be given either as an array or as named arguments, not both.',
	);
});
