<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\CacheLatte;

use Nette;
use Nette\Caching\Cache;
use Latte;


/**
 * Macro {cache} ... {/cache}
 */
class CacheMacro implements Latte\IMacro
{
	use Nette\SmartObject;

	/** @var bool */
	private $used;


	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize()
	{
		$this->used = FALSE;
	}


	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		if ($this->used) {
			return ['Nette\Bridges\CacheLatte\CacheMacro::initRuntime($this);'];
		}
	}


	/**
	 * New node is found.
	 * @return bool
	 */
	public function nodeOpened(Latte\MacroNode $node)
	{
		if ($node->modifiers) {
			throw new Latte\CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$this->used = TRUE;
		$node->empty = FALSE;
		$node->openingCode = Latte\PhpWriter::using($node)
			->write('<?php if (Nette\Bridges\CacheLatte\CacheMacro::createCache($this->global->cacheStorage, %var, $this->global->cacheStack, %node.array?)) { ?>',
				Nette\Utils\Random::generate()
			);
	}


	/**
	 * Node is closed.
	 * @return void
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		$node->closingCode = '<?php $_tmp = array_pop($this->global->cacheStack); if (!$_tmp instanceof stdClass) $_tmp->end(); } ?>';
	}


	/********************* run-time helpers ****************d*g**/


	/**
	 * @return void
	 */
	public static function initRuntime(Latte\Runtime\Template $template)
	{
		if (!empty($template->global->cacheStack)) {
			$file = (new \ReflectionClass($template))->getFileName();
			if (@is_file($file)) { // @ - may trigger error
				end($template->global->cacheStack)->dependencies[Cache::FILES][] = $file;
			}
		}
	}


	/**
	 * Starts the output cache. Returns Nette\Caching\OutputHelper object if buffering was started.
	 * @param  Nette\Caching\IStorage
	 * @param  string
	 * @param  Nette\Caching\OutputHelper[]
	 * @param  array
	 * @return Nette\Caching\OutputHelper
	 */
	public static function createCache(Nette\Caching\IStorage $cacheStorage, $key, & $parents, array $args = NULL)
	{
		if ($args) {
			if (array_key_exists('if', $args) && !$args['if']) {
				return $parents[] = new \stdClass;
			}
			$key = array_merge([$key], array_intersect_key($args, range(0, count($args))));
		}
		if ($parents) {
			end($parents)->dependencies[Cache::ITEMS][] = $key;
		}

		$cache = new Cache($cacheStorage, 'Nette.Templating.Cache');
		if ($helper = $cache->start($key)) {
			if (isset($args['dependencies'])) {
				$args += call_user_func($args['dependencies']);
			}
			if (isset($args['expire'])) {
				$args['expiration'] = $args['expire']; // back compatibility
			}
			$helper->dependencies = [
				$cache::TAGS => isset($args['tags']) ? $args['tags'] : NULL,
				$cache::EXPIRATION => isset($args['expiration']) ? $args['expiration'] : '+ 7 days',
			];
			$parents[] = $helper;
		}
		return $helper;
	}

}
