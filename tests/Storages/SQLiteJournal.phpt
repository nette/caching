<?php

/**
 * Test: Nette\Caching\Storages\SQLiteJournal basic test.
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Storages\SQLiteJournal;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/IJournalTestCase.php';


/**
 * @testCase
 */
class SQLiteJournalTest extends IJournalTestCase
{
	public function createJournal()
	{
		static $id = 0;
		return new SQLiteJournal(TEMP_DIR . '/sqlitejournal_' . ++$id . '.sqlite');
	}
}

$test = new SQLiteJournalTest;
$test->run();
