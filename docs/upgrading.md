# Upgrading nette/caching

## 4.0.0

- **Removed** `Nette\Caching\Storage::lock()`: a storage that generates a missing item in only one process at a time implements `Nette\Caching\LockingReader::readOrGenerate()`, which runs the generator under its own lock; the method of a custom storage is no longer called (bcaec61b)
- **Removed** `Nette\Caching\Storages\FileStorage::lock()`: the item is locked by `Nette\Caching\Storages\FileStorage::readOrGenerate()`, which `Nette\Caching\Cache::load()` calls with the generator; affects code calling it directly and subclasses overriding it, whose method is no longer called (bcaec61b)
- **Removed** `Nette\Caching\Storages\MemcachedStorage::lock()`: it did nothing, drop the call (bcaec61b)
- **Removed** `Nette\Caching\Storages\SQLiteStorage::lock()`: it did nothing, drop the call (bcaec61b)
- **Removed** `Nette\Caching\Storages\MemoryStorage::lock()`: it did nothing, drop the call (bcaec61b)
- **Removed** `Nette\Caching\Storages\DevNullStorage::lock()`: it did nothing, drop the call (bcaec61b)
- **Removed** `Nette\Bridges\CacheLatte\Nodes\CacheNode::$endLine`: the range of the closing tag is the last item of `Latte\Compiler\Nodes\StatementNode::$tagRanges`, inherited from Latte 3.1 (2d9e55c7)
- **Removed** the service alias `cacheStorage`: use the service `cache.storage` or autowire `Nette\Caching\Storage` (cb9e002c)
- **Removed** the service alias `nette.cacheJournal`: use the service `cache.journal` or autowire `Nette\Caching\Storages\Journal` (cb9e002c)
- **Renamed** `Nette\Caching\IStorage` → `Nette\Caching\Storage` (alias since 3.1.0); the old name was removed (8420d0bc)
- **Renamed** `Nette\Caching\Storages\IJournal` → `Nette\Caching\Storages\Journal` (alias since 3.1.0); the old name was removed (8420d0bc)
- **Renamed** `Nette\Caching\IBulkReader` → `Nette\Caching\BulkReader` (alias since 3.1.0); the old name was removed (8420d0bc)
- **Deprecated** `Nette\Caching\Cache::PRIORITY` (deprecated since 3.2.2): use `Nette\Caching\Cache::Priority`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::EXPIRATION` (deprecated since 3.2.2): use `Nette\Caching\Cache::Expire`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::EXPIRE` (deprecated since 3.2.2): use `Nette\Caching\Cache::Expire`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::SLIDING` (deprecated since 3.2.2): use `Nette\Caching\Cache::Sliding`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::TAGS` (deprecated since 3.2.2): use `Nette\Caching\Cache::Tags`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::FILES` (deprecated since 3.2.2): use `Nette\Caching\Cache::Files`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::ITEMS` (deprecated since 3.2.2): use `Nette\Caching\Cache::Items`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::CONSTS` (deprecated since 3.2.2): use `Nette\Caching\Cache::Constants`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::CALLBACKS` (deprecated since 3.2.2): use `Nette\Caching\Cache::Callbacks`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::NAMESPACES` (deprecated since 3.2.2): use `Nette\Caching\Cache::Namespaces`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::ALL` (deprecated since 3.2.2): use `Nette\Caching\Cache::All`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::NAMESPACE_SEPARATOR` (deprecated since 3.4.3): use `Nette\Caching\Cache::NamespaceSeparator`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** `Nette\Caching\Cache::start()` (deprecated since 3.1.0): use `Nette\Caching\Cache::capture()`; using the old name triggers E_USER_DEPRECATED (58813ed5)
- **Deprecated** a `\Closure` as `$data` of `Nette\Caching\Cache::save()`: pass it as the generator of `Nette\Caching\Cache::load()`; the closure is still invoked, but no longer while the item is locked; using it triggers E_USER_DEPRECATED (bcaec61b)
- **Signature** `Nette\Caching\Storage::read()`: the return type `mixed` is now declared; affects implementations (230dd929)
- **Signature** `Nette\Caching\Cache::clean()`: takes the new optional parameters `$tags`, `$priority`, `$namespaces` and `$all`; affects subclasses overriding it, which must declare them as well (c2435c33)
- **Signature** `$dependencies` of `Nette\Caching\Cache::load()`, `Nette\Caching\Cache::save()`, `Nette\Caching\Cache::bulkSave()`, `Nette\Caching\Cache::wrap()` and `Nette\Caching\OutputHelper::end()` also accepts `Nette\Caching\Dependencies`; affects subclasses overriding them, which must widen the type (6e6b9f03)
- **Changed** `Nette\Caching\Cache::save()` throws `Nette\InvalidArgumentException` for an unknown key in `$dependencies` or a condition of `Nette\Caching\Cache::clean()` such as `Nette\Caching\Cache::Namespaces`, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\Cache::bulkSave()` throws `Nette\InvalidArgumentException` for an unknown key in `$dependencies` or a condition of `Nette\Caching\Cache::clean()`, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\Cache::load()` throws `Nette\InvalidArgumentException` for an unknown key or a condition of `Nette\Caching\Cache::clean()` in `$dependencies` or in the dependencies set by the generator, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\Cache::bulkLoad()` throws `Nette\InvalidArgumentException` for an unknown key or a condition of `Nette\Caching\Cache::clean()` in the dependencies set by the generator, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\Cache::wrap()`: the returned closure throws `Nette\InvalidArgumentException` for an unknown key or a condition of `Nette\Caching\Cache::clean()` in `$dependencies`, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\OutputHelper::end()` throws `Nette\InvalidArgumentException` for an unknown key or a condition of `Nette\Caching\Cache::clean()` in `$dependencies` or in `Nette\Caching\OutputHelper::$dependencies`, and `TypeError` for a value of a wrong type; previously such dependencies were passed on to the storage, which ignored them (6e6b9f03)
- **Changed** `Nette\Caching\Cache::clean()` throws `Nette\InvalidArgumentException` for an unknown key in `$conditions` or a dependency such as `Nette\Caching\Cache::Expire`, and `TypeError` for a value of a wrong type such as a numeric string priority; previously such conditions were passed on to the storage, which ignored them (e95c064d)
- **Changed** `Nette\Caching\Cache::clean()` called without any condition throws `Nette\InvalidArgumentException`; previously it passed an empty array to the storage, which `Nette\Caching\Storages\FileStorage` and `Nette\Caching\Storages\SQLiteStorage` took as a request to remove expired items; for garbage collection call `Nette\Caching\Storage::clean()` with an empty array (33108087)
- **Changed** `Nette\Caching\Cache::clean()` with `all: true` or `Nette\Caching\Cache::All` on a cache with a namespace removes only that namespace and the namespaces derived from it; previously it cleared the entire storage; to clear everything call it on a `Nette\Caching\Cache` without a namespace or pass `Nette\Caching\Cache::All` to `Nette\Caching\Storage::clean()`; with `Nette\Caching\Storages\MemcachedStorage` it throws `Nette\NotSupportedException` (c3f661a8)
- **Changed** `Nette\Caching\Storages\FileStorage::clean()` with `Nette\Caching\Cache::Namespaces` also removes the items of the namespaces derived from them by `Nette\Caching\Cache::derive()`; previously only the items of the namespace itself (150fa68d)
- **Changed** `Nette\Caching\Storages\FileStorage::$gcProbability` defaults to 0.01; previously 0.001; when it hits, the constructor runs a garbage collector batch limited to 20 ms instead of walking the whole cache; call `Nette\Caching\Storages\FileStorage::clean()` with an empty array to collect the whole cache (b5a83af9)
- **Changed** `Nette\Caching\Storages\MemcachedStorage::write()` triggers `E_USER_WARNING` when storing the item fails; previously the failure was silently ignored (a15e5b1c)
- **Changed** `Nette\Caching\Storages\MemcachedStorage::clean()` throws `Nette\NotSupportedException` for `Nette\Caching\Cache::Namespaces`; previously the condition was silently ignored (1fed5d97)
- **Changed** `Nette\Caching\Storages\SQLiteStorage::write()` throws `Nette\NotSupportedException` for the dependencies `Nette\Caching\Cache::Priority`, `Nette\Caching\Cache::Items` and `Nette\Caching\Cache::Callbacks`, so also for `Nette\Caching\Cache::Files` and `Nette\Caching\Cache::Constants`, which `Nette\Caching\Cache` turns into callbacks; previously they were silently ignored (1fed5d97)
- **Changed** `Nette\Caching\Storages\SQLiteStorage::clean()` throws `Nette\NotSupportedException` for `Nette\Caching\Cache::Priority`; previously the condition was silently ignored (1fed5d97)
- **Changed** `Nette\Bridges\Psr\PsrCacheAdapter` validates keys as PSR-16 requires: an empty key or a key containing any of `{}()/\@:` throws `Nette\Bridges\Psr\InvalidArgumentException`; previously any key was passed on to the storage; affects keys such as `user:1` (c1950a4e)

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
