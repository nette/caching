# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

`Cache` -> `Storage` -> `Journal` is one layered mechanism whose traps only make
sense together (overloaded `null`, dependency desugaring, the cross-method lock,
the FileStorage format). Read `docs/internals.md` before editing them.

`docs/upgrading.md` records, per released version, every change visible to
users of the package (renames, removals, deprecations, signatures, behavior,
configuration), each with its commit hash; internal changes are not in it. It
is the place to look up the history of a public symbol. Do not write to it; it
is extended at release time from the git history.

## Project Overview

Nette Caching provides flexible caching with pluggable storage backends
(File, SQLite, Memcached, Memory, DevNull), rich dependency tracking (expiration,
tags, files, callbacks), a PSR-16 adapter, and a Latte `{cache}` tag. Part of the
Nette Framework, usable standalone.

- **PHP Version**: 8.3 - 8.6
- **Package**: `nette/caching`

## Essential Commands

```bash
# Run all tests
vendor/bin/tester tests -s        # or: composer tester

# Run one test directory / file
vendor/bin/tester tests/Storages -s
php tests/Caching/Cache.bulkLoad.phpt

# Static analysis (PHPStan level 8)
composer phpstan
```

## Conventions

- Every file starts with `declare(strict_types=1);`; Nette Coding Standard.
- Tests are Nette Tester `.phpt` files under `tests/`; `tests/bootstrap.php`
  provides `test()` and `getTempDir()` (isolated per-process temp dir).
- Constants are modern PascalCase (`Cache::Expire`) with deprecated UPPERCASE
  aliases (`Cache::EXPIRATION`) kept for BC; the `Storage` interface was `IStorage`
  before v3.1.

## Working in this repo

- **`null` is overloaded: `save($key, null)` *is* `remove()`.** `null` means cache
  miss on read, delete on write, and an already-expired `Expire` also deletes.
  Never add a path that stores `null` as a real value.
- **Dependencies are desugared before storages see them** (`completeDependencies`):
  `Files`/`Constants` become `Callbacks`, `Expire` becomes a relative second count.
  Reason about a backend's support from the *post-desugar* set, not the public
  constant list.
- **The lock spans two calls** - `Cache` locks before running a generator, and
  `write()`/`remove()` release it. **Stampede protection is real only in
  `FileStorage`**; every other backend's `lock()` is a no-op.
- **`MemoryStorage` honors only `Expire`, `Sliding`, `Tags` and `Priority`**;
  `Callbacks` (so `Files`/`Constants`), `Items` and `Namespaces` are silently
  ignored - a trap when it stands in for a real backend.
- **Unsupported dependencies throw.** `SQLiteStorage` and `MemcachedStorage`
  throw `NotSupportedException` for a dependency or clean condition they cannot
  honor.
- **FileStorage writes the real header last** (it doubles as a commit marker). Its
  constructor probabilistically runs a GC batch limited to 20 ms; only an
  explicit `clean([])` walks the whole cache.
- User-facing how-to (DI wiring, storage/journal config, PSR-16 usage, `{cache}`
  tag) is manual material and lives in the public web docs, not here.
