<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\CacheDI;

use Nette;
use Nette\Utils\FileSystem;


/**
 * Cache extension for Nette DI.
 */
final class CacheExtension extends Nette\DI\CompilerExtension
{
	public function __construct(
		private string $tempDir,
	) {
	}


	public function loadConfiguration(): void
	{
		if (!FileSystem::isAbsolute($this->tempDir)) {
			throw new Nette\InvalidArgumentException("Cache directory must be absolute, '$this->tempDir' given.");
		}
		FileSystem::createDir($this->tempDir);
		if (!is_writable($this->tempDir)) {
			throw new Nette\InvalidStateException("Make directory '$this->tempDir' writable.");
		}

		$builder = $this->getContainerBuilder();

		if (extension_loaded('pdo_sqlite')) {
			$builder->addDefinition($this->prefix('journal'))
				->setType(Nette\Caching\Storages\Journal::class)
				->setFactory(Nette\Caching\Storages\SQLiteJournal::class, [$this->tempDir . '/journal.s3db']);
		}

		$builder->addDefinition($this->prefix('storage'))
			->setType(Nette\Caching\Storage::class)
			->setFactory(Nette\Caching\Storages\FileStorage::class, [$this->tempDir]);

		if ($this->name === 'cache') {
			if (extension_loaded('pdo_sqlite')) {
				$builder->addAlias('nette.cacheJournal', $this->prefix('journal'));
			}

			$builder->addAlias('cacheStorage', $this->prefix('storage'));
		}
	}
}
