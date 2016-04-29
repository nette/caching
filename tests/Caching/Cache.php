<?php

use Nette\Caching\IBulkReadStorage;
use Nette\Caching\IStorage;

class TestStorage implements IStorage
{
	private $data = [];

	public function read($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : NULL;
	}

	public function write($key, $data, array $dependencies)
	{
		$this->data[$key] = [
			'data' => $data,
			'dependencies' => $dependencies,
		];
	}

	public function lock($key) {}

	public function remove($key) {}

	public function clean(array $conditions) {}
}

class BulkReadTestStorage extends TestStorage implements IBulkReadStorage
{
	function bulkRead(array $keys)
	{
		$result = [];
		foreach ($keys as $key) {
			$data = $this->read($key);
			if ($data !== NULL) {
				$result[$key] = $data;
			}
		}

		return $result;
	}

}
