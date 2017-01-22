<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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


	/**
	 * Read from cache.
	 * @return mixed
	 */
	public function read(string $key)
	{
		return $this->data[$key] ?? NULL;
	}


	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 */
	public function lock(string $key): void
	{
	}


	/**
	 * Writes item into the cache.
	 */
	public function write(string $key, $data, array $dependencies): void
	{
		$this->data[$key] = $data;
	}


	/**
	 * Removes item from the cache.
	 */
	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}


	/**
	 * Removes items from the cache by conditions & garbage collector.
	 */
	public function clean(array $conditions): void
	{
		if (!empty($conditions[Nette\Caching\Cache::ALL])) {
			$this->data = [];
		}
	}

}
