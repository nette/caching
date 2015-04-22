<?php

/**
 * Test: Nette\Caching\Storages\FileJournal basic test.
 */

use Nette\Caching\Storages\FileJournal;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/IJournalTestCase.inc';


class FileJournalTest extends IJournalTestCase
{

	public function createJournal()
	{
		FileJournal::$debug = TRUE;
		return new FileJournal(TEMP_DIR);
	}

}

$test = new FileJournalTest();
$test->run();
