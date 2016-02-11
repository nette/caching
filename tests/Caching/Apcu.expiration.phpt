<?php

/**
 * Test: Nette\Caching\Storages\ApcuStorage expiration test.
 */

use Nette\Caching\Storages\ApcuStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!ApcuStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Apcu.');
}


$key = 'nette-expiration-key';
$value = 'rulez';

$cache = new Cache(new ApcuStorage());

// Writing cache...
$cache->save($key, $value, array(
	Cache::EXPIRATION => time() + 3,
));


// Sleeping 1 second
sleep(1);
Assert::true( isset($cache[$key]) );


// Sleeping 3 seconds
sleep(3);
Assert::false( isset($cache[$key]) );
