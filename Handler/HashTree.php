<?php

namespace Kiri\Message\Handler;

class HashTree
{
	
	/** @var array<HashTree> */
	private array $childes = [];
	
	
	private string $path = '';
	
	
	/**
	 * @param string $path
	 * @param HashTree $tree
	 * @return HashTree
	 */
	public function addChild(string $path, HashTree $tree): HashTree
	{
		$this->childes[$path] = $tree;
		return $tree;
	}
	
	
	/**
	 * @param string $path
	 * @return HashTree|null
	 */
	public function search(string $path): ?HashTree
	{
		foreach ($this->childes as $child) {
			if ($child->path == $path) {
				return $child;
			}
		}
		return null;
	}
	
	
}