<?php

namespace Kiri\Message\Handler\TreeHelper;

use Kiri\Message\Handler\Handler;

abstract class HashTree
{


	/**
	 * @var string
	 */
	protected string $path = '';


	/**
	 * @var array<HashTree>
	 */
	protected array $leaf = [];


	/**
	 * @var Handler|null
	 */
	protected ?Handler $handler = null;


	public function getPath(string $path): string
	{
		// TODO: Implement getPath() method.
		return $this->path;
	}


	/**
	 * @param string $path
	 */
	public function setPath(string $path): void
	{
		$this->path = $path;
	}


	/**
	 * @param string $path
	 * @param HashTree $leaf
	 * @return HashTree
	 */
	public function addLeaf(string $path, mixed $leaf): TreeLeafInterface
	{
		if (isset($this->leaf[$path])) {
			return $this->leaf[$path];
		}

		$this->leaf[$path] = $leaf;

		return $leaf;
	}


	/**
	 * @param Handler $handler
	 */
	public function setHandler(Handler $handler): void
	{
		$this->handler = $handler;
	}


	/**
	 * @return Handler|null
	 */
	public function getHandler(): ?Handler
	{
		return $this->handler;
	}


	/**
	 * @return bool
	 */
	public function hasLeaf(): bool
	{
		return count($this->leaf) > 0;
	}


	/**
	 * @param string $path
	 * @return static|null
	 */
	public function searchLeaf(string $path): ?static
	{
		foreach ($this->leaf as $item) {
			if ($item->path == $path) {
				return $item;
			}
		}
		return null;
	}

}
