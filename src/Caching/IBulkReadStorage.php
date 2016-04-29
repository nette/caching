<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching;


/**
 * Cache storage with a bulk read support.
 */
interface IBulkReadStorage extends IStorage
{

	/**
	 * Read from cache.
	 * @param  string key
	 * @return array key => value pairs, missing items are omitted
	 */
	function bulkRead(array $keys);

}
