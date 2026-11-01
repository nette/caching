<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Nette\PHPStan\Tester\TypeAssert;

TypeAssert::assertTypes(__DIR__ . '/caching-types.php');
TypeAssert::assertErrors(
	__DIR__ . '/caching-exceptions.php',
	[
		'missingType.checkedException on line 12',
		'missingType.checkedException on line 21',
		'missingType.checkedException on line 29',
	],
	[__DIR__ . '/checked-exceptions.neon'],
);
