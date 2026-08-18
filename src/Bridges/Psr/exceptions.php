<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\Psr;

use Nette;
use Psr;


/**
 * Exception thrown for invalid PSR-16 cache keys.
 */
class InvalidArgumentException extends Nette\InvalidArgumentException implements Psr\SimpleCache\InvalidArgumentException
{
}
