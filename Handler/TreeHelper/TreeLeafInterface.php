<?php

namespace Kiri\Message\Handler\TreeHelper;

use Kiri\Message\Handler\Handler;

interface TreeLeafInterface
{

	public function setPath(string $path): void;
	public function getPath(string $path): string;

	public function setHandler(Handler $handler): void;


	public function getHandler(): ?Handler;

}
