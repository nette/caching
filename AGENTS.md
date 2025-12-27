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

## Project Overview

Nette Caching provides flexible caching with pluggable storage backends
(File, SQLite, Memcached, Memory, DevNull), rich dependency tracking (expiration,
tags, files, callbacks), a PSR-16 adapter, and a Latte `{cache}` tag. Part of the
Nette Framework, usable standalone.

- **PHP Version**: 8.1 - 8.5
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
- **`MemoryStorage` ignores all dependencies** (no expiration, no tags) - a real
  trap when it stands in for a real backend.
- **FileStorage writes the real header last** (it doubles as a commit marker) and
  runs GC probabilistically **in the constructor**, not at shutdown.
- User-facing how-to (DI wiring, storage/journal config, PSR-16 usage, `{cache}`
  tag) is manual material and lives in the public web docs, not here.
