<?php

namespace Http\Handler;

use Kiri\Kiri;
use ReflectionException;

class DataGrip
{

	private array $servers = [];


	/**
	 * @param $type
	 * @return Router
	 * @throws ReflectionException
	 */
	public function get($type): Router
	{
		if (isset($this->servers[$type])) {
			return $this->servers[$type];
		}
		$router = Kiri::getDi()->get(Router::class);
		return $this->servers[$type] = $router;
	}


}
