<?php

declare(strict_types=1);

use Nette\Caching\BulkReader;
use Nette\Caching\BulkWriter;
use Nette\Caching\Storage;

class TestStorage implements Storage
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
	}
}

class BulkReadTestStorage extends TestStorage implements BulkReader
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


	public function bulkWrite($items, array $dp): void
	{
		foreach ($items as $key => $data) {
			$this->write($key, $data, $dp);
		}
	}
}
