<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Caching;

use Nette;
use function is_array;


/**
 * Dependencies of a cache item, the typed form of the array with Cache::* keys.
 */
final class Dependencies
{
	/** Cache::* key => property */
	private const Properties = [
		Cache::Expire => 'expire',
		Cache::Sliding => 'sliding',
		Cache::Tags => 'tags',
		Cache::Priority => 'priority',
		Cache::Files => 'files',
		Cache::Items => 'items',
		Cache::Constants => 'constants',
		Cache::Callbacks => 'callbacks',
	];


	public function __construct(
		/** expiration time, relative such as '20 minutes' or absolute, infinite if null */
		public string|int|\DateTimeInterface|null $expire = null,
		/** extends the expiration with every read */
		public bool $sliding = false,
		/** @var list<string> */
		public array $tags = [],
		public ?int $priority = null,
		/** @var list<string>  files whose change invalidates the item */
		public array $files = [],
		/** @var list<mixed>  keys of the items whose invalidation invalidates this one */
		public array $items = [],
		/** @var list<string>  names of the constants whose change invalidates the item */
		public array $constants = [],
		/** @var list<array{0: callable, 1?: mixed, 2?: mixed}>  callbacks deciding whether the item is still valid */
		public array $callbacks = [],
	) {
	}


	/**
	 * Creates the dependencies from the array with Cache::* keys, where a list may also be given as a single value,
	 * unless the value is itself an array. A value of a wrong type throws TypeError.
	 * @param  array<string, mixed>  $dependencies
	 * @throws Nette\InvalidArgumentException  when the array contains an unknown key
	 */
	public static function fromArray(array $dependencies): self
	{
		$dp = new self;
		foreach ($dependencies as $key => $value) {
			$property = self::Properties[$key] ?? throw self::createUnknownKeyException((string) $key);
			if ($value !== null) {
				$dp->$property = is_array($dp->$property) ? array_values(is_array($value) ? $value : [$value]) : $value;
			}
		}

		return $dp;
	}


	/**
	 * Returns the dependencies as the array with Cache::* keys, leaving out the unset ones.
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		$result = [];
		foreach (self::Properties as $key => $property) {
			if (!in_array($this->$property, [null, false, []], true)) {
				$result[$key] = $this->$property;
			}
		}

		return $result;
	}


	private static function createUnknownKeyException(string $key): Nette\InvalidArgumentException
	{
		if ($key === Cache::Namespaces || $key === Cache::All) {
			return new Nette\InvalidArgumentException("'$key' is a condition of Cache::clean(), not a dependency of an item.");
		}

		$hint = Nette\Utils\Helpers::getSuggestion(array_keys(self::Properties), $key);
		return new Nette\InvalidArgumentException("Unknown dependency '$key'" . ($hint ? ", did you mean '$hint'?" : '.'));
	}
}
