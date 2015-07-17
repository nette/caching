<?php

/**
 * Test: Nette\Caching\Storages\SQLiteJournal basic test.
 */

use Nette\Caching\Storages\SQLiteJournal;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/IJournalTestCase.inc';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


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
