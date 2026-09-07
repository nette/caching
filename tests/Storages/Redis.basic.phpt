<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage basic usage.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$storage = createRedisStorage('nette-basic:');
$cache = new Cache($storage);

$key = 'nette';
$value = ['rulez', new stdClass];


Assert::null($cache->load($key));

// write & read
$cache->save($key, $value);
Assert::equal($value, $cache->load($key));

// remove
$cache->remove($key);
Assert::null($cache->load($key));

// non-serialized string data
$storage->write('str', 'plain', []);
Assert::same('plain', $storage->read('str'));

// Items dependency is not supported
Assert::exception(
	fn() => $cache->save($key, 'value', [Cache::Items => ['another']]),
	Nette\NotSupportedException::class,
	'Dependency Cache::Items is not supported by Nette\Caching\Storages\RedisStorage.',
);

// clean all removes only prefixed keys
$other = createRedisStorage('nette-basic-other:');
$other->write('kept', 'value', []);
$storage->write('removed', 'value', []);

$storage->clean([Cache::All => true]);
Assert::null($storage->read('removed'));
Assert::same('value', $other->read('kept'));
