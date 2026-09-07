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
$cacheD = new Cache($storage, 'CA');

$cacheA->save('test', 'David');
$cacheB->save('test', 'Barry');
$cacheC->save('test', 'Oliver');
$cacheD->save('test', 'Bruce');

// cleaning the nested namespace 'C\x00A' does not affect 'C'
$storage->clean([Cache::Namespaces => [$cacheB->getNamespace()]]);
Assert::null($cacheB->load('test'));
Assert::same('Oliver', $cacheC->load('test'));

// cleaning 'C' removes the nested namespaces too, but not the default one nor 'CA'
$cacheB->save('test', 'Barry');
$storage->clean([Cache::Namespaces => [$cacheC->getNamespace()]]);

Assert::same('David', $cacheA->load('test'));
Assert::null($cacheB->load('test'));
Assert::null($cacheC->load('test'));
Assert::same('Bruce', $cacheD->load('test'));
