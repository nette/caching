<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette;


/**
 * Cache dummy storage.
 */
class DevNullStorage implements Nette\Caching\IStorage
{
	use Nette\SmartObject;

	public function read($key)
	{
	}


	public function lock($key)
	{
	}


	public function write($key, $data, array $dependencies)
	{
	}


	public function remove($key)
	{
	}


	public function clean(array $conditions)
	{
	}
}
