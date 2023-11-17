<?php

declare(strict_types=1);

use Nette\Caching\BulkWriter;
use Nette\Caching\IBulkReader;
use Nette\Caching\IStorage;

class TestStorage implements IStorage
{
	private array $data = [];


	public function read(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}


	public function write(string $key, $data, array $dependencies): void
	{
		$this->data[$key] = [
			'data' => $data,
			'dependencies' => $dependencies,
		];
	}


	public function lock(string $key): void
	{
	}


	public function remove(string $key): void
	{
	}


	public function clean(array $conditions): void
	{
		if (!empty($conditions[Nette\Caching\Cache::All])) {
			$this->data = [];
			return;
		}

		//unset based by tags
		if (!empty($conditions[Nette\Caching\Cache::Tags])) {
			$unsets = [];
			foreach ($this->data as $key => $data) {
				$tags = $data['dependencies'][Nette\Caching\Cache::Tags] ?? null;
				if (array_intersect($conditions[Nette\Caching\Cache::Tags], $tags)) {
					$unsets[$key] = $key;
				}
			}

			foreach ($unsets as $unsetKey) {
				unset($this->data[$unsetKey]);
			}
		}
	}
}

class BulkReadTestStorage extends TestStorage implements IBulkReader
{
	public function bulkRead(array $keys): array
	{
		$result = [];
		foreach ($keys as $key) {
			$data = $this->read($key);
			if ($data !== null) {
				$result[$key] = $data;
			}
		}

		return $result;
	}
}

class BulkWriteTestStorage extends TestStorage implements BulkWriter
{
	public function bulkRead(array $keys): array
	{
		$result = [];
		foreach ($keys as $key) {
			$data = $this->read($key);
			if ($data !== null) {
				$result[$key] = $data;
			}
		}

		return $result;
	}


	public function bulkRemove(array $keys): void
	{

	}


	public function bulkWrite($items, array $dp): bool
	{
		foreach ($items as $key => $data) {
			$this->write($key, $data, $dp);
		}

		return true;
	}
}
