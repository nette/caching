<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\CacheDI;

use Nette;


/**
 * Cache extension for Nette DI.
 *
 * @author     David Grudl
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

		$container->addDefinition('nette.cacheJournal')
			->setClass('Nette\Caching\Storages\IJournal')
			->setFactory('Nette\Caching\Storages\FileJournal', array($this->tempDir));

		$container->addDefinition('cacheStorage') // no namespace for back compatibility
			->setClass('Nette\Caching\IStorage')
			->setFactory('Nette\Caching\Storages\FileStorage', array($this->tempDir . '/cache'));

		if (class_exists('Nette\Caching\Storages\PhpFileStorage')) {
			$container->addDefinition('nette.templateCacheStorage')
				->setClass('Nette\Caching\Storages\PhpFileStorage', array($this->tempDir . '/cache'))
				->addSetup('::trigger_error', array('Service templateCacheStorage is deprecated.', E_USER_DEPRECATED))
				->setAutowired(FALSE);
		}

		$container->addDefinition('nette.cache')
			->setClass('Nette\Caching\Cache', array(1 => $container::literal('$namespace')))
			->addSetup('::trigger_error', array('Service cache is deprecated.', E_USER_DEPRECATED))
			->setParameters(array('namespace' => NULL))
			->setAutowired(FALSE);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$class->methods['initialize']->addBody(
			'Nette\Caching\Storages\FileStorage::$useDirectories = ?;',
			array($this->checkTempDir($this->tempDir . '/cache'))
		);
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
