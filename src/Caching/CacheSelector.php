<?php

declare(strict_types=1);

namespace Nette\Caching;

class CacheSelector
{
	private array $conditions;


	/**
	 * Adds where condition, more calls appends with AND.
	 * Pass tags as array to append with OR.
	 *
	 * Example:
	 * (new CacheSelector())->where("animal")->where("dog")->where(["brown", "white"])
	 * Creates condition looking for entities having tags animal and dog and (brown / white). Will not match entity, tagged animal, dog, black.
	 *
	 * @param  string|array  $tags  tag names to select
	 */
	public function where(string|array $tags): static
	{
		$this->conditions[] = $tags;

		return $this;
	}


	public function getConditions(): array
	{
		return $this->conditions;
	}
}
