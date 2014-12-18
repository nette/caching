<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Caching\Storages;

use Nette,
	Nette\Caching\Cache;


/**
 * APCu storage.
 * @author     Filip Procházka
 */
class ApcuStorage extends Nette\Object implements Nette\Caching\IStorage
{
	/** @internal cache structure */
	const META_CALLBACKS = Cache::CALLBACKS,
		META_DATA = 'data',
		META_DELTA = 'delta';

	/** @var string */
	private $prefix;

	/** @var IJournal */
	private $journal;


	/**
	 * Checks if APCu extension is available.
	 * @return bool
	 */
	public static function isAvailable()
	{
		return (extension_loaded('apc') || extension_loaded('apcu')) && ini_get('apc.enabled');
	}


	public function __construct($prefix = '', IJournal $journal = NULL)
	{
		if (!static::isAvailable()) {
			throw new Nette\NotSupportedException("PHP extension 'apcu' is not loaded.");
		}

		$this->prefix = $prefix;
		$this->journal = $journal;
	}


	/**
	 * Read from cache.
	 * @param  string key
	 * @return mixed|NULL
	 */
	public function read($key)
	{
		$key = $this->prefix . $key;
		$meta = apc_fetch($key, $success);
		if (!$success || !$meta) {
			return NULL;
		}

		// meta structure:
		// array(
		//     data => stored data
		//     delta => relative (sliding) expiration
		//     callbacks => array of callbacks (function, args)
		// )

		// verify dependencies
		if (!empty($meta[self::META_CALLBACKS]) && !Cache::checkCallbacks($meta[self::META_CALLBACKS])) {
			apc_delete($key);
			return NULL;
		}

		if (!empty($meta[self::META_DELTA])) {
			apc_store($key, $meta, $meta[self::META_DELTA]);
		}

		return $meta[self::META_DATA];
	}


	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 * @param  string key
	 * @return void
	 */
	public function lock($key)
	{
	}


	/**
	 * Writes item into the cache.
	 * @param  string key
	 * @param  mixed  data
	 * @param  array  dependencies
	 * @return void
	 */
	public function write($key, $data, array $dp)
	{
		if (isset($dp[Cache::ITEMS])) {
			throw new Nette\NotSupportedException('Dependent items are not supported by ApcuStorage.');
		}

		$key = $this->prefix . $key;
		$meta = array(
			self::META_DATA => $data,
		);

		$expire = 0;
		if (isset($dp[Cache::EXPIRATION])) {
			$expire = (int) $dp[Cache::EXPIRATION];
			if (!empty($dp[Cache::SLIDING])) {
				$meta[self::META_DELTA] = $expire; // sliding time
			}
		}

		if (isset($dp[Cache::CALLBACKS])) {
			$meta[self::META_CALLBACKS] = $dp[Cache::CALLBACKS];
		}

		if (isset($dp[Cache::TAGS]) || isset($dp[Cache::PRIORITY])) {
			if (!$this->journal) {
				throw new Nette\InvalidStateException('CacheJournal has not been provided.');
			}
			$this->journal->write($key, $dp);
		}

		apc_store($key, $meta, $expire);
	}


	/**
	 * Removes item from the cache.
	 * @param  string key
	 * @return void
	 */
	public function remove($key)
	{
		apc_delete($this->prefix . $key);
	}


	/**
	 * Removes items from the cache by conditions & garbage collector.
	 * @param  array  conditions
	 * @return void
	 */
	public function clean(array $conditions)
	{
		if (!empty($conditions[Cache::ALL])) {
			apc_clear_cache();
			apc_clear_cache('user');

		} elseif ($this->journal) {
			foreach ($this->journal->clean($conditions) as $entry) {
				apc_delete($entry);
			}
		}
	}

}
