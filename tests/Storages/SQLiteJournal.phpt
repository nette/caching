<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\SQLiteJournal basic test.
 * @phpExtension pdo_sqlite
 */

use Nette\Caching\Storages\SQLiteJournal;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/IJournalTestCase.php';


class SQLiteJournalTest extends IJournalTestCase
{
	public function createJournal()
	{
		static $id = 0;
		return new SQLiteJournal(getTempDir() . '/sqlitejournal_' . ++$id . '.sqlite');
	}
}

$test = new SQLiteJournalTest;
$test->run();
