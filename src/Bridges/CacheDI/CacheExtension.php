<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

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
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('journal'))
			->setClass('Nette\Caching\Storages\IJournal')
			->setFactory('Nette\Caching\Storages\FileJournal', array($this->tempDir));

		$container->addDefinition($this->prefix('storage'))
			->setClass('Nette\Caching\IStorage')
			->setFactory('Nette\Caching\Storages\FileStorage', array($this->tempDir . '/cache'));

		if ($this->name === 'cache') {
			$container->addAlias('nette.cacheJournal', $this->prefix('journal'));
			$container->addAlias('cacheStorage', $this->prefix('storage'));
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (!$this->checkTempDir($this->tempDir . '/cache')) {
			$class->getMethod('initialize')->addBody('Nette\Caching\Storages\FileStorage::$useDirectories = FALSE;');
		}
	}


	private function checkTempDir($dir)
	{
		@mkdir($dir); // @ - directory may exists

		// checks whether directory is writable
		$uniq = uniqid('_', TRUE);
		if (!@mkdir("$dir/$uniq")) { // @ - is escalated to exception
			throw new Nette\InvalidStateException("Unable to write to directory '$dir'. Make this directory writable.");
		}

		// checks whether subdirectory is writable
		$isWritable = @file_put_contents("$dir/$uniq/_", '') !== FALSE; // @ - error is expected
		if ($isWritable) {
			unlink("$dir/$uniq/_");
		}
		rmdir("$dir/$uniq");
		return $isWritable;
	}

}
