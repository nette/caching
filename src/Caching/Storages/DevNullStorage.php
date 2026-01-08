<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette;


/**
 * Cache dummy storage.
 */
class DevNullStorage implements Nette\Caching\Storage
{
	public function read(string $key): mixed
	{
		return null;
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, mixed $data, array $dependencies): void
	{
	}


	public function remove(string $key): void
	{
	}


	public function clean(array $conditions): void
	{
	}
}
