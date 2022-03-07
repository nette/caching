<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage tags dependency test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteJournal;
use Tester\Assert;


require __DIR__ . '/../../bootstrap.php';


$journal = new SQLiteJournal(':memory:');

// Writing cache...
$keys = [];
for ($i = 0; $i < 2000; $i++) {
	$keys[] = 'key' . $i;
}

foreach ($keys as $key) {
	$journal->write($key, [
		Cache::Tags => ['one', 'two'],
	]);
}

$journal->write('keyThree', [
	Cache::Tags => ['three'],
]);

// Cleaning by tags...
$keys = $journal->clean([
	Cache::Tags => ['one', 'xx'],
]);

Assert::same($keys, $keys);
