<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;


/**
 * Cache journal provider.
 */
interface Journal
{
	/**
	 * Writes entry information into the journal.
	 * @param  array<string, mixed>  $dependencies  {Cache::Tags => string[], Cache::Priority => int}
	 */
	function write(string $key, array $dependencies): void;

	/**
	 * Cleans entries from journal.
	 * @param  array<string, mixed>  $conditions  {Cache::Tags => string[], Cache::Priority => int, Cache::All => bool}
	 * @return list<string>|null  array of removed keys or null when performing a full cleanup
	 */
	function clean(array $conditions): ?array;
}


class_exists(IJournal::class);
