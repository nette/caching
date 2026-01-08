<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching;

use Nette;


/**
 * Output caching helper.
 */
class OutputHelper
{
	/** @var array<string, mixed> */
	public array $dependencies = [];


	public function __construct(
		private ?Cache $cache,
		private mixed $key,
	) {
		ob_start();
	}


	/**
	 * Stops and saves the cache.
	 * @param  array<string, mixed>  $dependencies
	 */
	public function end(array $dependencies = []): void
	{
		if ($this->cache === null) {
			throw new Nette\InvalidStateException('Output cache has already been saved.');
		}

		$this->cache->save($this->key, ob_get_flush(), $dependencies + $this->dependencies);
		$this->cache = null;
	}


	/**
	 * Stops and throws away the output.
	 */
	public function rollback(): void
	{
		ob_end_flush();
		$this->cache = null;
	}
}
