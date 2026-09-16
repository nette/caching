# Upgrading nette/caching

## 3.4.3

- **Renamed** `$dp` of `Nette\Caching\Storages\FileStorage::write()` → `$dependencies`; affects named-argument calls; the old name was removed (77c07f41)
- **Renamed** `$dp` of `Nette\Caching\Storages\MemcachedStorage::write()` → `$dependencies`; affects named-argument calls; the old name was removed (77c07f41)
- **Renamed** `$dp` of `Nette\Caching\Storages\MemcachedStorage::bulkWrite()` → `$dependencies`; affects named-argument calls; the old name was removed (77c07f41)
- **Deprecated** `Nette\Caching\Cache::NAMESPACE_SEPARATOR` (alias since 3.1.4): use `Nette\Caching\Cache::NamespaceSeparator`; the old name is silently deprecated (729ab60c)

## 3.4.0

- **Removed** `Nette\Bridges\CacheLatte\CacheMacro`: support for Latte 2 is gone, register the Latte 3 extension `Nette\Bridges\CacheLatte\CacheExtension` instead (c8a36dee)
- **Changed** `Nette\Caching\Cache::generateKey()` now hashes the key with xxh128; previously md5, so every previously stored item becomes unreachable and the whole cache is effectively invalidated on upgrade (76181d11)

## 3.3.0

- **Changed** `Nette\Caching\Storages\FileStorage::__construct()` now throws `Nette\DirectoryNotFoundException` when `$dir` is not an absolute path; previously a relative path was accepted (ec73e13b)
- **Changed** `Nette\Caching\Cache` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\OutputHelper` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\FileStorage` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\MemcachedStorage` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\MemoryStorage` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\DevNullStorage` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\SQLiteStorage` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)
- **Changed** `Nette\Caching\Storages\SQLiteJournal` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (d3e75f6b)

## 3.2.2

- **Removed** `Nette\Caching\Storages\FileStorage::$useDirectories` (deprecated since 3.0.0): the storage always creates subdirectories, there is no replacement (bb4fe737)
- **Removed** `Nette\Caching\Storages\NewMemcachedStorage` (deprecated since 3.0.0): use `Nette\Caching\Storages\MemcachedStorage` (bb4fe737)
- **Replaced** the Latte provider `$this->global->cacheStorage`, a `Nette\Caching\Storage` instance, with `$this->global->cache`, a `Nette\Bridges\CacheLatte\Runtime` object with a different API; affects templates and custom Latte nodes reaching for the provider (ceb814d7)
- **Deprecated** `Nette\Caching\Cache::PRIORITY` (alias since 3.1.3): use `Nette\Caching\Cache::Priority`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::EXPIRATION` (alias since 3.1.3): use `Nette\Caching\Cache::Expire`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::EXPIRE` (alias since 3.1.3): use `Nette\Caching\Cache::Expire`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::SLIDING` (alias since 3.1.3): use `Nette\Caching\Cache::Sliding`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::TAGS` (alias since 3.1.3): use `Nette\Caching\Cache::Tags`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::FILES` (alias since 3.1.3): use `Nette\Caching\Cache::Files`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::ITEMS` (alias since 3.1.3): use `Nette\Caching\Cache::Items`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::CONSTS` (alias since 3.1.3): use `Nette\Caching\Cache::Constants`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::CALLBACKS` (alias since 3.1.3): use `Nette\Caching\Cache::Callbacks`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::NAMESPACES` (alias since 3.1.3): use `Nette\Caching\Cache::Namespaces`; the old name is silently deprecated (a8e7a108)
- **Deprecated** `Nette\Caching\Cache::ALL` (alias since 3.1.3): use `Nette\Caching\Cache::All`; the old name is silently deprecated (a8e7a108)

## 3.1.4

- **Changed** `Nette\Caching\Cache::NAMESPACE_SEPARATOR` exists again as an alias of `Nette\Caching\Cache::NamespaceSeparator` (marked @internal, removed in 3.1.3); do not rely on it, it is not public API (b516eb3b)

## 3.1.3

- **Renamed** `Nette\Caching\Cache::PRIORITY` → `Nette\Caching\Cache::Priority`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::EXPIRATION` → `Nette\Caching\Cache::Expire`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::EXPIRE` → `Nette\Caching\Cache::Expire`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::SLIDING` → `Nette\Caching\Cache::Sliding`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::TAGS` → `Nette\Caching\Cache::Tags`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::FILES` → `Nette\Caching\Cache::Files`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::ITEMS` → `Nette\Caching\Cache::Items`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::CONSTS` → `Nette\Caching\Cache::Constants`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::CALLBACKS` → `Nette\Caching\Cache::Callbacks`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::NAMESPACES` → `Nette\Caching\Cache::Namespaces`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::ALL` → `Nette\Caching\Cache::All`; the old name still works as an alias (8bf74558)
- **Renamed** `Nette\Caching\Cache::NAMESPACE_SEPARATOR` → `Nette\Caching\Cache::NamespaceSeparator` (marked @internal); the old name was removed (8bf74558)

## 3.1.1

- **Changed** `Nette\Caching\Cache::save()` no longer triggers `E_USER_WARNING` when `$data` is a `\Closure`; previously it did (deprecated since 3.1.0); passing a closure is supported again (3e771c58)

## 3.1.0

- **Renamed** `Nette\Caching\IStorage` → `Nette\Caching\Storage`; the old name still works as an alias (d6c6cb55)
- **Renamed** `Nette\Caching\Storages\IJournal` → `Nette\Caching\Storages\Journal`; the old name still works as an alias (d6c6cb55)
- **Renamed** `Nette\Caching\IBulkReader` → `Nette\Caching\BulkReader`; the old name still works as an alias (d6c6cb55)
- **Renamed** `Nette\Caching\Cache::start()` → `Nette\Caching\Cache::capture()`; the old name is silently deprecated (60281abf)
- **Renamed** `$fallback` of `Nette\Caching\Cache::load()` → `$generator`; affects named-argument calls; the old name was removed (8804ccbd)
- **Renamed** `$fallback` of `Nette\Caching\Cache::bulkLoad()` → `$generator`; affects named-argument calls; the old name was removed (8804ccbd)
- **Changed** `Nette\Caching\Cache::save()` triggers `E_USER_WARNING` when `$data` is a `\Closure`; previously the closure was invoked silently; use `Nette\Caching\Cache::load()` with a generator callback instead; reverted in 3.1.1 (5ffe2637)

## 3.0.0

- **Removed** support for a `Nette\Callback` instance as `$data` of `Nette\Caching\Cache::save()`: such a value is no longer invoked and is cached as plain data, pass a `\Closure` or `Nette\Utils\Callback::toClosure()` (e83c1cd0)
- **Replaced** `Nette\Caching\Storages\MemcachedStorage`, built on the abandoned `ext-memcache`, with an implementation built on `ext-memcached`: the class name stays, but the `memcached` extension is now required (4a375062, aebaa0eb)
- **Renamed** `Nette\Caching\Storages\NewMemcachedStorage` → `Nette\Caching\Storages\MemcachedStorage`; the old name is silently deprecated (aebaa0eb)
- **Deprecated** `Nette\Caching\Storages\FileStorage::$useDirectories`: the storage always creates subdirectories, so the flag has no replacement; it is silently deprecated (077ab411)
- **Signature** `Nette\Caching\Storages\MemcachedStorage::addServer()`: the third parameter `$timeout` is gone and the method returns `void`; a timeout passed as the third argument is silently ignored, the connection uses the driver default (4a375062)
- **Signature** `Nette\Caching\Cache::getNamespace()`: declared `final`; affects subclasses overriding it (78c213dd)
- **Signature** `Nette\Caching\Cache::getStorage()`: declared `final`; affects subclasses overriding it (78c213dd)
- **Signature** `Nette\Bridges\CacheDI\CacheExtension`: declared `final`; affects subclasses (78c213dd)
- **Signature** native parameter and return types were added throughout the package, so a value of another type now throws a `TypeError` and an implementation or subclass must repeat the type: `Nette\Caching\IStorage::read()`, `Nette\Caching\IStorage::write()`, `Nette\Caching\IStorage::remove()`, `Nette\Caching\IStorage::clean()`, `Nette\Caching\IStorage::lock()`, `Nette\Caching\Storages\IJournal::write()`, `Nette\Caching\Storages\IJournal::clean()`, `Nette\Caching\IBulkReader::bulkRead()`, `Nette\Caching\Cache::__construct()`, `Nette\Caching\Cache::load()`, `Nette\Caching\Cache::bulkLoad()`, `Nette\Caching\Cache::call()`, `Nette\Caching\Cache::wrap()`, `Nette\Caching\Cache::derive()`, `Nette\Caching\Cache::clean()`, `Nette\Caching\Storages\FileStorage::__construct()`, `Nette\Caching\Storages\SQLiteStorage::__construct()`, `Nette\Caching\Storages\MemcachedStorage::__construct()`, `Nette\Bridges\CacheDI\CacheExtension::__construct()` (d1ffc287, ec293113, 7d024e7a)
- **Changed** `Nette\Caching\Storages\SQLiteJournal::__construct()` throws `Nette\NotSupportedException` right away when `ext-pdo_sqlite` is missing; previously the check happened lazily on first use (d84d5328)
- **Changed** `Nette\Caching\Storages\FileStorage` writes cache files without the leading `<?php //netteCache[01]` header; cache files written by an older version are read as invalid and regenerated; affects code reading the files directly (0e5d0699)
