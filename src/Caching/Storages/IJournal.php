<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;


/**
 * Cache journal provider.
 */
interface IJournal
{

	/**
	 * Writes entry information into the journal.
	 * @param  string  $key
	 * @return void
	 */
	function write($key, array $dependencies);

	/**
	 * Cleans entries from journal.
	 * @return array|null of removed items or null when performing a full cleanup
	 */
	function clean(array $conditions);
}
