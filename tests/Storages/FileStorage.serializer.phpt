<?php declare(strict_types=1);

/**
 * Test: Nette\Caching\Storages\FileStorage with another format of serialized data.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class JsonFileStorage extends FileStorage
{
	protected function serializeData(mixed $data): string
	{
		return json_encode($data, JSON_THROW_ON_ERROR);
	}


	protected function unserializeData(string $data): mixed
	{
		return json_decode($data, true, flags: JSON_THROW_ON_ERROR);
	}
}


$dir = getTempDir();
$cache = new Cache(new JsonFileStorage($dir));
$cache->save('array', ['a' => 1, 'b' => [true, null]]);
$cache->save('string', 'not serialized');

Assert::same(['a' => 1, 'b' => [true, null]], $cache->load('array'));
Assert::same('not serialized', $cache->load('string'));

$files = glob($dir . '/_*');
$content = implode('', array_map('file_get_contents', $files));
Assert::contains('{"a":1,"b":[true,null]}', $content);
