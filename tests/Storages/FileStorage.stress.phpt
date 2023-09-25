<?php

/**
 * Test: Nette\Caching\Storages\FileStorage atomicity test.
 * @multiple   5
 */

declare(strict_types=1);

use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


set_time_limit(0);


function randomStr()
{
	$s = str_repeat('LaTrine', rand(10, 2000));
	return sha1($s, binary: true) . $s;
}


function checkStr($s)
{
	return substr($s, 0, 20) === sha1(substr($s, 20), binary: true);
}


define('COUNT_FILES', 3);


$storage = new FileStorage(getTempDir());


// clear playground
for ($i = 0; $i <= COUNT_FILES; $i++) {
	$storage->write((string) $i, randomStr(), []);
}

// test loop
$hits = ['ok' => 0, 'notfound' => 0, 'error' => 0, 'cantwrite' => 0, 'cantdelete' => 0];
for ($counter = 0; $counter < 1000; $counter++) {
	// write
	$ok = $storage->write((string) rand(0, COUNT_FILES), randomStr(), []);
	if ($ok === false) {
		$hits['cantwrite']++;
	}

	// remove
	//$ok = $storage->remove((string) rand(0, COUNT_FILES));
	//if (!$ok) $hits['cantdelete']++;

	// read
	$res = $storage->read((string) rand(0, COUNT_FILES));

	// compare
	if ($res === null) {
		$hits['notfound']++;
	} elseif (checkStr($res)) {
		$hits['ok']++;
	} else {
		$hits['error']++;
	}
}

Assert::same([
	'ok' => 1000,
	'notfound' => 0,
	'error' => 0,
	'cantwrite' => 0,
	'cantdelete' => 0,
], $hits);

// expected results are:
//    [ok] => 1000       // should be 1000. If unlink() is used, sum [ok] + [notfound] should be 1000
//    [notfound] => 0    // means 'file not found', should be 0 if delete() is not used
//    [error] => 0,      // means 'file contents is damaged', MUST be 0
//    [cantwrite] => ?,  // means 'somebody else is writing this file'
//    [cantdelete] => 0  // means 'delete() has timeout',  should be 0

Assert::same(0, $hits['error']);
