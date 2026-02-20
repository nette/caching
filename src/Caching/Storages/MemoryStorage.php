<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette;
use Nette\Caching\Cache;


/**
 * Memory cache storage.
 */
class MemoryStorage implements Nette\Caching\Storage
{
	/** @internal cache structure */
	private const
		MetaData = 'data',
		MetaExpire = 'expire', // expiration timestamp
		MetaDelta = 'delta', // relative (sliding) expiration
		MetaTags = 'tags',
		MetaPriority = 'priority';

	/** @var array<string, array<string, mixed>>  key => meta structure */
	private array $data = [];


	public function read(string $key): mixed
	{
		if (!isset($this->data[$key])) {
			return null;
		}

		$entry = $this->data[$key];
		if ($entry[self::MetaExpire] !== null && $entry[self::MetaExpire] < time()) {
			unset($this->data[$key]);
			return null;

		} elseif ($entry[self::MetaDelta] !== null) {
			$this->data[$key][self::MetaExpire] = time() + $entry[self::MetaDelta];
		}

		return $entry[self::MetaData];
	}


	public function lock(string $key): void
	{
	}


	public function write(string $key, mixed $data, array $dependencies): void
	{
		$expire = isset($dependencies[Cache::Expire])
			? $dependencies[Cache::Expire] + time()
			: null;
		$delta = $expire !== null && !empty($dependencies[Cache::Sliding])
			? (int) $dependencies[Cache::Expire]
			: null;

		$this->data[$key] = [
			self::MetaData => $data,
			self::MetaExpire => $expire,
			self::MetaDelta => $delta,
			self::MetaTags => $dependencies[Cache::Tags] ?? [],
			self::MetaPriority => $dependencies[Cache::Priority] ?? null,
		];
	}


	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}


	public function clean(array $conditions): void
	{
		if (!empty($conditions[Cache::All])) {
			$this->data = [];
			return;
		}

		if (!empty($conditions[Cache::Tags])) {
			$tags = (array) $conditions[Cache::Tags];
			foreach ($this->data as $key => $entry) {
				if (array_intersect($tags, $entry[self::MetaTags])) {
					unset($this->data[$key]);
				}
			}
		}

		if (isset($conditions[Cache::Priority])) {
			$limit = (int) $conditions[Cache::Priority];
			foreach ($this->data as $key => $entry) {
				if ($entry[self::MetaPriority] !== null && $entry[self::MetaPriority] <= $limit) {
					unset($this->data[$key]);
				}
			}
		}
	}
}
