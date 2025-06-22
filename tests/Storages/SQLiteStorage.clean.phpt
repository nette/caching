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

require __DIR__ . '/../bootstrap.php';

$key = 'nette';
$value = 'is the best';

// Test 1: With gcProbability = 0, expired items should remain
SQLiteStorage::$gcProbability = 0.0;
$storage1 = new SQLiteStorage(':memory:');
$cache1 = new Cache($storage1);

// Save item that expires in 1 second
$cache1->save($key, $value, [
    Cache::Expire => time() + 1,
]);

// Wait for expiration
sleep(2);
clearstatcache();

// With gcProbability = 0, the expired item should still be in DB
// We need to check the storage directly since Cache::load() respects expiration
$reflection = new ReflectionClass($storage1);
$pdoProperty = $reflection->getProperty('pdo');
$pdoProperty->setAccessible(true);
$pdo = $pdoProperty->getValue($storage1);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM cache WHERE key = ?');
$stmt->execute([$key]);
$count = $stmt->fetchColumn();

Assert::same(1, (int)$count, 'Expired item should still exist in DB when gcProbability = 0');

// Test 2: With gcProbability = 1.0, expired items should be cleaned
SQLiteStorage::$gcProbability = 1.0;
$storage2 = new SQLiteStorage(':memory:');
$cache2 = new Cache($storage2);

// Save item that expires immediately
$cache2->save($key, $value, [
    Cache::Expire => time() - 1, // Already expired
]);

// Create another storage instance - this should trigger cleaning
$storage3 = new SQLiteStorage(':memory:');
// But we need the same database... let's use a different approach

// Alternative approach: Test that clean() is called by checking the database
SQLiteStorage::$gcProbability = 1.0;
$tempFile = tempnam(sys_get_temp_dir(), 'nette_cache_test');
$storage4 = new SQLiteStorage($tempFile);
$cache4 = new Cache($storage4);

// Add expired item directly to database
$reflection4 = new ReflectionClass($storage4);
$pdoProperty4 = $reflection4->getProperty('pdo');
$pdoProperty4->setAccessible(true);
$pdo4 = $pdoProperty4->getValue($storage4);

$pdo4->prepare('INSERT INTO cache (key, data, expire) VALUES (?, ?, ?)')
     ->execute([$key, serialize($value), time() - 100]); // Expired 100 seconds ago

// Verify item exists
$stmt = $pdo4->prepare('SELECT COUNT(*) FROM cache WHERE key = ?');
$stmt->execute([$key]);
$countBefore = $stmt->fetchColumn();
Assert::same(1, (int)$countBefore, 'Expired item should exist before cleaning');

// Create new storage instance with 100% probability - should clean
$storage5 = new SQLiteStorage($tempFile);

// Check that expired item was cleaned
$reflection5 = new ReflectionClass($storage5);
$pdoProperty5 = $reflection5->getProperty('pdo');
$pdoProperty5->setAccessible(true);
$pdo5 = $pdoProperty5->getValue($storage5);

$stmt = $pdo5->prepare('SELECT COUNT(*) FROM cache WHERE key = ?');
$stmt->execute([$key]);
$countAfter = $stmt->fetchColumn();
Assert::same(0, (int)$countAfter, 'Expired item should be cleaned when gcProbability = 1.0');

// Test 3: Test that non-expired items are not cleaned
SQLiteStorage::$gcProbability = 1.0;
$tempFile2 = tempnam(sys_get_temp_dir(), 'nette_cache_test2');
$storage6 = new SQLiteStorage($tempFile2);
$cache6 = new Cache($storage6);

// Save non-expired item
$cache6->save($key, $value, [
    Cache::Expire => time() + 3600, // Expires in 1 hour
]);

// Create new storage instance - should trigger cleaning but not remove non-expired items
$storage7 = new SQLiteStorage($tempFile2);
$cache7 = new Cache($storage7);

// Non-expired item should still be loadable
Assert::same($value, $cache7->load($key), 'Non-expired item should remain after cleaning');

// Cleanup
unlink($tempFile);
unlink($tempFile2);

// Reset gcProbability
SQLiteStorage::$gcProbability = 0.001;