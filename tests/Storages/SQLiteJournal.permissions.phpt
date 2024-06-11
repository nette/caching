<?php

/**
 * Test: Nette\Caching\Storages\SQLiteJournal database file permissions.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (defined('PHP_WINDOWS_VERSION_BUILD')) {
	Tester\Environment::skip('UNIX test only.');
}


test('', function () {
	$file = getTempDir() . '/sqlitejournal.permissions.1.sqlite';
	Assert::false(file_exists($file));

	umask(0);
	(new SQLiteJournal($file))->write('foo', []);

	Assert::same(0o666, fileperms($file) & 0o777);
});


test('', function () {
	$file = getTempDir() . '/sqlitejournal.permissions.2.sqlite';
	Assert::false(file_exists($file));

	umask(0o077);
	(new SQLiteJournal($file))->write('foo', []);

	Assert::same(0o600, fileperms($file) & 0o777);
});
