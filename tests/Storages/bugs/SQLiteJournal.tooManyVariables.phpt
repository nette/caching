<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage tags dependency test.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


$journal = new SQLiteJournal(':memory:');

// Writing cache...
$keys = [];
for ($i = 0; $i < 2000; $i++) {
	$keys[] = 'key' . $i;
}

foreach ($keys as $key) {
	$journal->write($key, [
		Cache::TAGS => ['one', 'two'],
	]);
}

$journal->write('keyThree', [
	Cache::TAGS => ['three'],
]);

// Cleaning by tags...
$keys = $journal->clean([
	Cache::TAGS => ['one', 'xx'],
]);

Assert::same($keys, $keys);
