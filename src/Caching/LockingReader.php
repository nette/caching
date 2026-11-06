<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching;


/**
 * Cache storage that generates a missing item in only one process at a time.
 */
interface LockingReader
{
	/**
	 * Reads the item; when it is missing, calls the generator, which stores it through write() or remove()
	 * of this storage while the lock is held and returns it. Other processes asking for the same item
	 * meanwhile wait and read the result.
	 * @param  \Closure(): mixed  $generator
	 */
	function readOrGenerate(string $key, \Closure $generator): mixed;
}
