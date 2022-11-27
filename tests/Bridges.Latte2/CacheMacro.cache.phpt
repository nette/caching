<?php

/**
 * Test: {cache ...}
 */

declare(strict_types=1);

use Nette\Bridges\CacheLatte\CacheMacro;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '>')) {
	Tester\Environment::skip('Test for Latte 2');
}


$latte = new Latte\Engine;
$latte->setTempDirectory(getTempDir());
$latte->addMacro('cache', new CacheMacro($latte->getCompiler()));
$latte->addProvider('cacheStorage', new Nette\Caching\Storages\DevNullStorage);

$params['title'] = 'Hello';
$params['id'] = 456;

Assert::matchFile(
	__DIR__ . '/expected/CacheMacro.cache.php',
	$latte->compile(__DIR__ . '/templates/cache.latte')
);
Assert::matchFile(
	__DIR__ . '/expected/CacheMacro.cache.html',
	$latte->renderToString(
		__DIR__ . '/templates/cache.latte',
		$params
	)
);
Assert::matchFile(
	__DIR__ . '/expected/CacheMacro.cache.inc.php',
	file_get_contents($latte->getCacheFile(__DIR__ . strtr('/templates/include.cache.latte', '/', DIRECTORY_SEPARATOR)))
);
