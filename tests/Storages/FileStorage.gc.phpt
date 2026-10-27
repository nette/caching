<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage garbage collector run by the constructor.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$dir = getTempDir() . '/gc';
mkdir($dir);
$countFiles = fn() => count(glob($dir . '/_*')) + count(glob($dir . '/_*/_*'));

FileStorage::$gcProbability = 0;
$storage = new FileStorage($dir);
$root = new Cache($storage);
$namespaced = new Cache($storage, 'ns');
for ($i = 0; $i < 5; $i++) {
	$root->save("expired$i", 'value', [Cache::Expire => 1]);
	$namespaced->save("expired$i", 'value', [Cache::Expire => 1]);
}

$root->save('valid', 'value');
$namespaced->save('valid', 'value');
sleep(2);


test('files locked by another process are skipped, not waited for', function () use ($dir) {
	$locked = [];
	foreach (glob($dir . '/_ns/_*') as $file) {
		$locked[] = $handle = fopen($file, 'r+b');
		flock($handle, LOCK_EX);
	}

	FileStorage::$gcProbability = 1;
	for ($i = 0; $i < 50 && count(glob($dir . '/_*')) > 2; $i++) {
		new FileStorage($dir);
	}

	Assert::count(2, glob($dir . '/_*')); // the valid item without namespace and the directory
	Assert::count(6, glob($dir . '/_ns/_*')); // all locked, none collected

	foreach ($locked as $handle) {
		flock($handle, LOCK_UN);
		fclose($handle);
	}
});


test('batches collect expired items in the root and in namespaces', function () use ($dir, $countFiles, $root, $namespaced) {
	FileStorage::$gcProbability = 1;
	for ($i = 0; $i < 50 && $countFiles() > 3; $i++) {
		new FileStorage($dir);
	}

	Assert::same(3, $countFiles()); // two valid files and the directory
	Assert::same('value', $root->load('valid'));
	Assert::same('value', $namespaced->load('valid'));
});
