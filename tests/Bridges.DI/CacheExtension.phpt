<?php

/**
 * Test: CacheExtension.
 */

declare(strict_types=1);

use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('cache', new CacheExtension(getTempDir()));

	eval($compiler->compile());

	$container = new Container;
	$container->initialize();

	$journal = $container->getService('cache.journal');
	Assert::type(Nette\Caching\Storages\SQLiteJournal::class, $journal);

	$storage = $container->getService('cache.storage');
	Assert::type(Nette\Caching\Storages\FileStorage::class, $storage);
});
