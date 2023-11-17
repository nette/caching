<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Caching;
use Nette\InvalidStateException;
use Nette\NotSupportedException;


/**
 * Cache storage with a bulk write support.
 */
interface BulkWriter
{
	/**
	 * Writes to cache in bulk.
	 * <p>Similar to <code>write()</code>, but instead of a single key/value item, it works on multiple items specified in <code>items</code></p>
	 *
	 * @param array{string, mixed} $items <p>An array of key/data pairs to store on the server</p>
	 * @param array $dp Global dependencies of each stored value
	 * @return bool <p>Returns <b><code>true</code></b> on success or <b><code>false</code></b> on failure</p>
	 * @throws NotSupportedException
	 * @throws InvalidStateException
	 */
	function bulkWrite(array $items, array $dp): bool;

	/**
	 * Removes multiple items from cache
	 */
	function bulkRemove(array $keys): void;
}
