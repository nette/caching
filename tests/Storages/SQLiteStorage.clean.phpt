<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage probabilistic cleaning
 * @phpExtension pdo
 * @phpExtension pdo_sqlite
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\SQLiteStorage;
use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


// first we'll set up a test file, storage and cache
$db_file = getTempDir() . '/sqlite_clean_test.db';

// we'll start with garbage collection probability set to 0
SQLiteStorage::$gcProbability = 0;
$storage = new SQLiteStorage($db_file);

$cache = new Cache($storage);

$key = 'nette';

// We'll write an entry to cache which expires after 1 second
$cache->save($key, 'rulez', [
	Cache::Expire => time() + 1
]);

// there should be one entry in the cache
Assert::same(1, countStorageEntries($storage), 'Test cache entry should be saved');

// wait until the item expires
sleep(2);

// expired item should still be present in the database
Assert::same(1, countStorageEntries($storage), 'Expired item should still be in DB prior to cleanup');

// PHASE 2

// Now we will reload the storage object on the same DB file, with garbage collection probability set to 1 (100%)
SQLiteStorage::$gcProbability = 1;

// The storage constructor should run the clean() function.
$fresh_storage = new SQLiteStorage($db_file);

// cache DB should now be empty (zero rows)
Assert::same(0, countStorageEntries($fresh_storage), 'Expired item should be cleaned up');

// ok, all done

// clean up our test file
if (file_exists($db_file) && !unlink($db_file)) {
    trigger_error("Failed to clean up test database: $db_file", E_USER_WARNING);
}


function countStorageEntries(SQLiteStorage $storage): int {

	// because we are checking EXPIRED cache entries, we need to access the SQLite file directly, rather than use the standard SqliteStorage methods
	try {
			
		// we use Reflection to access the private 'pdo' property of the SQLiteStorage object
        $reflection = new ReflectionProperty(SQLiteStorage::class, 'pdo');
        $reflection->setAccessible(true);
        $pdo = $reflection->getValue($storage);
        
		// Now we use the storage object's own PDO connection to count the number of rows in the cache
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cache');
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (ReflectionException $e) {
        Assert::fail('Unable to access PDO property: ' . $e->getMessage());
    }

}