<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage database file permissions.
 */

declare(strict_types=1);

use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
} elseif (defined('PHP_WINDOWS_VERSION_BUILD')) {
	Tester\Environment::skip('UNIX test only.');
}


test(function () {
	$file = TEMP_DIR . '/sqlitestorage.permissions.1.sqlite';
	Assert::false(file_exists($file));

	umask(0);
	(new SQLiteStorage($file))->write('foo', 'bar', []);

	Assert::same(0666, fileperms($file) & 0777);
});


test(function () {
	$file = TEMP_DIR . '/sqlitestorage.permissions.2.sqlite';
	Assert::false(file_exists($file));

	umask(0077);
	(new SQLiteStorage($file))->write('foo', 'bar', []);

	Assert::same(0600, fileperms($file) & 0777);
});
