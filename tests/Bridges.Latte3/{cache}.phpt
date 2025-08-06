<?php

/**
 * Test: {cache ...}
 */

declare(strict_types=1);

use Nette\Bridges\CacheLatte\CacheExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setTempDirectory(getTempDir());
$latte->addExtension(new CacheExtension(new Nette\Caching\Storages\MemoryStorage));

$params['title'] = 'Hello';
$params['id'] = 456;

Assert::matchFile(
	__DIR__ . '/expected/cache.php',
	$latte->compile(__DIR__ . '/templates/cache.latte'),
);
Assert::matchFile(
	__DIR__ . '/expected/cache.html',
	$latte->renderToString(
		__DIR__ . '/templates/cache.latte',
		$params,
	),
);
Assert::matchFile(
	__DIR__ . '/expected/cache.html',
	$latte->renderToString(
		__DIR__ . '/templates/cache.latte',
		$params,
	),
);
Assert::matchFile(
	__DIR__ . '/expected/cache.inc.php',
	file_get_contents($latte->getCacheFile(__DIR__ . strtr('/templates/include.cache.latte', '/', DIRECTORY_SEPARATOR))),
);
