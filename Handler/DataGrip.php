<?php

namespace Http\Handler;

use Kiri\Kiri;

class DataGrip
{

	private array $servers = [];


	/**
	 * @param $type
	 * @return Router
	 */
	public function get($type): Router
	{
		if (!isset($this->servers[$type])) {
			$this->servers[$type] = Kiri::getDi()->create(Router::class);
		}
		return $this->servers[$type];
	}


}
