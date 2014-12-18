<?php

/**
 * Test: Nette\Caching\Storages\ApcuStorage sliding expiration test.
 */

use Nette\Caching\Storages\ApcuStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!ApcuStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Apcu.');
}


$key = 'nette-sliding-key';
$value = 'rulez';

$cache = new Cache(new ApcuStorage());


// Writing cache...
$cache->save($key, $value, array(
	Cache::EXPIRATION => time() + 3,
	Cache::SLIDING => TRUE,
));


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);

	Assert::true( isset($cache[$key]) );

}

// Sleeping few seconds...
sleep(5);

Assert::false( isset($cache[$key]) );
