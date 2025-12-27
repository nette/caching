# Caching internals

How `nette/caching` works underneath, for agents editing it. Medium depth, one
file: `Cache` → `Storage` → `Journal` is a single layered mechanism, and the traps
(overloaded `null`, the cross-method lock, the FileStorage format) only make sense
together.

## The three layers and where responsibility sits

- **`Cache`** is the high-level, storage-agnostic API: namespacing, dependency
  normalization, memoization, output capture. It never touches files or sockets.
- **`Storage`** (interface) is the backend contract: `read`/`write`/`remove`/
  `lock`/`clean` (+ optional `BulkReader`/`BulkWriter`).
- **`Journal`** (interface; the built-in implementation is `SQLiteJournal`) is an
  **optional** side-index for tags and priorities. `FileStorage` and
  `MemcachedStorage` need one **only** for `Tags`/`Priority` and throw if a write
  uses them without a journal; `SQLiteStorage` never uses a journal (it has its
  own `tags` table); everything else works journal-less. `Journal::clean()`
  returns the list of keys to delete, **or `null` meaning "full cleanup, the
  storage handles `All` itself"** — hence `?->clean(...) ?? []` in FileStorage;
  don't confuse `null` with an empty list.

Keys are namespaced by `Cache`: the namespace has a `NamespaceSeparator`
(`"\x00"`) appended, and `generateKey` = `namespace . xxh128(key)`. `derive()`
nests namespaces by concatenation. FileStorage later turns the `"\x00"` into a
subdirectory (`getCacheFile`), so a namespace is a directory on disk — but only
the **last** separator (`strrpos`), so nested namespaces are a single directory
whose name still contains `%00`, and the empty root namespace (separator at
position 0) creates no directory at all.

## `null` is overloaded; `save($key, null)` **is** `remove()`

`remove()` is literally `save($key, null)`, and `null` carries three meanings:

- from `read()`/`load()` → **cache miss**;
- as data passed to `save()` → **delete this key**;
- an `Expire <= 0` (already expired) → **delete instead of write**.

So a generator that returns `null` does not cache a null — it removes the key.
Do not add a code path that stores `null` as a value; the whole layer assumes
`null` means absence.

`\Closure` is overloaded the same way: `save($key, $closure)` does not store the
closure, it **runs** it under the lock exactly like `load()`'s generator and
stores the result. A closure can never be cached as a value.

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
only by `FileStorage` and `MemcachedStorage`; `Tags`/`Priority` need a `Journal`
in those two, while `SQLiteStorage` handles `Tags` natively. Unsupported
dependencies either throw (`MemcachedStorage` + `Items`) or are **silently
dropped** — nothing warns. An agent adding a backend must reason about this
**post-desugar** set, not the public constant list.

## The lock spans two method calls (and is mostly a FileStorage thing)

The `Storage::lock` contract is explicit in the interface: *"Lock is released by
`write()` or `remove()`."* This is the non-local invariant:

- `Cache::load`/`save` call `storage->lock($key)` **before** running a generator,
  then either `write()` (success) or `remove()` (generator threw) releases it.
- `FileStorage::lock` opens the file `c+b`, takes `flock(LOCK_EX)`, and stores the
  handle in `$locks[$key]`; `write()` **reuses that same handle** (removing it from
  `$locks`) and unlocks at the end. The lock therefore lives across
  `lock → write`, not within one call.
- There is **no double-checked re-read** after `lock()` — `Cache::load` locks and
  runs the generator unconditionally. The protection actually manifests in
  `read()`: concurrent readers block on `flock(LOCK_SH)` while the generating
  process holds `LOCK_EX`, and read the fresh value once `write()` unlocks. A
  lock implementation for another backend must replicate this (waiters re-read
  after the lock clears), otherwise it only serializes the duplicate work
  instead of preventing it.
- **Stampede protection is real only for `FileStorage`.** Every other built-in
  storage (`MemoryStorage`, `Memcached`, `SQLite`, `DevNull`) implements `lock()`
  as a **no-op**, so concurrent `load($key, $generator)` calls can all generate at
  once there. "Nette caching prevents stampede" is a FileStorage guarantee, not a
  universal one.

## FileStorage on-disk format and atomic writes

The file layout is: **a 6-byte zero-padded meta-length header + serialized meta +
data**. The write sequence is the subtle part and is a commit protocol:

1. under the held `LOCK_EX`, `ftruncate(0)`;
2. write a **zero-filled** header placeholder, then the data;
3. `fseek(0)` and write the **real** header **last**.

Writing the real (non-zero) header last means a reader that sees a non-zero size
is guaranteed a complete record — the header doubles as the commit marker. Reads
take `LOCK_SH`; the class docblock enumerates the three atomic operations
(read/delete/write) and the **NTFS-vs-ext3 delete fallback**: `unlink` can fail on
a locked NTFS file, so `delete()` falls back to `lock(EX) → truncate → close →
unlink`.

Two more non-obvious behaviors:

- **Reads self-heal.** `verify()` checks expiration, sliding (`touch`es the file to
  extend it), callbacks, and — **recursively** — `Items` dependencies (each
  dependent file's `MetaTime` must still match). Any failure **deletes** the entry
  on the spot, so a stale read is also a cleanup.
- **GC runs in the constructor, not at shutdown.** Each `FileStorage`
  instantiation runs `clean([])` with probability `$gcProbability` (0.001) —
  probabilistic garbage collection at construction time.

## Backends honor different subsets of dependencies

The built-in storages are `FileStorage`, `SQLiteStorage`, `MemcachedStorage`,
`MemoryStorage`, and `DevNullStorage` (Redis and others live outside this
package). They do **not** all honor the same dependencies, and the weakest are
easy traps:

- **`MemoryStorage` ignores every dependency** — its `write()` stores the raw
  value and drops `$dependencies` entirely, so there is **no expiration, no tags,
  no invalidation** (only `clean(All)` empties it). It is a process-local map, fine
  for a single request, wrong for anything that must expire.
- **`SQLiteStorage`** supports `Expire`/`Sliding` and `Tags` (its own `tags`
  table, no journal) but **silently drops `Callbacks`** — so `Files`/`Constants`
  dependencies do nothing there — and ignores `Priority` and `Items`.
- **`MemcachedStorage`** honors `Expire`/`Sliding`/`Callbacks`, throws
  `NotSupportedException` on `Items`, and its `clean(All)` calls `flush()` —
  wiping the **entire memcached server**, key prefix notwithstanding.
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
| Lock lifetime contract | `Storage::lock` (doc), `Cache::load`, `FileStorage::lock`/`write` |
| On-disk format, commit-last header | `FileStorage::write`, `readMetaAndLock` |
| Read-time verify + self-delete | `FileStorage::verify` |
| Namespace→subdir, GC | `FileStorage::getCacheFile`, constructor |
| PSR-16 (raw keys, `null` = miss) | `Bridges/Psr/PsrCacheAdapter` |
| Latte `{cache}`: nesting → `Items`, template file → `Files` | `Bridges/CacheLatte/Runtime` |
