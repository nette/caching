<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage clean() skips non-cache files.
 *
 * Regression test for GH#45: FileStorage GC crashes with "unserialize(): Error at offset"
 * when non-cache files (e.g. Latte lock files) matching the _* pattern exist
 * in the cache directory.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$dir = getTempDir();
$storage = new FileStorage($dir);
$cache = new Cache($storage);


// Write valid cache entries
$cache->save('key1', 'value1');
$cache->save('key2', 'value2');

Assert::same('value1', $cache->load('key1'));
Assert::same('value2', $cache->load('key2'));


// Create foreign files that match the _* pattern used by GC.
// These simulate Latte lock files and other non-cache files that may
// coexist in the same temp directory tree.

// Case 1: Content starting with digits — triggers (int) cast to non-zero size
// in readMetaAndLock(), then garbage gets passed to unserialize().
// This is the exact scenario from GH#45 diagnosis by @martinbohmcz:
// PHP 8 interprets "0942e7" as float 942e7, (int) cast = 9420000.
file_put_contents($dir . '/_includes-template.php.lock', '0942e7deadbeef');

// Case 2: Binary content
file_put_contents($dir . '/_session.lock', "\xff\xfe\x00\x01\x80\x90binary");

// Case 3: Empty file
file_put_contents($dir . '/_empty.lock', '');

// Case 4: File with zero-like header (6 bytes of zeros = size 0, skip path)
file_put_contents($dir . '/_zero-header.tmp', '000000some-data');

// Case 5: File with valid-looking size but garbage meta
file_put_contents($dir . '/_fake-meta.dat', '000010not-a-serialized-array');

// Case 6: File in a subdirectory matching _* pattern
@mkdir($dir . '/_subdir', 0777, true);
file_put_contents($dir . '/_subdir/_nested.lock', 'nested-lock-data');


// GC (collector mode) must not crash on foreign files
Assert::noError(function () use ($storage) {
	$storage->clean([]);
});


// Valid cache entries must still be readable
Assert::same('value1', $cache->load('key1'));
Assert::same('value2', $cache->load('key2'));


// Foreign files must be untouched (GC should skip, not delete them)
Assert::true(file_exists($dir . '/_includes-template.php.lock'));
Assert::true(file_exists($dir . '/_session.lock'));
Assert::true(file_exists($dir . '/_empty.lock'));
Assert::true(file_exists($dir . '/_zero-header.tmp'));
Assert::true(file_exists($dir . '/_fake-meta.dat'));
Assert::true(file_exists($dir . '/_subdir/_nested.lock'));


// Cache::All mode deletes all _* files including foreign ones — expected behavior
$storage->clean([Cache::All => true]);

Assert::null($cache->load('key1'));
Assert::null($cache->load('key2'));
Assert::false(file_exists($dir . '/_includes-template.php.lock'));
Assert::false(file_exists($dir . '/_session.lock'));
Assert::false(file_exists($dir . '/_empty.lock'));
Assert::false(file_exists($dir . '/_zero-header.tmp'));
Assert::false(file_exists($dir . '/_fake-meta.dat'));
