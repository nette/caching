<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\CacheDI;

use Nette;


/**
 * Cache extension for Nette DI.
 */
class CacheExtension extends Nette\DI\CompilerExtension
{
	/** @var string */
	private $tempDir;


	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}


	public function loadConfiguration()
	{
		@mkdir($this->tempDir . '/cache'); // @ - directory may exists

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('journal'))
			->setClass(Nette\Caching\Storages\IJournal::class)
			->setFactory(Nette\Caching\Storages\SQLiteJournal::class, [$this->tempDir . '/cache/journal.s3db']);

		$builder->addDefinition($this->prefix('storage'))
			->setClass(Nette\Caching\IStorage::class)
			->setFactory(Nette\Caching\Storages\FileStorage::class, [$this->tempDir . '/cache']);

		if ($this->name === 'cache') {
			$builder->addAlias('nette.cacheJournal', $this->prefix('journal'));
			$builder->addAlias('cacheStorage', $this->prefix('storage'));
		}
	}

}
