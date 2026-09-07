<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\RedisStorage clean by namespaces.
 */

use Nette\Caching\Cache;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/RedisConnect.php';

$storage = createRedisStorage('nette-namespaces:');

$cacheA = new Cache($storage);
$cacheB = new Cache($storage, 'C' . Cache::NamespaceSeparator . 'A');
$cacheC = new Cache($storage, 'C');

$cacheA->save('test', 'David');
$cacheB->save('test', 'Barry');
$cacheC->save('test', 'Oliver');

// cleaning 'C' must not affect the nested namespace 'C\x00A' nor the default namespace
$storage->clean([Cache::Namespaces => [$cacheC->getNamespace()]]);

Assert::same('David', $cacheA->load('test'));
Assert::same('Barry', $cacheB->load('test'));
Assert::null($cacheC->load('test'));

$storage->clean([Cache::Namespaces => [$cacheB->getNamespace()]]);
Assert::null($cacheB->load('test'));
Assert::same('David', $cacheA->load('test'));
