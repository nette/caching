# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nette Caching is a PHP library providing flexible caching with multiple storage backends and advanced dependency tracking. It's part of the Nette Framework ecosystem.

**Key features:**
- Multiple storage backends (FileStorage, MemcachedStorage, SQLiteStorage, MemoryStorage)
- Advanced dependency tracking (tags, priorities, file changes, callbacks)
- Cache stampede prevention in FileStorage
- Atomic operations with file locking
- PSR-16 SimpleCache adapter
- Latte template integration with `{cache}` tag
- Nette DI integration

**Requirements:** PHP 8.1-8.5

## Essential Commands

### Testing

```bash
# Run all tests
vendor/bin/tester tests -s

# Run specific test directory
vendor/bin/tester tests/Caching -s
vendor/bin/tester tests/Storages -s

# Run single test file
php tests/Caching/Cache.bulkLoad.phpt
```

### Static Analysis

```bash
# Run PHPStan (level 5)
composer run phpstan

# Or directly
vendor/bin/phpstan analyse
```

### Linting

```bash
# Nette coding standard checks
composer run tester
```

## Architecture Overview

### Core Layering

The library follows a clean separation of concerns:

```
Cache (high-level API)
  ↓
Storage interface (abstraction)
  ↓
Storage implementations (FileStorage, MemcachedStorage, etc.)
  ↓
Journal interface (for tags/priorities)
  ↓
SQLiteJournal implementation
```

**Cache** (`src/Caching/Cache.php`): Primary API for caching operations. Provides namespace isolation, dependency tracking, memoization (`wrap()`, `call()`), and output capturing (`capture()`, was `start()` in v3.0).

**Storage interface** (`src/Caching/Storage.php`): Defines the contract all storage backends must implement:
- `read(string $key): mixed`
- `write(string $key, $data, array $dependencies): void`
- `remove(string $key): void`
- `clean(array $conditions): void`
- `lock(string $key): void` - Prevents concurrent writes

**Journal interface** (`src/Caching/Storages/Journal.php`): Tracks metadata for tags and priorities. Required for:
- `Cache::Tags` - Tag-based invalidation
- `Cache::Priority` - Priority-based cleanup

Default implementation: SQLiteJournal using SQLite database at `{tempDir}/journal.s3db`.

### Storage Implementations

All in `src/Caching/Storages/`:

- **FileStorage** - Production default. Files stored in temp directory with atomic operations via file locking (LOCK_SH for reads, LOCK_EX for writes). Implements cache stampede prevention: when cache miss occurs with concurrent requests, only first thread generates value, others wait. File format: 6-byte header with meta size + serialized metadata + data.

- **SQLiteStorage** - Single-file database storage. Good for shared hosting environments.

- **MemcachedStorage** - Distributed caching via Memcached server. Requires `memcached` PHP extension.

- **MemoryStorage** - In-memory array storage, lost after request. Used for testing or request-scoped caching.

- **DevNullStorage** - No-op storage for testing when you want to disable caching.

### Dependency System

Cache dependencies control expiration and invalidation. All use Cache class constants:

- `Cache::Expire` - Time-based expiration (timestamp, seconds, or string like "20 minutes")
- `Cache::Sliding` - Extends expiration on each read
- `Cache::Files` - Invalidate when file(s) modified (checks filemtime)
- `Cache::Items` - Invalidate when other cache items expire
- `Cache::Tags` - Tag-based invalidation (requires Journal)
- `Cache::Priority` - Priority-based cleanup (requires Journal)
- `Cache::Callbacks` - Custom validation callbacks
- `Cache::Constants` - Invalidate when PHP constants change

Dependencies can be combined; cache expires when ANY criterion fails.

### Bridge Components

**Nette DI Bridge** (`src/Bridges/CacheDI/CacheExtension.php`):
- Auto-registers Storage service (FileStorage by default)
- Auto-registers Journal service (SQLiteJournal if pdo_sqlite available)
- Validates and creates temp directory
- Services registered: `cache.storage`, `cache.journal`

**Latte Bridge** (`src/Bridges/CacheLatte/`):
- Provides `{cache}` tag for template caching
- Runtime in `Runtime.php` manages cache lifecycle
- Node compilation in `Nodes/CacheNode.php`
- Automatic invalidation when template source changes
- Supports parameters: `{cache $id, expire: '20 minutes', tags: [tag1, tag2]}`
- Can be conditional: `{cache $id, if: !$form->isSubmitted()}`

**PSR-16 Bridge** (`src/Bridges/Psr/PsrCacheAdapter.php`):
- Adapts Nette Storage to PSR-16 SimpleCache interface
- Used for PSR compatibility in third-party integrations

### Bulk Operations

Two specialized classes enable efficient bulk operations:

- **BulkReader** (`src/Caching/BulkReader.php`) - Interface for storages supporting bulk reads
- **BulkWriter** (`src/Caching/BulkWriter.php`) - Interface for storages supporting bulk writes

Used by `Cache::bulkLoad()` and `Cache::bulkSave()` to reduce storage round-trips.

## Testing Structure

Tests organized by component in `tests/`:
- `Caching/` - Cache class tests
- `Storages/` - Storage implementation tests
- `Bridges.DI/` - Nette DI integration tests
- `Bridges.Latte3/` - Latte 3.x template caching tests
- `Bridges.Psr/` - PSR-16 adapter tests

Test utilities:
- `bootstrap.php` - Test environment setup with `test()` helper function
- `getTempDir()` - Creates isolated temp directory per test process
- Uses Nette Tester with `.phpt` format

## Development Notes

### File Locking Strategy (FileStorage)

Three atomic operation types documented in FileStorage.php:
1. **Reading**: open(r+b) → lock(LOCK_SH) → read → close
2. **Deleting**: unlink, if fails lock(LOCK_EX) → truncate → close → unlink
3. **Writing**: open(r+b or wb) → lock(LOCK_EX) → truncate → write data → write meta → close

This ensures atomicity on both NTFS and ext3 filesystems.

### Cache Stampede Prevention

FileStorage prevents cache stampede through locking: when multiple concurrent threads request non-existent cache item, `lock()` ensures only first thread generates value while others wait. Others then use the generated result.

### Namespace Handling

Cache uses internal null byte separator (`Cache::NamespaceSeparator = "\x00"`) to isolate namespaces. Keys are prefixed with `{namespace}\x00{key}`.

### Constants Naming

Library uses modern PascalCase constants (e.g., `Cache::Expire`) with deprecated UPPERCASE aliases (e.g., `Cache::EXPIRATION`) for backward compatibility.

**Version 3.0 compatibility note:** In version 3.0, the Storage interface was named `IStorage` (with `I` prefix) and constants were UPPERCASE (e.g., `Cache::EXPIRE` instead of `Cache::Expire`).

## Using Cache in Code

Two approaches for dependency injection:

**Approach 1: Inject Storage, create Cache manually**
```php
class ClassOne
{
	private Nette\Caching\Cache $cache;

	public function __construct(Nette\Caching\Storage $storage)
	{
		$this->cache = new Nette\Caching\Cache($storage, 'my-namespace');
	}
}
```

**Approach 2: Inject Cache directly**
```php
class ClassTwo
{
	public function __construct(
		private Nette\Caching\Cache $cache,
	) {
	}
}
```

Configuration for Approach 2:
```neon
services:
	- ClassTwo( Nette\Caching\Cache(namespace: 'my-namespace') )
```

## DI Services

Services automatically registered by CacheExtension:

| Service Name | Type | Description |
|--------------|------|-------------|
| `cache.storage` | `Nette\Caching\Storage` | Primary cache storage (FileStorage by default) |
| `cache.journal` | `Nette\Caching\Storages\Journal` | Journal for tags/priorities (SQLiteJournal, requires pdo_sqlite) |

## Configuration Examples

### Change Storage Backend

```neon
services:
	cache.storage: Nette\Caching\Storages\DevNullStorage
```

### Use MemcachedStorage

```neon
services:
	cache.storage: Nette\Caching\Storages\MemcachedStorage('10.0.0.5')
```

### Use SQLiteStorage

```neon
services:
	cache.storage: Nette\Caching\Storages\SQLiteStorage('%tempDir%/cache.db')
```

### Custom Journal

```neon
services:
	cache.journal: MyJournal
```

### Disable Caching (for testing)

```neon
services:
	cache.storage: Nette\Caching\Storages\DevNullStorage
```

**Note:** This doesn't affect Latte template caching or DI container caching, as those are managed independently and [don't need to be disabled during development](https://doc.nette.org/troubleshooting#How-to-Disable-Cache-During-Development).

## PSR-16 Usage

The `PsrCacheAdapter` provides PSR-16 SimpleCache compatibility (available since v3.3.1):

```php
$psrCache = new Nette\Bridges\Psr\PsrCacheAdapter($storage);

// PSR-16 interface
$psrCache->set('key', 'value', 3600);
$value = $psrCache->get('key', 'default');

// Supports all PSR-16 methods
$psrCache->getMultiple(['key1', 'key2']);
$psrCache->setMultiple(['key1' => 'val1', 'key2' => 'val2']);
$psrCache->deleteMultiple(['key1', 'key2']);
```
