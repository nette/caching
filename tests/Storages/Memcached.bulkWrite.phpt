<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage and bulkWrite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemcachedStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcached.');
}

Tester\Environment::lock('memcached-files', getTempDir());


$storage = new MemcachedStorage('localhost', 11211, '', new SQLiteJournal(getTempDir() . '/journal-memcached.s3db'));
$cache = new Cache($storage);

//standard
$cache->bulkSave(['foo' => 'bar']);
Assert::same(['foo' => 'bar', 'lorem' => null], $cache->bulkLoad(['foo', 'lorem']));

//tags
$dependencies = [Cache::Tags => ['tag']];
$cache->bulkSave(['foo' => 'bar'], $dependencies);
Assert::same(['foo' => 'bar'], $cache->bulkLoad(['foo']));
$cache->clean($dependencies);
Assert::same(['foo' => null], $cache->bulkLoad(['foo']));
