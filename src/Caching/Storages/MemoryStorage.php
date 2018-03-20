<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette;


/**
 * Memory cache storage.
 */
class MemoryStorage implements Nette\Caching\IStorage
{
	use Nette\SmartObject;

	/** @var array */
	private $data = [];


	public function read($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}


	public function lock($key)
	{
	}


	public function write($key, $data, array $dependencies)
	{
		$this->data[$key] = $data;
	}


	public function remove($key)
	{
		unset($this->data[$key]);
	}


	public function clean(array $conditions)
	{
		if (!empty($conditions[Nette\Caching\Cache::ALL])) {
			$this->data = [];
		}
	}
}
