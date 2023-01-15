<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\CacheLatte;

use Latte;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\Tag;
use Nette\Caching\Storage;


/**
 * Latte v3 extension for Nette Caching
 */
final class CacheExtension extends Latte\Extension
{
	private bool $used;
	private Storage $storage;


	public function __construct(Storage $storage)
	{
		$this->storage = $storage;
	}


	public function beforeCompile(Latte\Engine $engine): void
	{
		$this->used = false;
	}


	public function getTags(): array
	{
		return [
			'cache' => function (Tag $tag): \Generator {
				$this->used = true;
				return yield from Nodes\CacheNode::create($tag);
			},
		];
	}


	public function getPasses(): array
	{
		return [
			'cacheInitialization' => function (TemplateNode $node): void {
				if ($this->used) {
					$node->head->append(new AuxiliaryNode(fn() => '$this->global->cache->initialize($this);'));
				}
			},
		];
	}


	public function getProviders(): array
	{
		return [
			'cache' => new Runtime($this->storage),
		];
	}


	public function getCacheKey(Latte\Engine $engine): array
	{
		return ['version' => 2];
	}
}
