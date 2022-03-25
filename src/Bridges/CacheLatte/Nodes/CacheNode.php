<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\CacheLatte\Nodes;

use Latte;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Position;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Nette;
use Nette\Caching\Cache;


/**
 * {cache} ... {/cache}
 */
class CacheNode extends StatementNode
{
	public ArrayNode $args;
	public AreaNode $content;
	public ?Position $endLine;


	/** @return \Generator<int, ?array, array{AreaNode, ?Tag}, static> */
	public static function create(Tag $tag): \Generator
	{
		$node = new static;
		$node->args = $tag->parser->parseArguments();
		[$node->content, $endTag] = yield;
		$node->endLine = $endTag?->position;
		return $node;
	}


	public function print(PrintContext $context): string
	{
		return $context->format(
			<<<'XX'
				if (Nette\Bridges\CacheLatte\Nodes\CacheNode::createCache($this->global->cacheStorage, %dump, $this->global->cacheStack, %node?)) %line
				try {
					%node
					Nette\Bridges\CacheLatte\Nodes\CacheNode::endCache($this->global->cacheStack) %line;
				} catch (\Throwable $ʟ_e) {
					Nette\Bridges\CacheLatte\Nodes\CacheNode::rollback($this->global->cacheStack); throw $ʟ_e;
				}


				XX,
			Nette\Utils\Random::generate(),
			$this->args,
			$this->position,
			$this->content,
			$this->endLine,
		);
	}


	public function &getIterator(): \Generator
	{
		yield $this->args;
		yield $this->content;
	}


	/********************* run-time helpers ****************d*g**/


	public static function initRuntime(Latte\Runtime\Template $template): void
	{
		if (!empty($template->global->cacheStack)) {
			$file = (new \ReflectionClass($template))->getFileName();
			if (@is_file($file)) { // @ - may trigger error
				end($template->global->cacheStack)->dependencies[Cache::Files][] = $file;
			}
		}
	}


	/**
	 * Starts the output cache. Returns Nette\Caching\OutputHelper object if buffering was started.
	 * @return Nette\Caching\OutputHelper|\stdClass
	 */
	public static function createCache(
		Nette\Caching\Storage $cacheStorage,
		string $key,
		?array &$parents,
		?array $args = null,
	) {
		if ($args) {
			if (array_key_exists('if', $args) && !$args['if']) {
				return $parents[] = new \stdClass;
			}

			$key = array_merge([$key], array_intersect_key($args, range(0, count($args))));
		}

		if ($parents) {
			end($parents)->dependencies[Cache::Items][] = $key;
		}

		$cache = new Cache($cacheStorage, 'Nette.Templating.Cache');
		if ($helper = $cache->capture($key)) {
			$parents[] = $helper;

			if (isset($args['dependencies'])) {
				$args += $args['dependencies']();
			}

			$helper->dependencies[Cache::Tags] = $args['tags'] ?? null;
			$helper->dependencies[Cache::Expire] = $args['expiration'] ?? $args['expire'] ?? '+ 7 days';
		}

		return $helper;
	}


	/**
	 * Ends the output cache.
	 * @param  Nette\Caching\OutputHelper[]  $parents
	 */
	public static function endCache(array &$parents): void
	{
		$helper = array_pop($parents);
		if ($helper instanceof Nette\Caching\OutputHelper) {
			$helper->end();
		}
	}


	/**
	 * @param  Nette\Caching\OutputHelper[]  $parents
	 */
	public static function rollback(array &$parents): void
	{
		$helper = array_pop($parents);
		if ($helper instanceof Nette\Caching\OutputHelper) {
			$helper->rollback();
		}
	}
}
