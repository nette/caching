<?php

/**
 * Test: CacheExtension.
 */

use Nette\DI,
	Nette\Bridges\CacheDI\CacheExtension,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function() {
	$compiler = new DI\Compiler;
	$compiler->addExtension('cache', new CacheExtension(TEMP_DIR));

	eval($compiler->compile([], 'Container1'));

	$container = new Container1;
	$container->initialize();

	$journal = $container->getService('cache.journal');
	Assert::type('Nette\Caching\Storages\SQLiteJournal', $journal);

	$storage = $container->getService('cache.storage');
	Assert::type('Nette\Caching\Storages\FileStorage', $storage);

	// aliases
	Assert::same($journal, $container->getService('nette.cacheJournal'));
	Assert::same($storage, $container->getService('cacheStorage'));
});
