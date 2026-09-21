# Caching internals

How `nette/caching` works underneath, for agents editing it. Medium depth, one
file: `Cache` → `Storage` → `Journal` is a single layered mechanism, and the traps
(overloaded `null`, the lock around the generator, the FileStorage format) only
make sense together.

## The three layers and where responsibility sits

- **`Cache`** is the high-level, storage-agnostic API: namespacing, dependency
  normalization, memoization, output capture. It never touches files or sockets.
- **`Storage`** (interface) is the backend contract: `read`/`write`/`remove`/
  `clean` (+ optional `BulkReader`/`BulkWriter` and `LockingReader`).
- **`Journal`** (interface; the built-in implementation is `SQLiteJournal`) is an
  **optional** side-index for tags and priorities. `FileStorage`,
  `MemcachedStorage` and `Psr16Storage` need one **only** for `Tags`/`Priority`
  and throw if a write uses them without a journal; `SQLiteStorage` never uses a
  journal (it has its own `tags` table); everything else works journal-less.
  `Journal::clean()` returns the list of keys to delete, **or `null` meaning
  "full cleanup, the storage handles `All` itself"** — hence `clean(...) ?? []`
  in the storages; don't confuse `null` with an empty list.

Keys are namespaced by `Cache`: the namespace has a `NamespaceSeparator`
(`"\x00"`) appended, and `generateKey` = `namespace . xxh128(key)`. `derive()`
nests namespaces by concatenation. FileStorage later turns the `"\x00"` into a
subdirectory (`getCacheFile`), so a namespace is a directory on disk — but only
the **last** separator (`strrpos`), so nested namespaces are a single directory
whose name still contains `%00`, and the empty root namespace (separator at
position 0) creates no directory at all. Cleaning by `Namespaces` removes a
namespace **together with the nested ones** in every storage that supports it:
FileStorage deletes its directory and those whose name continues with `%00`,
SQLite the key range, Redis the `SCAN` of the prefix.

## `null` is overloaded; `save($key, null)` **is** `remove()`

`remove()` is literally `save($key, null)`, and `null` carries three meanings:

- from `read()`/`load()` → **cache miss**;
- as data passed to `save()` → **delete this key**;
- an `Expire <= 0` (already expired) → **delete instead of write**.

So a generator that returns `null` does not cache a null — it removes the key.
Do not add a code path that stores `null` as a value; the whole layer assumes
`null` means absence.

`\Closure` is overloaded the same way: `save($key, $closure)` does not store the
closure, it **runs** it (deprecated, without any lock) and stores the result. A
closure can never be cached as a value.

The PSR-16 adapter (`PsrCacheAdapter`) inherits this: it wraps the **`Storage`**
directly, not `Cache` — keys go to the backend raw (no namespace, no hashing) —
and `null` still means miss, so `set($key, null)` stores a value that `get()`/
`has()` can never distinguish from absence. A `DateInterval` TTL is converted to
seconds via UTC on purpose, so a DST transition cannot skew the count.

## Dependencies are rewritten before they reach the storage

`completeDependencies()` normalizes the user's dependency array so that storages
see a **smaller, uniform** set — this is why the "which backend supports which
dependency" matrix is partly an illusion:

- **`Files` and `Constants` are desugared into `Callbacks`** (a `checkFile`/
  `checkConst` closure with an mtime/value snapshot). Storages never see `Files`
  or `Constants` — so those two work on **every** backend that supports
  `Callbacks`.
- `Expire` is converted to a **relative second count** (`DateTime::from - time()`).
- `Items` keys are run through `generateKey` (namespaced); `Tags`/`Namespaces`
  are flattened to lists.

What genuinely needs backend support is therefore narrower than it looks, but
that support still varies: `Callbacks` (and thus `Files`/`Constants`) are honored
by `FileStorage`, `MemcachedStorage`, `RedisStorage` and `Psr16Storage`;
`Tags`/`Priority` need a `Journal` in `FileStorage`, `MemcachedStorage` and
`Psr16Storage`, while `RedisStorage`, `MemoryStorage` and (`Tags` only)
`SQLiteStorage` keep them natively. What a backend cannot honor, it rejects with
`NotSupportedException` — except `MemoryStorage`, which **silently drops**
`Callbacks` and `Items`. An agent adding a backend must reason about this
**post-desugar** set, not the public constant list.

## Conditions of `clean()`

`Cache::clean()` takes the conditions as named arguments or as an array with
`Cache::*` keys, never both at once. `convertConditions()` turns the array into
the named arguments, which closes its format: an unknown key or a dependency
such as `Expire` throws `InvalidArgumentException`, a value of a wrong type
`TypeError`, and a single tag or namespace is wrapped into a list. The values of
`Cache::Tags`, `Priority`, `Namespaces` and `All` are the names of the
parameters, so the conversion is a spread; checking the keys first is what makes
it safe, a numeric key would otherwise become a positional argument. `null`
means a condition is not given, while an empty list is a given condition that
matches nothing: `clean(tags: [])` returns without reaching the storage, so a
computed list of tags that happens to be empty does nothing. A call without any
condition throws: it reads as clearing the cache, while a storage takes an empty
array as a request to collect garbage, which therefore stays a matter of
`Storage::clean([])`.

**`all` removes what the `Cache` object sees.** A `Cache` with a namespace sends
the storage `Namespaces` with its own namespace instead of `All`, so the
namespaces derived from it go too and the others stay; only a `Cache` without a
namespace sends `All` and clears the entire storage. `MemcachedStorage` and
`Psr16Storage` cannot clean a namespace, so `all` on a `Cache` with a namespace
throws `NotSupportedException` there.

## The generator runs under the storage's lock (and only some storages lock)

`Cache::load()` with a generator hands the storage a callback when it implements
`LockingReader`, otherwise it does `read() ?? callback()`. The callback runs the
user's generator and stores the result through `Cache::save()`, so the meaning of
`null`, `Expire` and the desugared dependencies stays in `Cache`. The invariants:

- **The callback writes through `write()` or `remove()` of the same storage while
  the lock is held**, and the storage releases the lock in `finally`. A
  `LockingReader` whose `write()` cannot work under its own lock deadlocks.
- `FileStorage::readOrGenerate()` reads, opens the file `c+b`, takes
  `flock(LOCK_EX)` and keeps the handle in `$locks[$key]`; `write()` **reuses that
  same handle** (removing it from `$locks`) and unlocks at the end. Before
  calling the generator it **reads again through the locked handle**: a process
  that found the item missing while another was generating it waits for the lock
  and returns the fresh value instead of generating it twice. The re-read must not go through
  `read()` (a second handle of the same file blocks on the process's own lock)
  and must not delete an invalid item, which would close the locked handle; the
  write truncates it anyway, after `rewind()`, as the re-read moved the pointer.
- **An item's version (`MetaTime`) is assigned when the re-read misses, right
  before the generator runs, not by `write()`.** An `Items` dependency on a key
  the same instance is generating takes that version from `$versions` instead of
  reading the file, which it holds `LOCK_EX` and would wait for forever (ADR
  `item-version-at-lock`). Assigning it before the re-read would judge the items
  depending on the old version against the new one. Any new code that reads
  dependency metadata must go through the same check.
- A generator that fails stores nothing, so `finally` deletes the file `c+b`
  created; an empty file would stay forever, the GC does not recognize it as
  expired. A generator that generates its own key gets `InvalidStateException`,
  one that only reads it gets a miss; both would otherwise wait for the
  process's own lock.
- `RedisStorage::readOrGenerate()` takes the lock as the key `"\x01l:" . $key`
  (`SET NX PX`, TTL `$lockTimeout`) holding a random token, reads again, and
  releases it by a Lua script only while it still holds its token: a lock that
  expired during a long generation and was taken over is not deleted. A process
  that does not get the lock polls the item until the lock disappears, then
  takes it itself; after `$lockTimeout` it generates without it. Generation
  longer than the TTL therefore means duplicate work, never a wrong value.
- **Stampede protection is real only for `FileStorage` and `RedisStorage`.** No
  other built-in storage implements `LockingReader` (`MemoryStorage`,
  `Memcached`, `SQLite`, `Psr16`, `DevNull`), so concurrent
  `load($key, $generator)` calls can all generate at once there. "Nette caching
  prevents stampede" is a guarantee of those two, not a universal one.

## FileStorage on-disk format and atomic writes

The file layout is: **a 6-byte zero-padded meta-length header + serialized meta +
data**. Data other than a string go through `serializeData()`, which a subclass
may override (igbinary); the meta always uses `serialize()`, as
`readMetaAndLock()` recognizes foreign files by it. The write sequence is the
subtle part and is a commit protocol:

1. under the held `LOCK_EX`, `ftruncate(0)`;
2. write a **zero-filled** header placeholder, then the data;
3. `fseek(0)` and write the **real** header **last**.

Writing the real (non-zero) header last means a reader that sees a non-zero size
is guaranteed a complete record — the header doubles as the commit marker. Reads
take `LOCK_SH`; the class docblock enumerates the atomic operations
(read/delete/write/generate) and the **NTFS-vs-ext3 delete fallback**: `unlink` can fail on
a locked NTFS file, so `delete()` falls back to `lock(EX) → truncate → close →
unlink`.

Two more non-obvious behaviors:

- **Reads self-heal.** `verify()` checks expiration, sliding (`touch`es the file to
  extend it), callbacks, and — **recursively** — `Items` dependencies (each
  dependent file's `MetaTime` must still match). Any failure **deletes** the entry
  on the spot, so a stale read is also a cleanup.
- **The constructor runs a bounded GC batch, `clean([])` a full one.** With
  probability `$gcProbability` (0.01) the constructor collects one random
  directory (the root or a namespace), from a random file, for at most
  `GcBatchBudget` (20 ms), skipping files other processes hold (`LOCK_NB`), so no
  request pays for walking the whole cache. The cost of a batch beyond the budget
  is the `scandir()` of the root and of the chosen directory; root files of items
  without namespace are named `_%00` and the 32 digits of the hash, so only the
  other names need `is_dir()`. Checking just the `%00` prefix would take the
  directory of a namespace derived from the root one (`_%00sub`) for a file.
  An explicit `clean([])` still walks everything and waits for locks.

## Backends honor different subsets of dependencies

The built-in storages are `FileStorage`, `SQLiteStorage`, `MemcachedStorage`,
`RedisStorage`, `MemoryStorage`, and `DevNullStorage`, plus `Psr16Storage` in
`Bridges\Psr` (others live outside this package). They do **not** all honor the
same dependencies, and the weakest are easy traps:

- **`MemoryStorage`** honors `Expire`/`Sliding`, `Tags`, `Priority` and cleaning
  by `Namespaces` in a process-local map, but **silently drops** `Callbacks` — so
  `Files`/`Constants` do nothing there — and `Items`. A trap when it stands in for
  a real backend where file dependencies matter.
- **`SQLiteStorage`** supports `Expire`/`Sliding`, `Tags` (its own `tags` table,
  no journal) and cleaning by `Namespaces`, and throws for `Callbacks` (so
  `Files`/`Constants`), `Priority`, `Items` and cleaning by `Priority`. A
  namespace is cleaned by the key range `namespace\x00` .. `namespace\x01`, not by
  `LIKE`: `LIKE` cuts the pattern at the NUL byte of the separator and treats `_`
  and `%` in a namespace as wildcards. Expired rows are removed by `clean([])`,
  run probabilistically by the constructor like in `FileStorage`.
- **`MemcachedStorage`** honors `Expire`/`Sliding`/`Callbacks`, needs a `Journal`
  for `Tags`/`Priority` (`InvalidStateException` without one), throws
  `NotSupportedException` on `Items` and cleaning by `Namespaces`, and its
  `clean(All)` calls `flush()` — wiping the **entire memcached server**, key prefix
  notwithstanding. A failed `set()` (an item over the size limit) triggers
  `E_USER_WARNING`; `setMulti()` returns success even when an item was not
  stored, so `bulkWrite()` cannot tell.
- **`RedisStorage`** honors `Expire`/`Sliding`/`Callbacks`, natively `Tags` and
  `Priority` (auxiliary keys prefixed `"\x01"`, no journal) and cleaning by
  `Namespaces` (a `SCAN` of the key prefix), and throws on `Items`. Its
  `clean(All)` deletes only keys with its prefix (default `nette.cache:`); an
  empty prefix makes it `flushDB()`. The set of tags of an item
  (`"\x01k:"`) does **not** expire with it, so that a later write of the same key
  still finds the old tags and removes it from their indexes; otherwise cleaning
  by an old tag would delete the new value. Expired items leave these sets and
  their index entries behind until `clean([])`, run probabilistically by the
  constructor, removes them; it does so in Lua scripts, because checking and
  removing in two steps would drop the entries of a key written in between. Every
  value starts with 8 random bytes, its version; a sliding read extends the TTL
  only while the item still has the version it read (Lua, `GETRANGE`), so an item
  written in between keeps its own expiration.
- **`Psr16Storage`** puts any PSR-16 cache under Nette `Cache`: it honors
  `Expire`/`Callbacks`, needs a `Journal` for `Tags`/`Priority`, and throws for
  `Items`, cleaning by `Namespaces` and `Sliding` — PSR-16 cannot extend a TTL,
  and reading the item and writing it back would restore one removed in between.
  Keys are hashed (xxh128) into the charset PSR-16 guarantees. PSR-16 cannot list
  keys, so its `clean(All)` calls `clear()` on the whole underlying cache —
  **wiping foreign data sharing it**, prefix notwithstanding.
- **`DevNullStorage`** discards everything (always a miss).
- `FileStorage` is the reference implementation with the fullest support (via the
  meta header + optional `Journal`).

When adding or choosing a backend, verify the specific storage rather than
assuming the public dependency constants are universally honored.

## Navigation map

| Concern | Where |
|---|---|
| Namespacing, key hashing | `Cache::generateKey`, `NamespaceSeparator`, `derive` |
| `null` = remove, expire semantics | `Cache::save`, `remove` |
| Dependency desugaring | `Cache::completeDependencies` |
| Generator under the lock | `LockingReader` (doc), `Cache::load`, `FileStorage::readOrGenerate`/`write` |
| On-disk format, commit-last header | `FileStorage::write`, `readMetaAndLock` |
| Read-time verify + self-delete | `FileStorage::verify` |
| Namespace→subdir, GC | `FileStorage::getCacheFile`, `collectBatch`, `clean` |
| PSR-16 (raw keys, `null` = miss) | `Bridges/Psr/PsrCacheAdapter` |
| Latte `{cache}`: nesting → `Items`, template file → `Files` | `Bridges/CacheLatte/Runtime` |
