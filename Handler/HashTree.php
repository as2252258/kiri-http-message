<?php

namespace Kiri\Message\Handler;

class HashTree
{
	
	/** @var array<HashTree> */
	private array $childes = [];
	
	
	private string $path = '';
	
	
	public function addChild(string $path, HashTree $tree): HashTree
	{
		$this->childes[$path] = $tree;
		return $tree;
	}
	
	
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