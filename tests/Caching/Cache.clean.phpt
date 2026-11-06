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


test('no condition collects garbage', function () {
	Assert::same([[]], clean());
});


test('array of conditions', function () {
	Assert::same([[Cache::Tags => ['a']]], clean([Cache::Tags => 'a']));
	Assert::same([[Cache::All => true]], clean([Cache::All => true]));
});


test('array and named arguments cannot be combined', function () {
	Assert::exception(
		fn() => clean([Cache::Tags => ['a']], priority: 50),
		Nette\InvalidArgumentException::class,
		'Conditions can be given either as an array or as named arguments, not both.',
	);
});
