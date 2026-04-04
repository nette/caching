<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage ignores files of other tools in the cache directory
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$dir = getTempDir();
$storage = new FileStorage($dir);
$cache = new Cache($storage);
(new Cache($storage, 'ns'))->save('key', 'value', [Cache::Expire => 1]);
$cache->save('key', 'value');

// Latte stores compiled templates and their lock files in its own subdirectory
mkdir($dir . '/latte');
file_put_contents($dir . '/latte/_include-template.php', '<?php echo 1;');
file_put_contents($dir . '/latte/_include-template.php.lock', '0942e7abcdef0123456789');

// a file that is not a cache entry directly in the cache directory
file_put_contents($dir . '/_foreign.lock', '0942e7abcdef0123456789');

sleep(2);


test('garbage collector skips foreign files', function () use ($storage, $cache, $dir) {
	Assert::noError(fn() => $storage->clean([]));
	Assert::null((new Cache($storage, 'ns'))->load('key'));
	Assert::same('value', $cache->load('key'));
	Assert::true(is_file($dir . '/latte/_include-template.php.lock'));
	Assert::true(is_file($dir . '/_foreign.lock'));
});


test('cleaning all keeps files in foreign subdirectories', function () use ($storage, $cache, $dir) {
	$storage->clean([Cache::All => true]);
	Assert::null($cache->load('key'));
	Assert::true(is_file($dir . '/latte/_include-template.php'));
	Assert::true(is_file($dir . '/latte/_include-template.php.lock'));
});
