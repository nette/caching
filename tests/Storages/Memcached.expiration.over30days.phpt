<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\MemcachedStorage expiration longer than 30 days.
 * Memcached interprets relative times over 30 days as absolute timestamps.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-expiration-long', getTempDir());


$key = 'nette-memcached-expiration-over30days-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage);


// interval over 30 days must not be interpreted as an absolute (past) timestamp
$cache->save($key, $value, [
	Cache::Expire => '40 days',
]);

Assert::same($value, $cache->load($key));


// bulk variant
$cache->bulkSave([$key => $value], [
	Cache::Expire => '40 days',
]);

Assert::same($value, $cache->load($key));
