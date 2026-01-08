<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Caching;


/**
 * Cache storage with a bulk write support.
 */
interface BulkWriter
{
	/**
	 * Writes to cache in bulk.
	 * @param  array<string, mixed>  $items
	 * @param  array<string, mixed>  $dependencies
	 */
	function bulkWrite(array $items, array $dependencies): void;

	/**
	 * Removes multiple items from cache.
	 * @param  string[]  $keys
	 */
	function bulkRemove(array $keys): void;
}
