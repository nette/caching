<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\CacheLatte;

use Latte;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\OutputHelper;


/**
 * Runtime helpers for Latte v3.
 * @internal
 */
class Runtime
{
	/** @var array<int, OutputHelper|\stdClass> */
	private array $stack = [];


	public function __construct(
		private Nette\Caching\Storage $storage,
	) {
	}


	public function initialize(Latte\Runtime\Template $template): void
	{
		if ($this->stack) {
			$file = (new \ReflectionClass($template))->getFileName();
			if (@is_file($file)) { // @ - may trigger error
				end($this->stack)->dependencies[Cache::Files][] = $file;
			}
		}
	}


	/**
	 * Starts the output cache. Returns true if buffering was started.
	 */
	public function createCache(string $key, ?array $args = null): bool
	{
		if ($args) {
			if (array_key_exists('if', $args) && !$args['if']) {
				$this->stack[] = new \stdClass;
				return true;
			}

			$key = array_merge([$key], array_intersect_key($args, range(0, count($args))));
		}

		if ($this->stack) {
			end($this->stack)->dependencies[Cache::Items][] = $key;
		}

		$cache = new Cache($this->storage, 'Nette.Templating.Cache');
		if ($helper = $cache->capture($key)) {
			$this->stack[] = $helper;

			if (isset($args['dependencies'])) {
				$args += $args['dependencies']();
			}

			$helper->dependencies[Cache::Tags] = $args['tags'] ?? null;
			$helper->dependencies[Cache::Expire] = $args['expiration'] ?? $args['expire'] ?? '+ 7 days';
		}

		return (bool) $helper;
	}


	/**
	 * Ends the output cache.
	 */
	public function end(): void
	{
		$helper = array_pop($this->stack);
		if ($helper instanceof OutputHelper) {
			$helper->end();
		}
	}


	public function rollback(): void
	{
		$helper = array_pop($this->stack);
		if ($helper instanceof OutputHelper) {
			$helper->rollback();
		}
	}
}
