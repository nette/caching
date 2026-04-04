<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage handles corrupted cache files gracefully.
 *
 * Verifies that reading a corrupted cache file returns null instead of
 * throwing unserialize errors. Covers both corrupted metadata and
 * corrupted data payloads.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$dir = getTempDir();
$storage = new FileStorage($dir);
$cache = new Cache($storage);


// --- Corrupted metadata ---

$cache->save('meta-test', 'hello');
Assert::same('hello', $cache->load('meta-test'));

// Find the cache file and corrupt its metadata
$files = glob($dir . '/_*');
Assert::truthy($files);

foreach ($files as $file) {
	if (is_file($file)) {
		// Overwrite with a valid-looking size header but garbage meta.
		// "000010" = meta size of 10, followed by non-serialized data.
		file_put_contents($file, '000010not-serial');
	}
}

// Reading corrupted meta should return null, not crash
Assert::null($cache->load('meta-test'));


// --- Corrupted serialized data ---

// Use a fresh storage to avoid interference
$dir2 = getTempDir() . '/data-test';
@mkdir($dir2, 0777, true);
$storage2 = new FileStorage($dir2);
$cache2 = new Cache($storage2);

$cache2->save('data-test', ['complex' => 'array', 'with' => [1, 2, 3]]);
Assert::type('array', $cache2->load('data-test'));

// Find the cache file
$files2 = glob($dir2 . '/_*');
Assert::truthy($files2);

foreach ($files2 as $file) {
	if (is_file($file)) {
		$content = file_get_contents($file);
		$size = (int) substr($content, 0, 6);
		$headerAndMeta = substr($content, 0, 6 + $size);
		// Keep valid header+meta but replace data with garbage
		file_put_contents($file, $headerAndMeta . 'CORRUPTED-DATA-NOT-SERIALIZABLE');
	}
}

// Reading corrupted data should not crash.
// The result may be false (failed unserialize) but must not throw.
Assert::noError(function () use ($cache2) {
	$cache2->load('data-test');
});


// --- Truncated file ---

$dir3 = getTempDir() . '/truncated-test';
@mkdir($dir3, 0777, true);
$storage3 = new FileStorage($dir3);
$cache3 = new Cache($storage3);

$cache3->save('trunc-test', str_repeat('x', 10000));
Assert::same(str_repeat('x', 10000), $cache3->load('trunc-test'));

// Truncate the file mid-data
$files3 = glob($dir3 . '/_*');
foreach ($files3 as $file) {
	if (is_file($file)) {
		$handle = fopen($file, 'r+b');
		ftruncate($handle, 50); // keep header + partial meta
		fclose($handle);
	}
}

// Should return null, not crash
Assert::null($cache3->load('trunc-test'));


// --- File with all zero bytes ---

$dir4 = getTempDir() . '/zeros-test';
@mkdir($dir4, 0777, true);
$storage4 = new FileStorage($dir4);
$cache4 = new Cache($storage4);

$cache4->save('zero-test', 'data');

$files4 = glob($dir4 . '/_*');
foreach ($files4 as $file) {
	if (is_file($file)) {
		file_put_contents($file, str_repeat("\x00", 100));
	}
}

// All-zero header = size 0, should return null
Assert::null($cache4->load('zero-test'));
