<?php

/**
 * Test: Nette\Caching\Storages\FileStorage exception situations.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(
	fn() => new FileStorage(getTempDir() . '/missing'),
	Nette\DirectoryNotFoundException::class,
	"Directory '%a%' not found.",
);


Assert::exception(function () {
	$storage = new FileStorage(getTempDir());
	$storage->write('a', 'b', [Cache::Tags => 'c']);
}, Nette\InvalidStateException::class, 'CacheJournal has not been provided.');
