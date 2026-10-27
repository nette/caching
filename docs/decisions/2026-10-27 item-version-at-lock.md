# FileStorage assigns an item's version when it is locked

Status: accepted · 2026-10-27 · branch `v3.4` (issue #54)

## Context

`Cache::Items` makes an item depend on another one: FileStorage records the
dependency's `MetaTime` in the dependent item, and `verify()` treats the item as
valid only while every dependency still carries that version (and is valid
itself, recursively). `MetaTime` is used for nothing else; it is an identity of
the version, not a timestamp anyone reads.

`Cache::load()` locks the key (`FileStorage::lock()`, `flock(LOCK_EX)`) before it
runs the generator and releases it in `write()` or `remove()`. A generator that
caches another item depending on the key being generated

```php
$cache->load('parent', function () use ($cache) {
	$cache->load('child', function (&$dependencies) {
		$dependencies[Cache::Items] = ['parent'];
		return ...;
	});
	return ...;
});
```

hung the process forever: `write()` of `child` read the metadata of `parent`
through a new handle with `LOCK_SH`, waiting for the `LOCK_EX` the same process
holds. Reading such a `child` inside the generator of `parent` hung the same way
in `verify()`. Verified on Windows and Linux.

The dependency is legitimate and acyclic: `child` lives as long as this version of
`parent`. What fails is only the implementation, which wants the version of an
item that has not been written yet.

## Considered options

- **Throw an exception** for a dependency on a key the process is generating -
  fails loudly, but rejects correct code and forces the programmer to restructure
  it because of a limitation of the storage.
- **Record the dependency without a version** - no hang, but `child` becomes
  invalid the moment `parent` is written, so it is never cached; a cache that
  silently does not work is the worst outcome.
- **Assign the version when the item is locked** (chosen) - the version of
  `parent` is known before its generator runs, so `child` can depend on it.

## Decision

- `lock()` assigns the version of the item (`$versions[file] = microtime()`) and
  `write()` stores it as `MetaTime`; `remove()`, which `Cache::load()` calls when
  the generator throws, discards it.
- A dependency on an item this process is generating takes its assigned version
  instead of reading the locked file, both in `write()` and in `verify()`.
- `verify()` carries the versions of the items it is verifying. Meeting one of
  them again (a cycle, now possible because a dependency can be written before
  the item it points to) it compares the version and does not recurse.

## Consequences

- (+) The pattern from #54 works as intended: `child` is generated once and stays
  cached until `parent` is rewritten, removed or its generator fails; read during
  a regeneration of `parent` it is regenerated, not served stale.
- (+) Items depending on each other live and die together instead of hanging
  every read.
- (-) Two `FileStorage` instances over the same directory in one process still
  deadlock in this pattern, as `$versions` is per instance. Rare, as DI provides a
  single storage.
- No change of API or on-disk format.
