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
class MemoryStorage implements Nette\Caching\Storage
{
	use Nette\SmartObject;

	private array $data = [];


	public function read(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, $data, array $dependencies): void
	{
		$this->data[$key] = $data;
	}


	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}


	public function clean(array $conditions): void
	{
		if (!empty($conditions[Nette\Caching\Cache::All])) {
			$this->data = [];
		}
	}
}
